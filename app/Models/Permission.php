<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'display_name',
        'description',
        'module',
        'resource',
        'action',
        'scope',
        'conditions',
        'is_system_permission',
        'is_active',
    ];

    protected $casts = [
        'conditions' => 'array',
        'is_system_permission' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->name);
            }
            
            // Auto-generate name if not provided
            if (empty($model->name)) {
                $model->name = $model->action . '_' . $model->resource;
                $model->slug = Str::slug($model->name);
            }
        });

        static::updating(function ($model) {
            if ($model->isDirty('name') && empty($model->slug)) {
                $model->slug = Str::slug($model->name);
            }
        });
    }

    /**
     * Get roles that have this permission
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_permission')
                    ->withPivot(['conditions', 'is_granted'])
                    ->withTimestamps();
    }

    /**
     * Get users that have this permission directly
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_permission')
                    ->withPivot(['granted_by', 'is_granted', 'conditions', 'granted_at', 'expires_at', 'reason'])
                    ->withTimestamps();
    }

    /**
     * Get all users that have this permission (through roles or direct)
     */
    public function getAllUsers()
    {
        $usersFromRoles = $this->roles()
                              ->with('activeUsers')
                              ->get()
                              ->pluck('activeUsers')
                              ->flatten()
                              ->unique('id');

        $directUsers = $this->users()
                           ->wherePivot('is_granted', true)
                           ->where(function ($query) {
                               $query->whereNull('user_permission.expires_at')
                                     ->orWhere('user_permission.expires_at', '>', now());
                           })
                           ->get();

        return $usersFromRoles->merge($directUsers)->unique('id');
    }

    /**
     * Check if permission is system permission
     */
    public function isSystemPermission(): bool
    {
        return $this->is_system_permission;
    }

    /**
     * Check if permission is active
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Scope for active permissions
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for permissions by module
     */
    public function scopeByModule($query, string $module)
    {
        return $query->where('module', $module);
    }

    /**
     * Scope for permissions by resource
     */
    public function scopeByResource($query, string $resource)
    {
        return $query->where('resource', $resource);
    }

    /**
     * Scope for permissions by action
     */
    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope for permissions by scope
     */
    public function scopeByScope($query, string $scope)
    {
        return $query->where('scope', $scope);
    }

    /**
     * Scope for system permissions
     */
    public function scopeSystemPermissions($query)
    {
        return $query->where('is_system_permission', true);
    }

    /**
     * Scope for custom permissions
     */
    public function scopeCustomPermissions($query)
    {
        return $query->where('is_system_permission', false);
    }

    /**
     * Get permission full name (module.resource.action)
     */
    public function getFullNameAttribute(): string
    {
        return $this->module . '.' . $this->resource . '.' . $this->action;
    }

    /**
     * Create permission from string format (module.resource.action)
     */
    public static function createFromString(string $permission, array $attributes = []): self
    {
        $parts = explode('.', $permission);
        
        if (count($parts) !== 3) {
            throw new \InvalidArgumentException('Permission string must be in format: module.resource.action');
        }

        [$module, $resource, $action] = $parts;

        return static::create(array_merge([
            'module' => $module,
            'resource' => $resource,
            'action' => $action,
            'name' => $permission,
            'slug' => Str::slug($permission),
            'display_name' => ucwords(str_replace(['.', '_'], ' ', $permission)),
        ], $attributes));
    }

    /**
     * Find permission by full name (module.resource.action)
     */
    public static function findByFullName(string $permission): ?self
    {
        $parts = explode('.', $permission);
        
        if (count($parts) !== 3) {
            return null;
        }

        [$module, $resource, $action] = $parts;

        return static::where('module', $module)
                    ->where('resource', $resource)
                    ->where('action', $action)
                    ->first();
    }

    /**
     * Get permissions grouped by module
     */
    public static function getGroupedByModule(): array
    {
        return static::active()
                    ->orderBy('module')
                    ->orderBy('resource')
                    ->orderBy('action')
                    ->get()
                    ->groupBy('module')
                    ->toArray();
    }

    /**
     * Get permissions for a specific module and resource
     */
    public static function getForResource(string $module, string $resource): \Illuminate\Database\Eloquent\Collection
    {
        return static::active()
                    ->where('module', $module)
                    ->where('resource', $resource)
                    ->orderBy('action')
                    ->get();
    }
}
