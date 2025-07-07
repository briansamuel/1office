<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'display_name',
        'description',
        'module',
        'level',
        'is_system_role',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'is_system_role' => 'boolean',
        'is_active' => 'boolean',
        'metadata' => 'array',
        'level' => 'integer',
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
        });

        static::updating(function ($model) {
            if ($model->isDirty('name') && empty($model->slug)) {
                $model->slug = Str::slug($model->name);
            }
        });
    }

    /**
     * Get users with this role
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_role')
                    ->withPivot(['assigned_by', 'assigned_at', 'expires_at', 'is_active', 'metadata'])
                    ->withTimestamps();
    }

    /**
     * Get active users with this role
     */
    public function activeUsers()
    {
        return $this->users()->wherePivot('is_active', true)
                           ->where(function ($query) {
                               $query->whereNull('user_role.expires_at')
                                     ->orWhere('user_role.expires_at', '>', now());
                           });
    }

    /**
     * Get permissions for this role
     */
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_permission')
                    ->withPivot(['conditions', 'is_granted'])
                    ->withTimestamps();
    }

    /**
     * Get granted permissions for this role
     */
    public function grantedPermissions()
    {
        return $this->permissions()->wherePivot('is_granted', true);
    }

    /**
     * Check if role has permission
     */
    public function hasPermission(string $permission): bool
    {
        return $this->grantedPermissions()->where('slug', $permission)->exists();
    }

    /**
     * Grant permission to role
     */
    public function grantPermission(string|Permission $permission, ?array $conditions = null): void
    {
        $permissionModel = is_string($permission) 
            ? Permission::where('slug', $permission)->firstOrFail() 
            : $permission;
        
        $this->permissions()->syncWithoutDetaching([
            $permissionModel->id => [
                'is_granted' => true,
                'conditions' => $conditions,
            ]
        ]);
    }

    /**
     * Revoke permission from role
     */
    public function revokePermission(string|Permission $permission): void
    {
        $permissionModel = is_string($permission) 
            ? Permission::where('slug', $permission)->firstOrFail() 
            : $permission;
            
        $this->permissions()->detach($permissionModel->id);
    }

    /**
     * Sync permissions for role
     */
    public function syncPermissions(array $permissions): void
    {
        $permissionIds = [];
        
        foreach ($permissions as $permission) {
            if (is_string($permission)) {
                $permissionModel = Permission::where('slug', $permission)->firstOrFail();
                $permissionIds[$permissionModel->id] = ['is_granted' => true];
            } elseif (is_array($permission) && isset($permission['slug'])) {
                $permissionModel = Permission::where('slug', $permission['slug'])->firstOrFail();
                $permissionIds[$permissionModel->id] = [
                    'is_granted' => $permission['is_granted'] ?? true,
                    'conditions' => $permission['conditions'] ?? null,
                ];
            }
        }
        
        $this->permissions()->sync($permissionIds);
    }

    /**
     * Check if role is system role
     */
    public function isSystemRole(): bool
    {
        return $this->is_system_role;
    }

    /**
     * Check if role is active
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Scope for active roles
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for roles by module
     */
    public function scopeByModule($query, string $module)
    {
        return $query->where('module', $module);
    }

    /**
     * Scope for system roles
     */
    public function scopeSystemRoles($query)
    {
        return $query->where('is_system_role', true);
    }

    /**
     * Scope for custom roles
     */
    public function scopeCustomRoles($query)
    {
        return $query->where('is_system_role', false);
    }

    /**
     * Get roles by level (hierarchy)
     */
    public function scopeByLevel($query, int $level)
    {
        return $query->where('level', $level);
    }

    /**
     * Get roles with level greater than or equal to
     */
    public function scopeMinLevel($query, int $level)
    {
        return $query->where('level', '>=', $level);
    }

    /**
     * Get roles with level less than or equal to
     */
    public function scopeMaxLevel($query, int $level)
    {
        return $query->where('level', '<=', $level);
    }
}
