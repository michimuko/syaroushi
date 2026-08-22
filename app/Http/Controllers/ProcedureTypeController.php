<?php

namespace App\Http\Controllers;

use App\Enums\RecurrenceType;
use App\Models\ProcedureType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ProcedureTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        $this->authorize('viewAny', ProcedureType::class);

        return Inertia::render('ProcedureTypes/Index', [
            'procedureTypes' => ProcedureType::query()
                ->orderBy('category')
                ->orderBy('name')
                ->get(),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProcedureType $procedureType): Response
    {
        $this->authorize('update', $procedureType);

        return Inertia::render('ProcedureTypes/Edit', [
            'procedureType' => $procedureType,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProcedureType $procedureType)
    {
        $this->authorize('update', $procedureType);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'recurrence_type' => ['required', Rule::enum(RecurrenceType::class)],
            'recurrence_rule.month' => 'nullable|integer|between:1,12',
            'recurrence_rule.day' => 'nullable|integer|between:1,31',
            'recurrence_rule.interval_months' => 'nullable|integer|min:1',
            'recurrence_rule.note' => 'nullable|string|max:255',
            'default_lead_days' => 'required|array|min:1',
            'default_lead_days.*' => 'integer|min:0',
            'description' => 'nullable|string|max:2000',
            'is_active' => 'boolean',
        ]);

        // バリデーションは値が整数「らしい」かのみを検証するため、文字列のまま渡ってくる
        // （フォームエンコード経由の場合）ことがある。JSON保存前に明示的にintへキャストする。
        $validated['default_lead_days'] = array_map(intval(...), $validated['default_lead_days']);
        $validated['recurrence_rule'] = $this->sanitizedRecurrenceRule(
            RecurrenceType::from($validated['recurrence_type']),
            $validated['recurrence_rule'] ?? [],
        );

        $procedureType->update($validated);

        return redirect()->route('procedure-types.index')->with('success', '手続き種別を更新しました。');
    }

    /**
     * RecurrenceRuleResolverが実際に参照するキーのみをrecurrence_typeに応じて残す。
     * yearlyはmonth/day、monthlyはinterval_months/dayが揃って初めて自動生成対象になる
     * （揃わない場合は個別管理として自動生成しない。企画書8章・36協定届のようなケース）。
     * noteは自動生成の可否に関わらず、揃わない場合の理由等を残す任意の備考として保持する。
     */
    private function sanitizedRecurrenceRule(RecurrenceType $type, array $rule): ?array
    {
        $result = match ($type) {
            RecurrenceType::Yearly => isset($rule['month'], $rule['day'])
                ? ['month' => (int) $rule['month'], 'day' => (int) $rule['day']]
                : [],
            RecurrenceType::Monthly => isset($rule['interval_months'])
                ? ['interval_months' => (int) $rule['interval_months'], 'day' => (int) ($rule['day'] ?? 1)]
                : [],
            default => [],
        };

        if (! empty($rule['note'])) {
            $result['note'] = $rule['note'];
        }

        return $result === [] ? null : $result;
    }
}
