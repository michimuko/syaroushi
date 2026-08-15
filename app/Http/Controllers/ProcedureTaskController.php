<?php

namespace App\Http\Controllers;

use App\Enums\TaskStatus;
use App\Models\ProcedureTask;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        return Inertia::render('ProcedureTasks/Index', [
            'tasks' => $tasks,
            'filters' => [
                'status' => $validated['status'] ?? '',
                'client_id' => $validated['client_id'] ?? '',
                'assigned_user_id' => $validated['assigned_user_id'] ?? '',
                'due_from' => $validated['due_from'] ?? '',
                'due_to' => $validated['due_to'] ?? '',
            ],
            'clientOptions' => Auth::user()->office->clients()->select('id', 'name')->orderBy('name')->get(),
            'staffOptions' => Auth::user()->office->users()->select('id', 'name')->orderBy('name')->get(),
            'statusOptions' => array_map(fn (TaskStatus $status) => $status->value, TaskStatus::cases()),
        ]);
    }
}
