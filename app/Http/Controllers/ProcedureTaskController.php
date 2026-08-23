<?php

namespace App\Http\Controllers;

use App\Enums\CustomFieldTarget;
use App\Enums\TaskStatus;
use App\Http\Controllers\Concerns\ComputesHighlightPage;
use App\Models\CustomFieldDefinition;
use App\Models\ProcedureTask;
use App\Models\ProcedureType;
use App\Services\CustomFieldValueValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ProcedureTaskController extends Controller
{
    use ComputesHighlightPage;

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
            'procedure_type_id' => 'nullable|integer',
            'due_from' => 'nullable|date',
            'due_to' => 'nullable|date',
        ]);

        $tasks = ProcedureTask::query()
            ->with(['client:id,name', 'procedureType:id,name', 'assignedUser:id,name'])
            ->when($validated['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($validated['client_id'] ?? null, fn ($query, $clientId) => $query->where('client_id', $clientId))
            ->when($validated['assigned_user_id'] ?? null, fn ($query, $userId) => $query->where('assigned_user_id', $userId))
            ->when($validated['procedure_type_id'] ?? null, fn ($query, $procedureTypeId) => $query->where('procedure_type_id', $procedureTypeId))
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
                'procedure_type_id' => $validated['procedure_type_id'] ?? '',
                'due_from' => $validated['due_from'] ?? '',
                'due_to' => $validated['due_to'] ?? '',
            ],
            'clientOptions' => $this->clientOptions(),
            'staffOptions' => $this->staffOptions(),
            'procedureTypeOptions' => ProcedureType::query()
                ->where('is_active', true)
                ->orderBy('category')
                ->orderBy('name')
                ->get(['id', 'name', 'category']),
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
            'customFieldDefinitions' => $this->customFieldDefinitions(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', ProcedureTask::class);

        $rules = [
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
        ];
        $rules = array_merge($rules, app(CustomFieldValueValidator::class)->rules($this->customFieldDefinitions()));

        $validated = $request->validate($rules);

        $validated['status'] = TaskStatus::NotStarted;
        $validated['original_due_date'] = $validated['due_date'];

        $task = ProcedureTask::create($validated);

        $page = $this->pageContainingId(ProcedureTask::query()->orderBy('due_date'), $task->id);

        return redirect()->route('tasks.index', $page > 1 ? ['page' => $page] : [])
            ->with('success', 'タスクを作成しました。')
            ->with('highlightId', $task->id);
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
            'customFieldDefinitions' => $this->customFieldDefinitions(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProcedureTask $task)
    {
        $this->authorize('update', $task);

        $rules = [
            'status' => ['required', Rule::enum(TaskStatus::class)],
            'due_date' => 'sometimes|required|date',
            'assigned_user_id' => [
                'nullable',
                Rule::exists('users', 'id')->where('office_id', Auth::user()->office_id),
            ],
            'notes' => 'nullable|string|max:2000',
            'return_to' => 'nullable|string',
        ];
        $rules = array_merge($rules, app(CustomFieldValueValidator::class)->rules($this->customFieldDefinitions()));

        $validated = $request->validate($rules);

        $returnTo = $this->sanitizeReturnTo(Arr::pull($validated, 'return_to'));

        // 完了への遷移でcompleted_atを自動セット、完了からの後戻しではクリアする
        if ($validated['status'] === TaskStatus::Completed->value && $task->status !== TaskStatus::Completed) {
            $validated['completed_at'] = now();
        } elseif ($validated['status'] !== TaskStatus::Completed->value && $task->status === TaskStatus::Completed) {
            $validated['completed_at'] = null;
        }

        $task->update($validated);

        // 一覧やカレンダーなど、遷移元の画面に戻す（フィルタ条件・表示月等を保ったまま）。
        // 一覧に戻る場合のみ、更新後のソート順で実際に表示されるページへ直接遷移させる
        // （カレンダー等それ以外の遷移先ではページネーションの概念がないため対象外）。
        if ($returnTo === null) {
            $page = $this->pageContainingId(ProcedureTask::query()->orderBy('due_date'), $task->id);

            return redirect()->route('tasks.index', $page > 1 ? ['page' => $page] : [])
                ->with('success', 'タスクを更新しました。')
                ->with('highlightId', $task->id);
        }

        return redirect($returnTo)
            ->with('success', 'タスクを更新しました。')
            ->with('highlightId', $task->id);
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

    /**
     * @return Collection<int, CustomFieldDefinition>
     */
    private function customFieldDefinitions(): Collection
    {
        return CustomFieldDefinition::query()
            ->where('target', CustomFieldTarget::ProcedureTask)
            ->orderBy('id')
            ->get();
    }
}
