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
            'default_lead_days' => 'required|array|min:1',
            'default_lead_days.*' => 'integer|min:0',
            'description' => 'nullable|string|max:2000',
            'is_active' => 'boolean',
        ]);

        // バリデーションは値が整数「らしい」かのみを検証するため、文字列のまま渡ってくる
        // （フォームエンコード経由の場合）ことがある。JSON保存前に明示的にintへキャストする。
        $validated['default_lead_days'] = array_map(intval(...), $validated['default_lead_days']);

        $procedureType->update($validated);

        return redirect()->route('procedure-types.index')->with('success', '手続き種別を更新しました。');
    }
}
