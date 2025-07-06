<?php

namespace App\Modules\Work\Repositories;

use App\Repositories\BaseRepository;
use App\Modules\Work\Models\Task;
use App\Enums\TaskStatus;
use App\Enums\TaskPriority;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class TaskRepository extends BaseRepository
{
    public function __construct(Task $model)
    {
        parent::__construct($model);
    }

    /**
     * Get tasks by status
     */
    public function getByStatus(TaskStatus $status): Collection
    {
        return $this->model->byStatus($status)->get();
    }

    /**
     * Get tasks by priority
     */
    public function getByPriority(TaskPriority $priority): Collection
    {
        return $this->model->byPriority($priority)->get();
    }

    /**
     * Get tasks assigned to user
     */
    public function getAssignedToUser(int $userId): Collection
    {
        return $this->model->assignedTo($userId)->get();
    }

    /**
     * Get overdue tasks
     */
    public function getOverdue(): Collection
    {
        return $this->model->overdue()->get();
    }

    /**
     * Get tasks with filters and pagination
     */
    public function getFiltered(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->with(['assignedUser', 'creator', 'project']);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (isset($filters['assigned_to'])) {
            $query->where('assigned_to', $filters['assigned_to']);
        }

        if (isset($filters['project_id'])) {
            $query->where('project_id', $filters['project_id']);
        }

        if (isset($filters['due_date_from'])) {
            $query->where('due_date', '>=', $filters['due_date_from']);
        }

        if (isset($filters['due_date_to'])) {
            $query->where('due_date', '<=', $filters['due_date_to']);
        }

        if (isset($filters['search'])) {
            $query->where(function($q) use ($filters) {
                $q->where('title', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('description', 'like', '%' . $filters['search'] . '%');
            });
        }

        // Default ordering
        $query->orderBy('priority', 'desc')
              ->orderBy('due_date', 'asc')
              ->orderBy('created_at', 'desc');

        return $query->paginate($perPage);
    }

    /**
     * Get task statistics
     */
    public function getStatistics(): array
    {
        return [
            'total' => $this->model->count(),
            'todo' => $this->model->byStatus(TaskStatus::TODO)->count(),
            'in_progress' => $this->model->byStatus(TaskStatus::IN_PROGRESS)->count(),
            'in_review' => $this->model->byStatus(TaskStatus::IN_REVIEW)->count(),
            'completed' => $this->model->byStatus(TaskStatus::COMPLETED)->count(),
            'overdue' => $this->model->overdue()->count(),
        ];
    }

    /**
     * Get tasks for Kanban board
     */
    public function getKanbanTasks(array $filters = []): array
    {
        $query = $this->model->with(['assignedUser', 'creator']);

        if (isset($filters['assigned_to'])) {
            $query->where('assigned_to', $filters['assigned_to']);
        }

        if (isset($filters['project_id'])) {
            $query->where('project_id', $filters['project_id']);
        }

        $tasks = $query->get();

        return [
            'todo' => $tasks->where('status', TaskStatus::TODO)->values(),
            'in_progress' => $tasks->where('status', TaskStatus::IN_PROGRESS)->values(),
            'in_review' => $tasks->where('status', TaskStatus::IN_REVIEW)->values(),
            'completed' => $tasks->where('status', TaskStatus::COMPLETED)->values(),
        ];
    }
}
