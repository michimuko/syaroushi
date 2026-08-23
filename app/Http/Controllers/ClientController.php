<?php

namespace App\Http\Controllers;

use App\Enums\ClientStatus;
use App\Enums\CustomFieldTarget;
use App\Http\Controllers\Concerns\ComputesHighlightPage;
use App\Models\Client;
use App\Models\CustomFieldDefinition;
use App\Models\ProcedureType;
use App\Services\CustomFieldValueValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ClientController extends Controller
{
    use ComputesHighlightPage;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Client::class);

        $validated = $request->validate([
            'search' => 'nullable|string|max:255',
            'status' => 'nullable|in:active,inactive',
        ]);

        $clients = Client::query()
            ->with('assignedUser:id,name')
            ->when($validated['search'] ?? null, fn ($query, $search) => $query->where('name', 'like', "%{$search}%")
            )
            ->when($validated['status'] ?? null, fn ($query, $status) => $query->where('status', $status)
            )
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Clients/Index', [
            'clients' => $clients,
            'filters' => [
                'search' => $validated['search'] ?? '',
                'status' => $validated['status'] ?? '',
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        $this->authorize('create', Client::class);

        return Inertia::render('Clients/Create', [
            'staffOptions' => Auth::user()->office->users()->select('id', 'name')->orderBy('name')->get(),
            'customFieldDefinitions' => $this->customFieldDefinitions(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', Client::class);

        $validated = $this->validateClient($request);

        $client = Client::create($validated);

        $page = $this->pageContainingId(Client::query()->orderBy('name'), $client->id);

        return redirect()->route('clients.index', $page > 1 ? ['page' => $page] : [])
            ->with('success', '顧問先を登録しました。')
            ->with('highlightId', $client->id);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Client $client): Response
    {
        $this->authorize('update', $client);

        return Inertia::render('Clients/Edit', [
            'client' => $client,
            'staffOptions' => Auth::user()->office->users()->select('id', 'name')->orderBy('name')->get(),
            'procedureTypes' => ProcedureType::query()
                ->where('is_active', true)
                ->orderBy('category')
                ->orderBy('name')
                ->get(['id', 'name', 'category', 'default_lead_days']),
            'subscribedProcedureTypeIds' => $client->procedureSubscriptions()
                ->where('is_active', true)
                ->pluck('procedure_type_id'),
            'leadDaysOverrides' => $client->procedureSubscriptions()
                ->whereNotNull('lead_days_override')
                ->pluck('lead_days_override', 'procedure_type_id'),
            'customFieldDefinitions' => $this->customFieldDefinitions(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Client $client)
    {
        $this->authorize('update', $client);

        $validated = $this->validateClient($request);

        $client->update($validated);

        $page = $this->pageContainingId(Client::query()->orderBy('name'), $client->id);

        return redirect()->route('clients.index', $page > 1 ? ['page' => $page] : [])
            ->with('success', '顧問先を更新しました。')
            ->with('highlightId', $client->id);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Client $client)
    {
        $this->authorize('delete', $client);

        $client->delete();

        return redirect()->route('clients.index')->with('success', '顧問先を削除しました。');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateClient(Request $request): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'representative_name' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'contract_start_date' => 'nullable|date',
            'status' => ['required', Rule::enum(ClientStatus::class)],
            'assigned_user_id' => [
                'nullable',
                Rule::exists('users', 'id')->where('office_id', Auth::user()->office_id),
            ],
            'notes' => 'nullable|string|max:2000',
        ];

        $rules = array_merge($rules, app(CustomFieldValueValidator::class)->rules($this->customFieldDefinitions()));

        return $request->validate($rules);
    }

    /**
     * @return Collection<int, CustomFieldDefinition>
     */
    private function customFieldDefinitions(): Collection
    {
        return CustomFieldDefinition::query()
            ->where('target', CustomFieldTarget::Client)
            ->orderBy('id')
            ->get();
    }
}
