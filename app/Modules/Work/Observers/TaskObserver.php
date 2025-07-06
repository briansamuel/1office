<?php

namespace App\Modules\Work\Observers;

use App\Modules\Work\Models\Task;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class TaskObserver
{
    /**
     * Handle the Task "creating" event.
     */
    public function creating(Task $task): void
    {
        Log::info('Task is being created', [
            'title' => $task->title,
            'assigned_to' => $task->assigned_to,
            'created_by' => $task->created_by,
        ]);
    }

    /**
     * Handle the Task "created" event.
     */
    public function created(Task $task): void
    {
        Log::info('Task created successfully', [
            'id' => $task->id,
            'title' => $task->title,
            'status' => $task->status->value,
            'priority' => $task->priority->value,
        ]);

        // Clear task statistics cache
        $this->clearTaskCache();

        // TODO: Send notification to assigned user
        // TODO: Create activity log entry
        // TODO: Update project statistics if task belongs to a project
    }

    /**
     * Handle the Task "updating" event.
     */
    public function updating(Task $task): void
    {
        $changes = $task->getDirty();
        
        if (!empty($changes)) {
            Log::info('Task is being updated', [
                'id' => $task->id,
                'changes' => $changes,
                'original' => $task->getOriginal(),
            ]);
        }
    }

    /**
     * Handle the Task "updated" event.
     */
    public function updated(Task $task): void
    {
        $changes = $task->getChanges();
        
        if (!empty($changes)) {
            Log::info('Task updated successfully', [
                'id' => $task->id,
                'changes' => $changes,
            ]);

            // Clear task statistics cache
            $this->clearTaskCache();

            // Handle specific field changes
            $this->handleFieldChanges($task, $changes);
        }
    }

    /**
     * Handle the Task "deleted" event.
     */
    public function deleted(Task $task): void
    {
        Log::info('Task deleted', [
            'id' => $task->id,
            'title' => $task->title,
        ]);

        // Clear task statistics cache
        $this->clearTaskCache();

        // TODO: Notify stakeholders about task deletion
        // TODO: Archive related data (comments, time logs, etc.)
    }

    /**
     * Handle the Task "restored" event.
     */
    public function restored(Task $task): void
    {
        Log::info('Task restored', [
            'id' => $task->id,
            'title' => $task->title,
        ]);

        // Clear task statistics cache
        $this->clearTaskCache();
    }

    /**
     * Handle the Task "force deleted" event.
     */
    public function forceDeleted(Task $task): void
    {
        Log::info('Task permanently deleted', [
            'id' => $task->id,
            'title' => $task->title,
        ]);

        // Clear task statistics cache
        $this->clearTaskCache();

        // TODO: Clean up related files and attachments
    }

    /**
     * Handle specific field changes
     */
    private function handleFieldChanges(Task $task, array $changes): void
    {
        // Handle status changes
        if (isset($changes['status'])) {
            $this->handleStatusChange($task, $changes['status']);
        }

        // Handle assignment changes
        if (isset($changes['assigned_to'])) {
            $this->handleAssignmentChange($task, $changes['assigned_to']);
        }

        // Handle priority changes
        if (isset($changes['priority'])) {
            $this->handlePriorityChange($task, $changes['priority']);
        }

        // Handle due date changes
        if (isset($changes['due_date'])) {
            $this->handleDueDateChange($task, $changes['due_date']);
        }
    }

    /**
     * Handle task status changes
     */
    private function handleStatusChange(Task $task, string $newStatus): void
    {
        Log::info('Task status changed', [
            'task_id' => $task->id,
            'new_status' => $newStatus,
        ]);

        // TODO: Send status change notifications
        // TODO: Update project progress if applicable
        // TODO: Trigger automation rules based on status
    }

    /**
     * Handle task assignment changes
     */
    private function handleAssignmentChange(Task $task, ?int $newAssigneeId): void
    {
        Log::info('Task assignment changed', [
            'task_id' => $task->id,
            'new_assignee' => $newAssigneeId,
        ]);

        // TODO: Send notification to new assignee
        // TODO: Send notification to previous assignee
    }

    /**
     * Handle task priority changes
     */
    private function handlePriorityChange(Task $task, string $newPriority): void
    {
        Log::info('Task priority changed', [
            'task_id' => $task->id,
            'new_priority' => $newPriority,
        ]);

        // TODO: Send high priority notifications if needed
    }

    /**
     * Handle due date changes
     */
    private function handleDueDateChange(Task $task, ?string $newDueDate): void
    {
        Log::info('Task due date changed', [
            'task_id' => $task->id,
            'new_due_date' => $newDueDate,
        ]);

        // TODO: Update calendar events
        // TODO: Send due date change notifications
    }

    /**
     * Clear task-related cache
     */
    private function clearTaskCache(): void
    {
        Cache::forget('task_statistics');
        Cache::forget('overdue_tasks_count');
        // Add more cache keys as needed
    }
}
