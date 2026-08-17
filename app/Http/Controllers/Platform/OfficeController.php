<?php

namespace App\Http\Controllers\Platform;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\BillingPlan;
use App\Models\BillingSetting;
use App\Models\Office;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        return Inertia::render('Platform/Offices/Index', [
            'offices' => Office::query()
                ->withCount('users')
                ->orderBy('name')
                ->paginate(20),
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
            'contract_plan' => 'nullable|string|max:255',
            'owner_name' => 'required|string|max:255',
            'owner_email' => 'required|string|lowercase|email|max:255|unique:users,email',
            'owner_password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        DB::transaction(function () use ($validated) {
            $office = Office::create([
                'name' => $validated['office_name'],
                'contract_plan' => $validated['contract_plan'] ?? null,
                'trial_ends_at' => now()->addDays(BillingSetting::current()->trial_days),
            ]);

            User::create([
                'office_id' => $office->id,
                'name' => $validated['owner_name'],
                'email' => $validated['owner_email'],
                'password' => Hash::make($validated['owner_password']),
                'role' => UserRole::Owner,
            ]);
        });

        return redirect()->route('platform.offices.index')->with('success', '事務所を作成しました。');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Office $office): Response
    {
        $office->loadMissing('billingPlan');

        return Inertia::render('Platform/Offices/Edit', [
            'office' => $office,
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
            'contract_plan' => 'nullable|string|max:255',
            'is_active' => 'required|boolean',
            'trial_ends_at' => 'nullable|date',
            'billing_plan_id' => 'nullable|exists:billing_plans,id',
            'custom_monthly_price' => 'nullable|integer|min:0',
        ]);

        $office->update($validated);

        return redirect()->route('platform.offices.index')->with('success', '事務所情報を更新しました。');
    }
}
