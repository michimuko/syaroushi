<?php

namespace App\Http\Controllers;

use App\Enums\TaskStatus;
use App\Models\ProcedureTask;
use App\Models\ProcedureType;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ProcedureTaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', ProcedureTask::class);

        $validated = $request->validate([
            'status' => 'nullable|in:not_started,in_progress,documents_collected,submitted,completed',
            'client_id' => 'nullable|integer',
            'assigned_user_id' => 'nullable|integer',
            'due_from' => 'nullable|date',
            'due_to' => 'nullable|date',
        ]);

        $tasks = ProcedureTask::query()
            ->with(['client:id,name', 'procedureType:id,name', 'assignedUser:id,name'])
            ->when($validated['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($validated['client_id'] ?? null, fn ($query, $clientId) => $query->where('client_id', $clientId))
            ->when($validated['assigned_user_id'] ?? null, fn ($query, $userId) => $query->where('assigned_user_id', $userId))
            ->when($validated['due_from'] ?? null, fn ($query, $date) => $query->whereDate('due_date', '>=', $date))
            ->when($validated['due_to'] ?? null, fn ($query, $date) => $query->whereDate('due_date', '<=', $date))
            ->orderBy('due_date')
            ->paginate(20)
            ->withQueryString();

        // Collection::each()はコールバックがfalseを返すと反復を打ち切るため、
        // 代入式（真偽値を返す）をそのままアロー関数にしない
        $tasks->getCollection()->each(function (ProcedureTask $task) {
            $task->can_update = Auth::user()->can('update', $task);
        });

        return Inertia::render('ProcedureTasks/Index', [
            'tasks' => $tasks,
            'filters' => [
                'status' => $validated['status'] ?? '',
                'client_id' => $validated['client_id'] ?? '',
                'assigned_user_id' => $validated['assigned_user_id'] ?? '',
                'due_from' => $validated['due_from'] ?? '',
                'due_to' => $validated['due_to'] ?? '',
            ],
            'clientOptions' => $this->clientOptions(),
            'staffOptions' => $this->staffOptions(),
            'statusOptions' => array_map(fn (TaskStatus $status) => $status->value, TaskStatus::cases()),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        $this->authorize('create', ProcedureTask::class);

        return Inertia::render('ProcedureTasks/Create', [
            'clientOptions' => $this->clientOptions(),
            'staffOptions' => $this->staffOptions(),
            'procedureTypeOptions' => ProcedureType::query()
                ->where('is_active', true)
                ->orderBy('category')
                ->orderBy('name')
                ->get(['id', 'name', 'category']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', ProcedureTask::class);

        $validated = $request->validate([
            'client_id' => [
                'required',
                Rule::exists('clients', 'id')->where('office_id', Auth::user()->office_id),
            ],
            'procedure_type_id' => 'required|exists:procedure_types,id',
            'title' => 'required|string|max:255',
            'due_date' => 'required|date',
            'assigned_user_id' => [
                'nullable',
                Rule::exists('users', 'id')->where('office_id', Auth::user()->office_id),
            ],
            'notes' => 'nullable|string|max:2000',
        ]);

        $validated['status'] = TaskStatus::NotStarted;
        $validated['original_due_date'] = $validated['due_date'];

        ProcedureTask::create($validated);

        return redirect()->route('tasks.index')->with('success', 'タスクを作成しました。');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, ProcedureTask $task): Response
    {
        $this->authorize('view', $task);

        $task->load(['client:id,name', 'procedureType:id,name,category', 'documents']);

        return Inertia::render('ProcedureTasks/Edit', [
            'task' => $task,
            'staffOptions' => $this->staffOptions(),
            'canUpdate' => Auth::user()->can('update', $task),
            'returnTo' => $this->sanitizeReturnTo($request->query('return_to')),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProcedureTask $task)
    {
        $this->authorize('update', $task);

        $validated = $request->validate([
            'status' => ['required', Rule::enum(TaskStatus::class)],
            'due_date' => 'sometimes|required|date',
            'assigned_user_id' => [
                'nullable',
                Rule::exists('users', 'id')->where('office_id', Auth::user()->office_id),
            ],
            'notes' => 'nullable|string|max:2000',
            'return_to' => 'nullable|string',
        ]);

        $returnTo = $this->sanitizeReturnTo(Arr::pull($validated, 'return_to'));

        // 完了への遷移でcompleted_atを自動セット、完了からの後戻しではクリアする
        if ($validated['status'] === TaskStatus::Completed->value && $task->status !== TaskStatus::Completed) {
            $validated['completed_at'] = now();
        } elseif ($validated['status'] !== TaskStatus::Completed->value && $task->status === TaskStatus::Completed) {
            $validated['completed_at'] = null;
        }

        $task->update($validated);

        // 一覧やカレンダーなど、遷移元の画面に戻す（フィルタ条件・表示月等を保ったまま）
        return redirect($returnTo ?? route('tasks.index'))->with('success', 'タスクを更新しました。');
    }

    /**
     * 遷移元URLとして安全に使えるか検証する（同一オリジンの相対パスのみ許可し、外部サイトへのリダイレクトを防ぐ）。
     */
    private function sanitizeReturnTo(?string $path): ?string
    {
        if (! $path || ! str_starts_with($path, '/') || str_starts_with($path, '//')) {
            return null;
        }

        return $path;
    }

    private function clientOptions(): Collection
    {
        return Auth::user()->office->clients()->select('id', 'name')->orderBy('name')->get();
    }

    private function staffOptions(): Collection
    {
        return Auth::user()->office->users()->select('id', 'name')->orderBy('name')->get();
    }
}
