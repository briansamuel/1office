<?php

namespace App\Modules\Work\Services;

use App\Services\BaseService;
use App\Modules\Work\Repositories\TaskRepository;
use App\Modules\Work\Models\Task;
use App\Enums\TaskStatus;
use App\Enums\TaskPriority;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TaskService extends BaseService
{
    public function __construct(TaskRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Create new task with business logic
     */
    public function create(array $data): Model
    {
        $this->beforeCreate($data);
        
        $task = $this->repository->create($data);
        
        $this->afterCreate($task);
        
        return $task;
    }

    /**
     * Update task status
     */
    public function updateStatus(int $taskId, TaskStatus $status): bool
    {
        $task = $this->repository->findOrFail($taskId);
        
        // Validate status transition
        if (!$this->canTransitionTo($task->status, $status)) {
            throw new \InvalidArgumentException("Cannot transition from {$task->status->value} to {$status->value}");
        }

        $updated = $this->repository->update($taskId, ['status' => $status]);

        if ($updated) {
            Log::info("Task {$taskId} status changed to {$status->value}", [
                'task_id' => $taskId,
                'old_status' => $task->status->value,
                'new_status' => $status->value,
                'user_id' => Auth::id(),
            ]);
        }

        return $updated;
    }

    /**
     * Assign task to user
     */
    public function assignTask(int $taskId, int $userId): bool
    {
        $updated = $this->repository->update($taskId, ['assigned_to' => $userId]);

        if ($updated) {
            Log::info("Task {$taskId} assigned to user {$userId}", [
                'task_id' => $taskId,
                'assigned_to' => $userId,
                'assigned_by' => Auth::id(),
            ]);

            // TODO: Send notification to assigned user
        }

        return $updated;
    }

    /**
     * Get tasks with filters
     */
    public function getFilteredTasks(array $filters = [], int $perPage = 15)
    {
        return $this->repository->getFiltered($filters, $perPage);
    }

    /**
     * Get task statistics
     */
    public function getStatistics(): array
    {
        return $this->repository->getStatistics();
    }

    /**
     * Get Kanban board data
     */
    public function getKanbanData(array $filters = []): array
    {
        return $this->repository->getKanbanTasks($filters);
    }

    /**
     * Get user's tasks
     */
    public function getUserTasks(int $userId = null)
    {
        $userId = $userId ?? Auth::id();
        return $this->repository->getAssignedToUser($userId);
    }

    /**
     * Get overdue tasks
     */
    public function getOverdueTasks()
    {
        return $this->repository->getOverdue();
    }

    /**
     * Business logic before creating task
     */
    protected function beforeCreate(array &$data): void
    {
        // Set default values
        $data['created_by'] = Auth::id();
        $data['status'] = $data['status'] ?? TaskStatus::TODO;
        $data['priority'] = $data['priority'] ?? TaskPriority::MEDIUM;

        // Auto-assign to creator if no assignee specified
        if (!isset($data['assigned_to'])) {
            $data['assigned_to'] = Auth::id();
        }
    }

    /**
     * Business logic after creating task
     */
    protected function afterCreate(Model $task): void
    {
        Log::info("New task created", [
            'task_id' => $task->id,
            'title' => $task->title,
            'created_by' => $task->created_by,
            'assigned_to' => $task->assigned_to,
        ]);

        // TODO: Send notification to assigned user
        // TODO: Create activity log entry
    }

    /**
     * Check if status transition is valid
     */
    private function canTransitionTo(TaskStatus $currentStatus, TaskStatus $newStatus): bool
    {
        $allowedTransitions = $currentStatus->nextStatuses();
        return in_array($newStatus, $allowedTransitions) || $currentStatus === $newStatus;
    }
}
