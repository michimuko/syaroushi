<?php

namespace App\Http\Controllers;

use App\Models\ProcedureTask;
use App\Services\CalcAssistant\AnnualPaidLeaveCalculator;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/**
 * 計算アシスタント（企画書5-D）。現場の自作ツール由来の計算ロジックをアプリ内に統合する差別化モジュール。
 * v1では年次有給休暇の付与日数計算のみ実装。残業時間・36協定上限チェック、シフト表作成支援は今後追加予定。
 */
class CalcAssistantController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('CalcAssistant/Index');
    }

    public function showPaidLeave(Request $request): Response
    {
        return Inertia::render('CalcAssistant/PaidLeave', [
            'task' => $this->taskContext($request->query('task_id')),
            'result' => null,
            'input' => null,
        ]);
    }

    public function calculatePaidLeave(Request $request, AnnualPaidLeaveCalculator $calculator): Response
    {
        $validated = $request->validate([
            'hire_date' => 'required|date',
            'weekly_scheduled_days' => 'nullable|integer|min:1|max:4',
            'task_id' => 'nullable|integer',
        ]);

        $schedule = $calculator->calculate(
            CarbonImmutable::parse($validated['hire_date']),
            $validated['weekly_scheduled_days'] ?? null,
        );

        return Inertia::render('CalcAssistant/PaidLeave', [
            'task' => $this->taskContext($validated['task_id'] ?? null),
            'result' => [
                'hire_date' => $validated['hire_date'],
                'weekly_scheduled_days' => $validated['weekly_scheduled_days'] ?? null,
                'schedule' => $schedule,
            ],
            'input' => $validated,
        ]);
    }

    /**
     * @return array{id: int, title: string, client_name: string}|null
     */
    private function taskContext(?int $taskId): ?array
    {
        if (! $taskId) {
            return null;
        }

        $task = ProcedureTask::with('client:id,name')->find($taskId);

        if (! $task || Auth::user()->cannot('update', $task)) {
            return null;
        }

        return [
            'id' => $task->id,
            'title' => $task->title,
            'client_name' => $task->client->name,
        ];
    }
}
