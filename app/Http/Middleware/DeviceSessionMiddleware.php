<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\DeviceAuthenticationService;
use Illuminate\Support\Facades\Auth;

class DeviceSessionMiddleware
{
    protected DeviceAuthenticationService $deviceAuthService;

    public function __construct(DeviceAuthenticationService $deviceAuthService)
    {
        $this->deviceAuthService = $deviceAuthService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Chỉ kiểm tra cho user đã đăng nhập
        if (Auth::check()) {
            // Kiểm tra session có hợp lệ không
            if (!$this->deviceAuthService->validateSession($request)) {
                // Session không hợp lệ, đăng xuất user
                Auth::logout();
                session()->forget('device_session_token');
                
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Phiên đăng nhập đã hết hạn',
                        'redirect' => '/login'
                    ], 401);
                }
                
                return redirect('/login')->with('error', 'Phiên đăng nhập đã hết hạn');
            }
            
            // Cập nhật hoạt động session
            $this->deviceAuthService->updateActivity($request);
        }

        return $next($request);
    }
}
