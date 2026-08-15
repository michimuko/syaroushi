<?php

namespace App\Http\Controllers;

use App\Enums\TaskStatus;
use App\Models\ProcedureTask;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * ログイン後のダッシュボード。期限の緊急度別サマリと直近のタスク一覧を表示する。
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
            'dateRanges' => [
                'yesterday' => $today->copy()->subDay()->toDateString(),
                'weekStart' => $today->toDateString(),
                'weekEnd' => $weekEnd->toDateString(),
                'monthStart' => $weekEnd->copy()->addDay()->toDateString(),
                'monthEnd' => $monthEnd->toDateString(),
            ],
        ]);
    }
}
