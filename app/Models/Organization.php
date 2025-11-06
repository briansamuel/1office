<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Organization extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'code',
        'description',
        'email',
        'phone',
        'website',
        'address',
        'logo',
        'timezone',
        'locale',
        'currency',
        'settings',
        'is_active',
        'parent_id',
    ];

    protected $casts = [
        'address' => 'array',
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
     * Get parent organization
     */
    public function parent()
    {
        return $this->belongsTo(Organization::class, 'parent_id');
    }

    /**
     * Get child organizations
     */
    public function children()
    {
        return $this->hasMany(Organization::class, 'parent_id');
    }

    /**
     * Get all descendants (recursive)
     */
    public function descendants()
    {
        return $this->children()->with('descendants');
    }

    /**
     * Get all ancestors (recursive)
     */
    public function ancestors()
    {
        return $this->parent()->with('ancestors');
    }

    /**
     * Get organization users
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
     * Get organization departments
     */
    public function departments()
    {
        return $this->hasMany(Department::class);
    }

    /**
     * Get active departments
     */
    public function activeDepartments()
    {
        return $this->departments()->where('is_active', true);
    }

    /**
     * Get organization audit logs
     */
    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class);
    }

    /**
     * Check if organization is active
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Check if organization is root (has no parent)
     */
    public function isRoot(): bool
    {
        return is_null($this->parent_id);
    }

    /**
     * Check if organization has children
     */
    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    /**
     * Get organization hierarchy level
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
     * Get organization tree path
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
     * Scope for active organizations
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for root organizations
     */
    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope for child organizations
     */
    public function scopeChildren($query)
    {
        return $query->whereNotNull('parent_id');
    }

    /**
     * Get organization statistics
     */
    public function getStatistics(): array
    {
        return [
            'total_users' => $this->users()->count(),
            'active_users' => $this->activeUsers()->count(),
            'total_departments' => $this->departments()->count(),
            'active_departments' => $this->activeDepartments()->count(),
            'child_organizations' => $this->children()->count(),
        ];
    }

    /**
     * Get organization setting
     */
    public function getSetting(string $key, $default = null)
    {
        return data_get($this->settings, $key, $default);
    }

    /**
     * Set organization setting
     */
    public function setSetting(string $key, $value): void
    {
        $settings = $this->settings ?? [];
        data_set($settings, $key, $value);
        $this->update(['settings' => $settings]);
    }

    /**
     * Get organization address field
     */
    public function getAddressField(string $field, $default = null)
    {
        return data_get($this->address, $field, $default);
    }

    /**
     * Get formatted address
     */
    public function getFormattedAddress(): string
    {
        $address = $this->address ?? [];
        
        $parts = array_filter([
            $address['street'] ?? null,
            $address['city'] ?? null,
            $address['state'] ?? null,
            $address['postal_code'] ?? null,
            $address['country'] ?? null,
        ]);
        
        return implode(', ', $parts);
    }
}
