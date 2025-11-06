<?php

namespace App\Enums\Work;

enum TaskStatus: string
{
    case TODO = 'todo';
    case IN_PROGRESS = 'in_progress';
    case IN_REVIEW = 'in_review';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';
    case ON_HOLD = 'on_hold';

    /**
     * Get all enum values
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Get human readable label
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
            self::TODO => [self::IN_PROGRESS, self::CANCELLED, self::ON_HOLD],
            self::IN_PROGRESS => [self::IN_REVIEW, self::COMPLETED, self::ON_HOLD, self::CANCELLED],
            self::IN_REVIEW => [self::COMPLETED, self::IN_PROGRESS, self::CANCELLED],
            self::COMPLETED => [self::IN_PROGRESS], // Có thể reopen
            self::CANCELLED => [self::TODO, self::IN_PROGRESS],
            self::ON_HOLD => [self::TODO, self::IN_PROGRESS, self::CANCELLED],
        };
    }

    /**
     * Check if status is final
     */
    public function isFinal(): bool
    {
        return in_array($this, [self::COMPLETED, self::CANCELLED]);
    }

    /**
     * Check if status is active
     */
    public function isActive(): bool
    {
        return in_array($this, [self::TODO, self::IN_PROGRESS, self::IN_REVIEW]);
    }

    /**
     * Get progress percentage for status
     */
    public function progressPercentage(): int
    {
        return match($this) {
            self::TODO => 0,
            self::IN_PROGRESS => 25,
            self::IN_REVIEW => 75,
            self::COMPLETED => 100,
            self::CANCELLED => 0,
            self::ON_HOLD => 10,
        };
    }
}
