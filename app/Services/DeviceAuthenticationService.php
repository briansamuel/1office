<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserSession;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Jenssegers\Agent\Agent;
use Carbon\Carbon;

class DeviceAuthenticationService
{
    protected Agent $agent;

    public function __construct()
    {
        $this->agent = new Agent();
    }

    /**
     * Đăng nhập và tạo session cho thiết bị
     */
    public function login(Request $request, array $credentials, bool $remember = false): array
    {
        if (!Auth::attempt($credentials, $remember)) {
            return [
                'success' => false,
                'message' => 'Thông tin đăng nhập không chính xác'
            ];
        }

        $user = Auth::user();
        $deviceInfo = $this->getDeviceInfo($request);
        
        // Tạo session token
        $sessionToken = $this->generateSessionToken();
        
        // Tạo session record
        $session = $this->createUserSession($user, $request, $sessionToken, $deviceInfo);
        
        // Lưu session token vào session
        session(['device_session_token' => $sessionToken]);
        
        // Kiểm tra giới hạn thiết bị
        $this->enforceDeviceLimit($user);
        
        return [
            'success' => true,
            'message' => 'Đăng nhập thành công',
            'session' => $session,
            'device_info' => $deviceInfo
        ];
    }

    /**
     * Đăng xuất từ thiết bị hiện tại
     */
    public function logout(Request $request): array
    {
        $user = Auth::user();
        $sessionToken = session('device_session_token');
        
        if ($user && $sessionToken) {
            $session = UserSession::where('user_id', $user->id)
                                 ->where('session_token', $sessionToken)
                                 ->first();
            
            if ($session) {
                $session->deactivate();
            }
        }
        
        Auth::logout();
        session()->forget('device_session_token');
        
        return [
            'success' => true,
            'message' => 'Đăng xuất thành công'
        ];
    }

    /**
     * Đăng xuất từ thiết bị cụ thể
     */
    public function logoutFromDevice(User $user, string $sessionToken): array
    {
        $session = UserSession::where('user_id', $user->id)
                             ->where('session_token', $sessionToken)
                             ->first();
        
        if (!$session) {
            return [
                'success' => false,
                'message' => 'Không tìm thấy phiên đăng nhập'
            ];
        }
        
        $session->deactivate();
        
        return [
            'success' => true,
            'message' => 'Đã đăng xuất khỏi thiết bị thành công'
        ];
    }

    /**
     * Đăng xuất từ tất cả thiết bị khác
     */
    public function logoutFromOtherDevices(User $user, string $currentSessionToken): array
    {
        $affectedRows = UserSession::where('user_id', $user->id)
                                  ->where('session_token', '!=', $currentSessionToken)
                                  ->where('is_active', true)
                                  ->update([
                                      'is_active' => false,
                                      'logout_at' => now()
                                  ]);
        
        return [
            'success' => true,
            'message' => "Đã đăng xuất khỏi {$affectedRows} thiết bị khác",
            'affected_devices' => $affectedRows
        ];
    }

    /**
     * Đăng xuất từ tất cả thiết bị
     */
    public function logoutFromAllDevices(User $user): array
    {
        $affectedRows = UserSession::where('user_id', $user->id)
                                  ->where('is_active', true)
                                  ->update([
                                      'is_active' => false,
                                      'logout_at' => now()
                                  ]);
        
        return [
            'success' => true,
            'message' => "Đã đăng xuất khỏi tất cả {$affectedRows} thiết bị",
            'affected_devices' => $affectedRows
        ];
    }

    /**
     * Lấy danh sách thiết bị đang đăng nhập
     */
    public function getActiveDevices(User $user): array
    {
        $sessions = UserSession::where('user_id', $user->id)
                              ->active()
                              ->orderBy('last_activity', 'desc')
                              ->get();
        
        $currentSessionToken = session('device_session_token');
        
        return $sessions->map(function ($session) use ($currentSessionToken) {
            return [
                'id' => $session->id,
                'session_token' => $session->session_token,
                'device_info' => $session->device_info,
                'device_type' => $session->device_type,
                'device_icon' => $session->device_icon,
                'ip_address' => $session->ip_address,
                'location' => $session->location,
                'last_activity' => $session->last_activity_human,
                'login_at' => $session->login_at->format('d/m/Y H:i'),
                'status_color' => $session->status_color,
                'status_text' => $session->status_text,
                'is_current' => $session->isCurrentDevice($currentSessionToken)
            ];
        })->toArray();
    }

    /**
     * Cập nhật hoạt động của session
     */
    public function updateActivity(Request $request): void
    {
        $user = Auth::user();
        $sessionToken = session('device_session_token');
        
        if ($user && $sessionToken) {
            $session = UserSession::where('user_id', $user->id)
                                 ->where('session_token', $sessionToken)
                                 ->first();
            
            if ($session && $session->isValid()) {
                $session->extend();
            }
        }
    }

    /**
     * Tạo session token
     */
    protected function generateSessionToken(): string
    {
        return Str::random(60);
    }

    /**
     * Lấy thông tin thiết bị
     */
    protected function getDeviceInfo(Request $request): array
    {
        $this->agent->setUserAgent($request->userAgent());
        
        $deviceType = 'unknown';
        if ($this->agent->isMobile()) {
            $deviceType = 'mobile';
        } elseif ($this->agent->isTablet()) {
            $deviceType = 'tablet';
        } elseif ($this->agent->isDesktop()) {
            $deviceType = 'desktop';
        }
        
        return [
            'device_type' => $deviceType,
            'device_name' => $this->agent->device() ?: 'Unknown Device',
            'browser' => $this->agent->browser(),
            'platform' => $this->agent->platform(),
            'user_agent' => $request->userAgent(),
            'ip_address' => $request->ip(),
            'device_id' => $this->generateDeviceId($request)
        ];
    }

    /**
     * Tạo device ID duy nhất
     */
    protected function generateDeviceId(Request $request): string
    {
        $fingerprint = $request->userAgent() . $request->ip();
        return hash('sha256', $fingerprint);
    }

    /**
     * Tạo user session record
     */
    protected function createUserSession(User $user, Request $request, string $sessionToken, array $deviceInfo): UserSession
    {
        return UserSession::create([
            'user_id' => $user->id,
            'session_token' => $sessionToken,
            'device_name' => $deviceInfo['device_name'],
            'device_type' => $deviceInfo['device_type'],
            'device_id' => $deviceInfo['device_id'],
            'ip_address' => $deviceInfo['ip_address'],
            'user_agent' => $deviceInfo['user_agent'],
            'browser' => $deviceInfo['browser'],
            'platform' => $deviceInfo['platform'],
            'location' => $this->getLocationFromIp($deviceInfo['ip_address']),
            'is_active' => true,
            'last_activity' => now(),
            'login_at' => now(),
            'expires_at' => now()->addMinutes(config('session.lifetime', 120)),
            'metadata' => [
                'login_method' => 'web',
                'timezone' => $request->header('timezone', 'UTC')
            ]
        ]);
    }

    /**
     * Lấy vị trí từ IP (có thể tích hợp với service bên ngoài)
     */
    protected function getLocationFromIp(string $ip): ?string
    {
        // Có thể tích hợp với MaxMind GeoIP hoặc service khác
        // Hiện tại return null
        return null;
    }

    /**
     * Áp dụng giới hạn số thiết bị
     */
    protected function enforceDeviceLimit(User $user): void
    {
        $maxDevices = config('auth.max_devices_per_user', 5);
        
        $activeSessions = UserSession::where('user_id', $user->id)
                                    ->active()
                                    ->orderBy('last_activity', 'desc')
                                    ->get();
        
        if ($activeSessions->count() > $maxDevices) {
            // Đăng xuất các thiết bị cũ nhất
            $sessionsToDeactivate = $activeSessions->skip($maxDevices);
            
            foreach ($sessionsToDeactivate as $session) {
                $session->deactivate();
            }
        }
    }

    /**
     * Dọn dẹp session hết hạn
     */
    public function cleanupExpiredSessions(): int
    {
        return UserSession::expired()->delete();
    }

    /**
     * Kiểm tra session có hợp lệ không
     */
    public function validateSession(Request $request): bool
    {
        $user = Auth::user();
        $sessionToken = session('device_session_token');

        if (!$user || !$sessionToken) {
            return false;
        }

        $session = UserSession::where('user_id', $user->id)
                             ->where('session_token', $sessionToken)
                             ->first();

        return $session && $session->isValid();
    }

    /**
     * Lấy thống kê thiết bị
     */
    public function getDeviceStatistics(User $user): array
    {
        $sessions = UserSession::where('user_id', $user->id)->get();

        return [
            'total_sessions' => $sessions->count(),
            'active_sessions' => $sessions->where('is_active', true)->count(),
            'device_types' => $sessions->groupBy('device_type')->map->count(),
            'recent_logins' => $sessions->where('login_at', '>=', now()->subDays(7))->count(),
            'unique_ips' => $sessions->pluck('ip_address')->unique()->count()
        ];
    }
}
