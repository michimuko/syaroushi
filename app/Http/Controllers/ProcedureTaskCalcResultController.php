<?php

namespace App\Http\Controllers;

use App\Models\ProcedureTask;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * 計算アシスタント（企画書5-D）の計算結果をタスクに保存する。
 * 権限はメモ追記・ステータス変更と同じくowner全件、staffは担当タスクのみ（企画書7.5章）。
 */
class ProcedureTaskCalcResultController extends Controller
{
    public function update(Request $request, ProcedureTask $task): RedirectResponse
    {
        $this->authorize('update', $task);

        $validated = $request->validate([
            'type' => 'required|string|max:100',
            'input' => 'required|array',
            'result' => 'required|array',
        ]);

        $task->update([
            'calc_result' => [
                'type' => $validated['type'],
                'input' => $validated['input'],
                'result' => $validated['result'],
                'calculated_at' => now()->toDateTimeString(),
            ],
        ]);

        return back()->with('success', '計算結果をタスクに保存しました。');
    }
}
