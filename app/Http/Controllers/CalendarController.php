<?php

namespace App\Http\Controllers;

use App\Models\ProcedureTask;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CalendarController extends Controller
{
    /**
     * ステータス別の色マッピング（UIデザイン指針2章と統一）。
     * 期限超過（is_overdue）は他の色より優先してred系で上書きする。
     *
     * @var array<string, string>
     */
    private const STATUS_COLORS = [
        'not_started' => '#9ca3af',
        'in_progress' => '#3b82f6',
        'documents_collected' => '#6366f1',
        'submitted' => '#f59e0b',
        'completed' => '#22c55e',
    ];

    private const OVERDUE_COLOR = '#dc2626';

    /**
     * カレンダー画面を表示する。
     */
    public function index(): Response
    {
        $this->authorize('viewAny', ProcedureTask::class);

        return Inertia::render('Calendar/Index');
    }

    /**
     * FullCalendarが表示期間ごとに呼び出すイベント取得API。
     * 表示中の期間分のみクエリし、全件取得しない（実装チェックリスト5章）。
     */
    public function events(Request $request)
    {
        $this->authorize('viewAny', ProcedureTask::class);

        $validated = $request->validate([
            'start' => 'required|date',
            'end' => 'required|date',
        ]);

        $tasks = ProcedureTask::query()
            ->with(['client:id,name', 'procedureType:id,name'])
            ->whereBetween('due_date', [$validated['start'], $validated['end']])
            ->get();

        return response()->json($tasks->map(function (ProcedureTask $task) {
            $color = $task->is_overdue
                ? self::OVERDUE_COLOR
                : self::STATUS_COLORS[$task->status->value];

            return [
                'id' => $task->id,
                'title' => "{$task->client->name} - {$task->procedureType->name}",
                'start' => $task->due_date->toDateString(),
                'allDay' => true,
                'backgroundColor' => $color,
                'borderColor' => $color,
                'textColor' => '#ffffff',
                'extendedProps' => [
                    'status' => $task->status->value,
                    'isOverdue' => $task->is_overdue,
                ],
            ];
        }));
    }
}
