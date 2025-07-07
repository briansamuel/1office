<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class UserSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'session_token',
        'device_name',
        'device_type',
        'device_id',
        'ip_address',
        'user_agent',
        'browser',
        'platform',
        'location',
        'is_active',
        'last_activity',
        'login_at',
        'logout_at',
        'expires_at',
        'metadata'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_activity' => 'datetime',
        'login_at' => 'datetime',
        'logout_at' => 'datetime',
        'expires_at' => 'datetime',
        'metadata' => 'array'
    ];

    /**
     * Quan hệ với User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope cho session đang hoạt động
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
                    ->where('expires_at', '>', now());
    }

    /**
     * Scope cho session đã hết hạn
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now())
                    ->orWhere('is_active', false);
    }

    /**
     * Scope theo thiết bị
     */
    public function scopeByDevice($query, $deviceType)
    {
        return $query->where('device_type', $deviceType);
    }

    /**
     * Scope theo IP
     */
    public function scopeByIp($query, $ipAddress)
    {
        return $query->where('ip_address', $ipAddress);
    }

    /**
     * Kiểm tra session có còn hợp lệ không
     */
    public function isValid(): bool
    {
        return $this->is_active && 
               $this->expires_at > now() &&
               $this->last_activity > now()->subMinutes(config('session.lifetime', 120));
    }

    /**
     * Gia hạn session
     */
    public function extend(): void
    {
        $this->update([
            'last_activity' => now(),
            'expires_at' => now()->addMinutes(config('session.lifetime', 120))
        ]);
    }

    /**
     * Đánh dấu session là không hoạt động
     */
    public function deactivate(): void
    {
        $this->update([
            'is_active' => false,
            'logout_at' => now()
        ]);
    }

    /**
     * Lấy thông tin thiết bị đã format
     */
    public function getDeviceInfoAttribute(): string
    {
        $parts = array_filter([
            $this->browser,
            $this->platform,
            $this->device_name
        ]);

        return implode(' - ', $parts) ?: 'Unknown Device';
    }

    /**
     * Lấy thời gian hoạt động cuối cùng đã format
     */
    public function getLastActivityHumanAttribute(): string
    {
        return $this->last_activity ? $this->last_activity->diffForHumans() : 'Never';
    }

    /**
     * Kiểm tra có phải thiết bị hiện tại không
     */
    public function isCurrentDevice(string $sessionToken): bool
    {
        return $this->session_token === $sessionToken;
    }

    /**
     * Lấy icon cho loại thiết bị
     */
    public function getDeviceIconAttribute(): string
    {
        return match($this->device_type) {
            'mobile' => 'fas fa-mobile-alt',
            'tablet' => 'fas fa-tablet-alt',
            'desktop' => 'fas fa-desktop',
            default => 'fas fa-question-circle'
        };
    }

    /**
     * Lấy màu status cho thiết bị
     */
    public function getStatusColorAttribute(): string
    {
        if (!$this->is_active) {
            return 'text-gray-500';
        }

        if ($this->last_activity > now()->subMinutes(5)) {
            return 'text-green-500';
        }

        if ($this->last_activity > now()->subHour()) {
            return 'text-yellow-500';
        }

        return 'text-red-500';
    }

    /**
     * Lấy text status
     */
    public function getStatusTextAttribute(): string
    {
        if (!$this->is_active) {
            return 'Đã đăng xuất';
        }

        if ($this->last_activity > now()->subMinutes(5)) {
            return 'Đang hoạt động';
        }

        if ($this->last_activity > now()->subHour()) {
            return 'Không hoạt động';
        }

        return 'Không hoạt động lâu';
    }
}
