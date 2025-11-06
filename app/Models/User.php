<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Str;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'username',
        'email',
        'password',
        'first_name',
        'last_name',
        'phone',
        'avatar',
        'profile_data',
        'is_active',
        'is_verified',
        'timezone',
        'locale',
        'preferences',
        'organization_id',
        'department_id',
        'manager_id',
        'employee_id',
        'position',
        'hire_date',
        'termination_date',
        'employment_status',
        'employment_type',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
        'is_verified' => 'boolean',
        'force_password_change' => 'boolean',
        'last_login_at' => 'datetime',
        'password_changed_at' => 'datetime',
        'hire_date' => 'date',
        'termination_date' => 'date',
        'profile_data' => 'array',
        'preferences' => 'array',
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
     * Get the user's full name
     */
    public function getFullNameAttribute(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    /**
     * Get user's organization
     */
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get user's department
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get user's manager
     */
    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /**
     * Get users managed by this user
     */
    public function subordinates()
    {
        return $this->hasMany(User::class, 'manager_id');
    }

    /**
     * Get user's roles
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_role')
                    ->withPivot(['assigned_by', 'assigned_at', 'expires_at', 'is_active', 'metadata'])
                    ->withTimestamps();
    }

    /**
     * Get user's active roles
     */
    public function activeRoles()
    {
        return $this->roles()->wherePivot('is_active', true)
                           ->where(function ($query) {
                               $query->whereNull('user_role.expires_at')
                                     ->orWhere('user_role.expires_at', '>', now());
                           });
    }

    /**
     * Get user's direct permissions
     */
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'user_permission')
                    ->withPivot(['granted_by', 'is_granted', 'conditions', 'granted_at', 'expires_at', 'reason'])
                    ->withTimestamps();
    }

    /**
     * Get all permissions (from roles and direct)
     */
    public function getAllPermissions()
    {
        $rolePermissions = $this->activeRoles()
                               ->with('permissions')
                               ->get()
                               ->pluck('permissions')
                               ->flatten()
                               ->unique('id');

        $directPermissions = $this->permissions()
                                 ->wherePivot('is_granted', true)
                                 ->where(function ($query) {
                                     $query->whereNull('user_permission.expires_at')
                                           ->orWhere('user_permission.expires_at', '>', now());
                                 })
                                 ->get();

        return $rolePermissions->merge($directPermissions)->unique('id');
    }

    /**
     * Check if user has permission
     */
    public function hasPermission(string $permission): bool
    {
        return $this->getAllPermissions()->contains('slug', $permission);
    }

    /**
     * Check if user has role
     */
    public function hasRole(string $role): bool
    {
        return $this->activeRoles()->where('slug', $role)->exists();
    }

    /**
     * Check if user has any of the given roles
     */
    public function hasAnyRole(array $roles): bool
    {
        return $this->activeRoles()->whereIn('slug', $roles)->exists();
    }

    /**
     * Check if user has all of the given roles
     */
    public function hasAllRoles(array $roles): bool
    {
        $userRoles = $this->activeRoles()->pluck('slug')->toArray();
        return empty(array_diff($roles, $userRoles));
    }

    /**
     * Assign role to user
     */
    public function assignRole(string|Role $role, ?User $assignedBy = null, ?\DateTime $expiresAt = null): void
    {
        $roleModel = is_string($role) ? Role::where('slug', $role)->firstOrFail() : $role;

        $this->roles()->syncWithoutDetaching([
            $roleModel->id => [
                'assigned_by' => $assignedBy?->id,
                'assigned_at' => now(),
                'expires_at' => $expiresAt,
                'is_active' => true,
            ]
        ]);
    }

    /**
     * Remove role from user
     */
    public function removeRole(string|Role $role): void
    {
        $roleModel = is_string($role) ? Role::where('slug', $role)->firstOrFail() : $role;
        $this->roles()->detach($roleModel->id);
    }

    /**
     * Grant permission to user
     */
    public function grantPermission(string|Permission $permission, ?User $grantedBy = null, ?\DateTime $expiresAt = null, ?string $reason = null): void
    {
        $permissionModel = is_string($permission) ? Permission::where('slug', $permission)->firstOrFail() : $permission;

        $this->permissions()->syncWithoutDetaching([
            $permissionModel->id => [
                'granted_by' => $grantedBy?->id,
                'is_granted' => true,
                'granted_at' => now(),
                'expires_at' => $expiresAt,
                'reason' => $reason,
            ]
        ]);
    }

    /**
     * Revoke permission from user
     */
    public function revokePermission(string|Permission $permission): void
    {
        $permissionModel = is_string($permission) ? Permission::where('slug', $permission)->firstOrFail() : $permission;
        $this->permissions()->detach($permissionModel->id);
    }

    /**
     * Get user's audit logs
     */
    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }

    /**
     * Check if user is super admin
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super-admin');
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin') || $this->isSuperAdmin();
    }

    /**
     * Check if user can access module
     */
    public function canAccessModule(string $module): bool
    {
        return $this->getAllPermissions()
                   ->where('module', $module)
                   ->isNotEmpty();
    }
