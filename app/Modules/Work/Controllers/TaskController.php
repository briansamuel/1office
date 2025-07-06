<?php

namespace App\Modules\Work\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Work\Services\TaskService;
use App\Modules\Work\Requests\TaskStoreRequest;
use App\Modules\Work\Requests\TaskUpdateRequest;
use App\Modules\Work\Resources\TaskResource;
use App\Enums\TaskStatus;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TaskController extends Controller
{
    private TaskService $taskService;

    public function __construct(TaskService $taskService)
    {
        $this->taskService = $taskService;
    }

    /**
     * Display a listing of tasks
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'status', 'priority', 'assigned_to', 'project_id',
            'due_date_from', 'due_date_to', 'search'
        ]);

        $perPage = $request->get('per_page', 15);
        $tasks = $this->taskService->getFilteredTasks($filters, $perPage);

        return response()->json([
            'success' => true,
            'data' => TaskResource::collection($tasks->items()),
            'meta' => [
                'current_page' => $tasks->currentPage(),
                'last_page' => $tasks->lastPage(),
                'per_page' => $tasks->perPage(),
                'total' => $tasks->total(),
            ]
        ]);
    }

    /**
     * Store a newly created task
     */
    public function store(TaskStoreRequest $request): JsonResponse
    {
        $task = $this->taskService->create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Task created successfully',
            'data' => new TaskResource($task)
        ], 201);
    }

    /**
     * Display the specified task
     */
    public function show(int $id): JsonResponse
    {
        $task = $this->taskService->getByIdOrFail($id);

        return response()->json([
            'success' => true,
            'data' => new TaskResource($task)
        ]);
    }

    /**
     * Update the specified task
     */
    public function update(TaskUpdateRequest $request, int $id): JsonResponse
    {
        $updated = $this->taskService->update($id, $request->validated());

        if (!$updated) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update task'
            ], 400);
        }

        $task = $this->taskService->getByIdOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Task updated successfully',
            'data' => new TaskResource($task)
        ]);
    }

    /**
     * Remove the specified task
     */
    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->taskService->delete($id);

        if (!$deleted) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete task'
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Task deleted successfully'
        ]);
    }

    /**
     * Update task status
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'status' => 'required|string|in:' . implode(',', TaskStatus::values())
        ]);

        $status = TaskStatus::from($request->status);
        $updated = $this->taskService->updateStatus($id, $status);

        if (!$updated) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update task status'
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Task status updated successfully'
        ]);
    }

    /**
     * Assign task to user
     */
    public function assign(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id'
        ]);

        $updated = $this->taskService->assignTask($id, $request->user_id);

        if (!$updated) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to assign task'
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Task assigned successfully'
        ]);
    }

    /**
     * Get task statistics
     */
    public function statistics(): JsonResponse
    {
        $stats = $this->taskService->getStatistics();

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * Get Kanban board data
     */
    public function kanban(Request $request): JsonResponse
    {
        $filters = $request->only(['assigned_to', 'project_id']);
        $kanbanData = $this->taskService->getKanbanData($filters);

        return response()->json([
            'success' => true,
            'data' => $kanbanData
        ]);
    }
}
