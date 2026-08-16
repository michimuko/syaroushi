<?php

namespace App\Http\Controllers;

use App\Enums\TaskStatus;
use App\Models\ProcedureTask;
use App\Models\ProcedureType;
use App\Models\User;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * ログイン後のダッシュボード。期限の緊急度別サマリと直近のタスク一覧に加え、
     * 担当者別ワークロード・手続き種別ごとの内訳（いずれも未完了タスクが対象）を表示する。
     */
    public function index(): Response
    {
        $this->authorize('viewAny', ProcedureTask::class);

        $today = Carbon::today();
        $weekEnd = $today->copy()->addDays(6);
        $monthEnd = $today->copy()->addDays(29);

        $pendingTasks = fn () => ProcedureTask::query()->where('status', '!=', TaskStatus::Completed);

        $summary = [
            'overdue' => $pendingTasks()->whereDate('due_date', '<', $today)->count(),
            'dueSoon' => $pendingTasks()
                ->whereDate('due_date', '>=', $today)
                ->whereDate('due_date', '<=', $weekEnd)
                ->count(),
            'dueLater' => $pendingTasks()
                ->whereDate('due_date', '>', $weekEnd)
                ->whereDate('due_date', '<=', $monthEnd)
                ->count(),
        ];

        $statusCounts = collect(TaskStatus::cases())->mapWithKeys(
            fn (TaskStatus $status) => [
                $status->value => ProcedureTask::query()->where('status', $status)->count(),
            ],
        );

        $upcomingTasks = ProcedureTask::query()
            ->with(['client:id,name', 'procedureType:id,name', 'assignedUser:id,name'])
            ->where('status', '!=', TaskStatus::Completed)
            ->orderBy('due_date')
            ->limit(8)
            ->get();

        return Inertia::render('Dashboard', [
            'summary' => $summary,
            'statusCounts' => $statusCounts,
            'upcomingTasks' => $upcomingTasks,
            'assigneeWorkload' => $this->assigneeWorkload($today),
            'procedureTypeBreakdown' => $this->procedureTypeBreakdown(),
            'dateRanges' => [
                'yesterday' => $today->copy()->subDay()->toDateString(),
                'weekStart' => $today->toDateString(),
                'weekEnd' => $weekEnd->toDateString(),
                'monthStart' => $weekEnd->copy()->addDay()->toDateString(),
                'monthEnd' => $monthEnd->toDateString(),
            ],
        ]);
    }

    /**
     * 担当者ごとの未完了タスク件数（うち期限超過件数）。多い順。
     *
     * @return array<int, array{assigned_user_id: int|null, name: string, total: int, overdue: int}>
     */
    private function assigneeWorkload(Carbon $today): array
    {
        $rows = ProcedureTask::query()
            ->select('assigned_user_id')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN due_date < ? THEN 1 ELSE 0 END) as overdue', [$today->toDateString()])
            ->where('status', '!=', TaskStatus::Completed)
            ->groupBy('assigned_user_id')
            ->get();

        $userNames = User::query()
            ->whereIn('id', $rows->pluck('assigned_user_id')->filter())
            ->pluck('name', 'id');

        return $rows
            ->map(fn ($row) => [
                'assigned_user_id' => $row->assigned_user_id,
                'name' => $row->assigned_user_id ? $userNames->get($row->assigned_user_id, '') : '未割当',
                'total' => (int) $row->total,
                'overdue' => (int) $row->overdue,
            ])
            ->sortByDesc('total')
            ->values()
            ->all();
    }

    /**
     * 手続き種別ごとの未完了タスク件数。多い順。
     *
     * @return array<int, array{procedure_type_id: int, name: string, total: int}>
     */
    private function procedureTypeBreakdown(): array
    {
        $rows = ProcedureTask::query()
            ->select('procedure_type_id')
            ->selectRaw('COUNT(*) as total')
            ->where('status', '!=', TaskStatus::Completed)
            ->groupBy('procedure_type_id')
            ->get();

        $names = ProcedureType::query()
            ->whereIn('id', $rows->pluck('procedure_type_id'))
            ->pluck('name', 'id');

        return $rows
            ->map(fn ($row) => [
                'procedure_type_id' => $row->procedure_type_id,
                'name' => $names->get($row->procedure_type_id, ''),
                'total' => (int) $row->total,
            ])
            ->sortByDesc('total')
            ->values()
            ->all();
    }
}
