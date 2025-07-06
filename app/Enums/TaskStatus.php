<?php

namespace App\Enums;

enum TaskStatus: string
{
    case TODO = 'todo';
    case IN_PROGRESS = 'in_progress';
    case IN_REVIEW = 'in_review';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case ON_HOLD = 'on_hold';

    /**
     * Get all status values
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get status label
     */
    public function label(): string
    {
        return match($this) {
            self::TODO => 'To Do',
            self::IN_PROGRESS => 'In Progress',
            self::IN_REVIEW => 'In Review',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
            self::ON_HOLD => 'On Hold',
        };
    }

    /**
     * Get status color for UI
     */
    public function color(): string
    {
        return match($this) {
            self::TODO => 'gray',
            self::IN_PROGRESS => 'blue',
            self::IN_REVIEW => 'yellow',
            self::COMPLETED => 'green',
            self::CANCELLED => 'red',
            self::ON_HOLD => 'orange',
        };
    }

    /**
     * Get next possible statuses
     */
    public function nextStatuses(): array
    {
        return match($this) {
            self::TODO => [self::IN_PROGRESS, self::CANCELLED],
            self::IN_PROGRESS => [self::IN_REVIEW, self::ON_HOLD, self::CANCELLED],
            self::IN_REVIEW => [self::COMPLETED, self::IN_PROGRESS],
            self::COMPLETED => [],
            self::CANCELLED => [self::TODO],
            self::ON_HOLD => [self::IN_PROGRESS, self::CANCELLED],
        };
    }
}
