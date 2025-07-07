<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Department extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'code',
        'description',
        'organization_id',
        'parent_id',
        'manager_id',
        'email',
        'phone',
        'settings',
        'is_active',
    ];

    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
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
     * Get department organization
     */
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Get parent department
     */
    public function parent()
    {
        return $this->belongsTo(Department::class, 'parent_id');
    }

    /**
     * Get child departments
     */
    public function children()
    {
        return $this->hasMany(Department::class, 'parent_id');
    }

    /**
     * Get all descendants (recursive)
     */
    public function descendants()
    {
        return $this->children()->with('descendants');
    }

    /**
     * Get department manager
     */
    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /**
     * Get department users
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get active users
     */
    public function activeUsers()
    {
        return $this->users()->where('is_active', true);
    }

    /**
     * Get all users including from child departments
     */
    public function allUsers()
    {
        $userIds = collect([$this->id]);
        
        // Get all descendant department IDs
        $this->getDescendantIds($userIds);
        
        return User::whereIn('department_id', $userIds);
    }

    /**
     * Helper method to get descendant department IDs
     */
    private function getDescendantIds(&$ids)
    {
        $children = $this->children;
        
        foreach ($children as $child) {
            $ids->push($child->id);
            $child->getDescendantIds($ids);
        }
    }

    /**
     * Check if department is active
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Check if department is root (has no parent)
     */
    public function isRoot(): bool
    {
        return is_null($this->parent_id);
    }

    /**
     * Check if department has children
     */
    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    /**
     * Get department hierarchy level
     */
    public function getLevel(): int
    {
        $level = 0;
        $parent = $this->parent;
        
        while ($parent) {
            $level++;
            $parent = $parent->parent;
        }
        
        return $level;
    }

    /**
     * Get department tree path
     */
    public function getTreePath(): array
    {
        $path = [$this->name];
        $parent = $this->parent;
        
        while ($parent) {
            array_unshift($path, $parent->name);
            $parent = $parent->parent;
        }
        
        return $path;
    }

    /**
     * Scope for active departments
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for root departments
     */
    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope for child departments
     */
    public function scopeChildren($query)
    {
        return $query->whereNotNull('parent_id');
    }

    /**
     * Scope for departments by organization
     */
    public function scopeByOrganization($query, $organizationId)
    {
        return $query->where('organization_id', $organizationId);
    }

    /**
     * Get department statistics
     */
    public function getStatistics(): array
    {
        return [
            'total_users' => $this->users()->count(),
            'active_users' => $this->activeUsers()->count(),
            'child_departments' => $this->children()->count(),
            'all_users' => $this->allUsers()->count(),
        ];
    }

    /**
     * Get department setting
     */
    public function getSetting(string $key, $default = null)
    {
        return data_get($this->settings, $key, $default);
    }

    /**
     * Set department setting
     */
    public function setSetting(string $key, $value): void
    {
        $settings = $this->settings ?? [];
        data_set($settings, $key, $value);
        $this->update(['settings' => $settings]);
    }

    /**
     * Check if user can manage this department
     */
    public function canBeManageBy(User $user): bool
    {
        // Department manager can manage
        if ($this->manager_id === $user->id) {
            return true;
        }

        // Parent department manager can manage
        $parent = $this->parent;
        while ($parent) {
            if ($parent->manager_id === $user->id) {
                return true;
            }
            $parent = $parent->parent;
        }

        // Organization admin can manage
        if ($user->organization_id === $this->organization_id && $user->hasRole('admin')) {
            return true;
        }

        return false;
    }

    /**
     * Get full department name with organization
     */
    public function getFullNameAttribute(): string
    {
        return $this->organization->name . ' - ' . implode(' > ', $this->getTreePath());
    }
}
