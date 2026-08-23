<?php

namespace App\Http\Controllers\Platform;

use App\Enums\Module;
use App\Enums\UserRole;
use App\Http\Controllers\Concerns\ComputesHighlightPage;
use App\Http\Controllers\Controller;
use App\Models\BillingPlan;
use App\Models\BillingSetting;
use App\Models\Office;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response;

/**
 * 運営者(platformガード)が顧客(office)を管理するコントローラー。
 * Policyは作らず、routes/platform.phpのauth:platformミドルウェアのみで
 * 認可する（運営者にロール階層はなく、認証済みなら全操作可でよいため）。
 */
class OfficeController extends Controller
{
    use ComputesHighlightPage;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $validated = $request->validate([
            'search' => 'nullable|string|max:255',
        ]);

        $offices = Office::query()
            ->withCount('users')
            ->with('billingPlan')
            ->when(
                $validated['search'] ?? null,
                fn ($query, $search) => $query->where(fn ($q) => $q
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('office_code', 'like', "%{$search}%")
                ),
            )
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Platform/Offices/Index', [
            'offices' => $offices,
            'filters' => [
                'search' => $validated['search'] ?? '',
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('Platform/Offices/Create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * 事務所の新規契約＝office新規作成＋最初のownerユーザー作成を1トランザクションで行う
     * （RegisteredUserController@storeと同じ思想）。
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'office_name' => 'required|string|max:255',
            'office_code' => ['required', 'string', 'min:3', 'max:50', 'regex:/^[a-zA-Z0-9_.-]+$/', 'unique:offices,office_code'],
            'owner_name' => 'required|string|max:255',
            // owner_login_idは事務所内でのみ一意であればよく、新規作成する事務所には
            // まだ他のユーザーがいないため、ここでの重複チェックは不要。
            'owner_login_id' => ['required', 'string', 'min:3', 'max:30', 'regex:/^[a-zA-Z0-9_.-]+$/'],
            'owner_email' => 'required|string|lowercase|email|max:255|unique:users,email',
            'owner_password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $office = DB::transaction(function () use ($validated) {
            $office = Office::create([
                'name' => $validated['office_name'],
                'office_code' => Str::lower($validated['office_code']),
                'trial_ends_at' => now()->addDays(BillingSetting::current()->trial_days),
            ]);

            // office_idはOffice::create()直後の信頼できる値。AssignsOfficeOnCreateの
            // 「認証中のwebガードユーザーの事務所に強制」フックが割り込むと、運営者が同じ
            // ブラウザで別事務所のwebガードにもログインしていた場合に書き換わってしまうため止める。
            User::withoutEvents(fn () => User::create([
                'office_id' => $office->id,
                'name' => $validated['owner_name'],
                'login_id' => $validated['owner_login_id'],
                'email' => $validated['owner_email'],
                'password' => Hash::make($validated['owner_password']),
                'role' => UserRole::Owner,
            ]));

            return $office;
        });

        $page = $this->pageContainingId(Office::query()->orderBy('name'), $office->id);

        return redirect()->route('platform.offices.index', $page > 1 ? ['page' => $page] : [])
            ->with('success', '事務所を作成しました。')
            ->with('highlightId', $office->id);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Office $office): Response
    {
        $office->loadMissing('billingPlan');

        return Inertia::render('Platform/Offices/Edit', [
            'office' => $office,
            'availableModules' => array_map(
                fn (Module $module) => ['value' => $module->value, 'label' => $module->label()],
                Module::cases(),
            ),
            'assignableBillingPlans' => BillingPlan::query()
                ->where(function ($query) use ($office) {
                    $query->where('is_active', true);
                    if ($office->billing_plan_id !== null) {
                        $query->orWhere('id', $office->billing_plan_id);
                    }
                })
                ->orderBy('sort_order')
                ->get(),
            'usage' => [
                'clientCount' => $office->billableClientCount(),
                'userCount' => $office->currentUserCount(),
            ],
            'exceededLimits' => $office->exceedsPlanLimits(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Office $office): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'office_code' => ['required', 'string', 'min:3', 'max:50', 'regex:/^[a-zA-Z0-9_.-]+$/', Rule::unique('offices', 'office_code')->ignore($office->id)],
            'is_active' => 'required|boolean',
            'trial_ends_at' => 'nullable|date',
            'enabled_modules' => 'nullable|array',
            'enabled_modules.*' => [Rule::enum(Module::class)],
            'billing_plan_id' => 'nullable|exists:billing_plans,id',
            'custom_monthly_price' => 'nullable|integer|min:0',
        ]);

        $validated['office_code'] = Str::lower($validated['office_code']);

        $office->update($validated);

        $page = $this->pageContainingId(Office::query()->orderBy('name'), $office->id);

        return redirect()->route('platform.offices.index', $page > 1 ? ['page' => $page] : [])
            ->with('success', '事務所情報を更新しました。')
            ->with('highlightId', $office->id);
    }
}
