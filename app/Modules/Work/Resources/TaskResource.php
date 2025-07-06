<?php

namespace App\Modules\Work\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'status' => [
                'value' => $this->status->value,
                'label' => $this->status->label(),
                'color' => $this->status->color(),
            ],
            'priority' => [
                'value' => $this->priority->value,
                'label' => $this->priority->label(),
                'color' => $this->priority->color(),
                'weight' => $this->priority->weight(),
            ],
            'assigned_user' => $this->whenLoaded('assignedUser', function () {
                return [
                    'id' => $this->assignedUser->id,
                    'name' => $this->assignedUser->name,
                    'email' => $this->assignedUser->email,
                    'avatar' => $this->assignedUser->avatar,
                ];
            }),
            'creator' => $this->whenLoaded('creator', function () {
                return [
                    'id' => $this->creator->id,
                    'name' => $this->creator->name,
                    'email' => $this->creator->email,
                ];
            }),
            'project' => $this->whenLoaded('project', function () {
                return [
                    'id' => $this->project->id,
                    'name' => $this->project->name,
                    'code' => $this->project->code,
                ];
            }),
            'due_date' => $this->due_date?->format('Y-m-d H:i:s'),
            'due_date_human' => $this->due_date?->diffForHumans(),
            'estimated_hours' => $this->estimated_hours,
            'actual_hours' => $this->actual_hours,
            'progress_percentage' => $this->calculateProgressPercentage(),
            'tags' => $this->tags ?? [],
            'attachments' => $this->attachments ?? [],
            'is_overdue' => $this->isOverdue(),
            'comments_count' => $this->whenCounted('comments'),
            'time_logs_count' => $this->whenCounted('timeLogs'),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
            'deleted_at' => $this->deleted_at?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Calculate task progress percentage
     */
    private function calculateProgressPercentage(): int
    {
        return match($this->status->value) {
            'todo' => 0,
            'in_progress' => 25,
            'in_review' => 75,
            'completed' => 100,
            'cancelled' => 0,
            'on_hold' => 10,
            default => 0,
        };
    }
}
