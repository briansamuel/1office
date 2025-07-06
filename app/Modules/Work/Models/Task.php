<?php

namespace App\Modules\Work\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;
use App\Enums\TaskStatus;
use App\Enums\TaskPriority;

class Task extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'status',
        'priority',
        'assigned_to',
        'created_by',
        'project_id',
        'due_date',
        'estimated_hours',
        'actual_hours',
        'tags',
        'attachments',
    ];

    protected $casts = [
        'due_date' => 'datetime',
        'status' => TaskStatus::class,
        'priority' => TaskPriority::class,
        'tags' => 'array',
        'attachments' => 'array',
        'estimated_hours' => 'decimal:2',
        'actual_hours' => 'decimal:2',
    ];

    /**
     * Get the user assigned to this task
     */
    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get the user who created this task
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the project this task belongs to
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get task comments
     */
    public function comments()
    {
        return $this->hasMany(TaskComment::class);
    }

    /**
     * Get task time logs
     */
    public function timeLogs()
    {
        return $this->hasMany(TaskTimeLog::class);
    }

    /**
     * Scope for filtering by status
     */
    public function scopeByStatus($query, TaskStatus $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for filtering by priority
     */
    public function scopeByPriority($query, TaskPriority $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope for filtering by assigned user
     */
    public function scopeAssignedTo($query, int $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    /**
     * Scope for overdue tasks
     */
    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
                    ->whereNotIn('status', [TaskStatus::COMPLETED, TaskStatus::CANCELLED]);
    }

    /**
     * Check if task is overdue
     */
    public function isOverdue(): bool
    {
        return $this->due_date && 
               $this->due_date->isPast() && 
               !in_array($this->status, [TaskStatus::COMPLETED, TaskStatus::CANCELLED]);
    }
}
