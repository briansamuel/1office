<?php

namespace App\Enums\Work;

enum TaskPriority: string
{
    case LOW = 'low';
    case NORMAL = 'normal';
    case HIGH = 'high';
    case URGENT = 'urgent';

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
            self::LOW => 'Low',
            self::NORMAL => 'Normal',
            self::HIGH => 'High',
            self::URGENT => 'Urgent',
        };
    }

    /**
     * Get priority color for UI
     */
    public function color(): string
    {
        return match($this) {
            self::LOW => 'green',
            self::NORMAL => 'blue',
            self::HIGH => 'orange',
            self::URGENT => 'red',
        };
    }

    /**
     * Get priority weight for sorting
     */
    public function weight(): int
    {
        return match($this) {
            self::LOW => 1,
            self::NORMAL => 2,
            self::HIGH => 3,
            self::URGENT => 4,
        };
    }

    /**
     * Get priority icon
     */
    public function icon(): string
    {
        return match($this) {
            self::LOW => 'arrow-down',
            self::NORMAL => 'minus',
            self::HIGH => 'arrow-up',
            self::URGENT => 'exclamation',
        };
    }

    /**
     * Check if priority is high or urgent
     */
    public function isHighPriority(): bool
    {
        return in_array($this, [self::HIGH, self::URGENT]);
    }

    /**
     * Get SLA hours based on priority
     */
    public function slaHours(): int
    {
        return match($this) {
            self::LOW => 168, // 1 week
            self::NORMAL => 72, // 3 days
            self::HIGH => 24, // 1 day
            self::URGENT => 4, // 4 hours
        };
    }
}
