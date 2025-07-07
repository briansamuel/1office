<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'user_id',
        'organization_id',
        'event',
        'auditable_type',
        'auditable_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'metadata',
        'module',
        'severity',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid();
            }
        });
    }

    /**
     * Get the user that performed the action
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the organization
     */
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get the auditable model
     */
    public function auditable()
    {
        return $this->morphTo();
    }

    /**
     * Scope for filtering by event
     */
    public function scopeByEvent($query, string $event)
    {
        return $query->where('event', $event);
    }

    /**
     * Scope for filtering by module
     */
    public function scopeByModule($query, string $module)
    {
        return $query->where('module', $module);
    }

    /**
     * Scope for filtering by severity
     */
    public function scopeBySeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }

    /**
     * Scope for filtering by user
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for filtering by organization
     */
    public function scopeByOrganization($query, int $organizationId)
    {
        return $query->where('organization_id', $organizationId);
    }

    /**
     * Scope for filtering by date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope for recent logs
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Get formatted event description
     */
    public function getEventDescriptionAttribute(): string
    {
        $descriptions = [
            'login' => 'User logged in',
            'logout' => 'User logged out',
            'create' => 'Created new record',
            'update' => 'Updated record',
            'delete' => 'Deleted record',
            'restore' => 'Restored record',
            'force_delete' => 'Permanently deleted record',
            'role_assigned' => 'Role assigned to user',
            'role_removed' => 'Role removed from user',
            'permission_granted' => 'Permission granted to user',
            'permission_revoked' => 'Permission revoked from user',
        ];

        return $descriptions[$this->event] ?? ucfirst(str_replace('_', ' ', $this->event));
    }

    /**
     * Get severity color for UI
     */
    public function getSeverityColorAttribute(): string
    {
        return match($this->severity) {
            'low' => 'green',
            'medium' => 'yellow',
            'high' => 'orange',
            'critical' => 'red',
            default => 'gray',
        };
    }

    /**
     * Get changes summary
     */
    public function getChangesSummary(): array
    {
        $changes = [];
        
        if ($this->old_values && $this->new_values) {
            foreach ($this->new_values as $key => $newValue) {
                $oldValue = $this->old_values[$key] ?? null;
                
                if ($oldValue !== $newValue) {
                    $changes[$key] = [
                        'old' => $oldValue,
                        'new' => $newValue,
                    ];
                }
            }
        }
        
        return $changes;
    }

    /**
     * Create audit log entry
     */
    public static function createLog(array $data): self
    {
        // Auto-fill some fields if not provided
        $data['user_id'] = $data['user_id'] ?? auth()->id();
        $data['organization_id'] = $data['organization_id'] ?? auth()->user()?->organization_id;
        $data['ip_address'] = $data['ip_address'] ?? request()->ip();
        $data['user_agent'] = $data['user_agent'] ?? request()->userAgent();

        return static::create($data);
    }

    /**
     * Log model event
     */
    public static function logModelEvent(string $event, Model $model, ?array $oldValues = null, ?array $metadata = null): self
    {
        return static::createLog([
            'event' => $event,
            'auditable_type' => get_class($model),
            'auditable_id' => $model->getKey(),
            'old_values' => $oldValues,
            'new_values' => $model->getAttributes(),
            'metadata' => $metadata,
            'module' => static::getModuleFromModel($model),
            'severity' => static::getSeverityFromEvent($event),
        ]);
    }

    /**
     * Log authentication event
     */
    public static function logAuthEvent(string $event, ?User $user = null, ?array $metadata = null): self
    {
        return static::createLog([
            'event' => $event,
            'user_id' => $user?->id,
            'organization_id' => $user?->organization_id,
            'metadata' => $metadata,
            'module' => 'auth',
            'severity' => static::getSeverityFromEvent($event),
        ]);
    }

    /**
     * Get module from model class
     */
    private static function getModuleFromModel(Model $model): string
    {
        $class = get_class($model);
        
        if (str_contains($class, '\\Modules\\Work\\')) {
            return 'work';
        } elseif (str_contains($class, '\\Modules\\HRM\\')) {
            return 'hrm';
        } elseif (str_contains($class, '\\Modules\\CRM\\')) {
            return 'crm';
        } elseif (str_contains($class, '\\Modules\\Warehouse\\')) {
            return 'warehouse';
        }
        
        return 'system';
    }

    /**
     * Get severity from event type
     */
    private static function getSeverityFromEvent(string $event): string
    {
        $criticalEvents = ['force_delete', 'login_failed_multiple', 'security_breach'];
        $highEvents = ['delete', 'role_assigned', 'permission_granted', 'login_failed'];
        $mediumEvents = ['update', 'role_removed', 'permission_revoked', 'logout'];
        
        if (in_array($event, $criticalEvents)) {
            return 'critical';
        } elseif (in_array($event, $highEvents)) {
            return 'high';
        } elseif (in_array($event, $mediumEvents)) {
            return 'medium';
        }
        
        return 'low';
    }
}
