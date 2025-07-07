<?php

namespace App\Enums\Work;

enum ProjectStatus: string
{
    case PLANNING = 'planning';
    case ACTIVE = 'active';
    case ON_HOLD = 'on_hold';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

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
            self::PLANNING => 'Planning',
            self::ACTIVE => 'Active',
            self::ON_HOLD => 'On Hold',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
        };
    }

    /**
     * Get status color for UI
     */
    public function color(): string
    {
        return match($this) {
            self::PLANNING => 'gray',
            self::ACTIVE => 'blue',
            self::ON_HOLD => 'yellow',
            self::COMPLETED => 'green',
            self::CANCELLED => 'red',
        };
    }

    /**
     * Get next possible statuses
     */
    public function nextStatuses(): array
    {
        return match($this) {
            self::PLANNING => [self::ACTIVE, self::CANCELLED],
            self::ACTIVE => [self::ON_HOLD, self::COMPLETED, self::CANCELLED],
            self::ON_HOLD => [self::ACTIVE, self::CANCELLED],
            self::COMPLETED => [], // Final status
            self::CANCELLED => [self::PLANNING, self::ACTIVE],
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
        return $this === self::ACTIVE;
    }

    /**
     * Check if project can have tasks
     */
    public function canHaveTasks(): bool
    {
        return !in_array($this, [self::CANCELLED]);
    }
}
