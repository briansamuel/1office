<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\DeviceAuthenticationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class DeviceAuthController extends Controller
{
    protected DeviceAuthenticationService $deviceAuthService;

    public function __construct(DeviceAuthenticationService $deviceAuthService)
    {
        $this->deviceAuthService = $deviceAuthService;
    }

    /**
     * Hiển thị trang quản lý thiết bị
     */
    public function index(): View
    {
        $user = Auth::user();
        $devices = $this->deviceAuthService->getActiveDevices($user);
        $statistics = $this->deviceAuthService->getDeviceStatistics($user);
        
        return view('auth.devices.index', compact('devices', 'statistics'));
    }

    /**
     * Đăng nhập với thông tin thiết bị
     */
    public function login(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'remember' => 'boolean'
        ]);

        $credentials = $request->only('email', 'password');
        $remember = $request->boolean('remember');

        $result = $this->deviceAuthService->login($request, $credentials, $remember);

        if ($request->expectsJson()) {
            return response()->json($result);
        }

        if ($result['success']) {
            return redirect()->intended('/dashboard')->with('success', $result['message']);
        }

        return back()->withErrors(['email' => $result['message']])->withInput();
    }

    /**
     * Đăng xuất từ thiết bị hiện tại
     */
    public function logout(Request $request): JsonResponse|RedirectResponse
    {
        $result = $this->deviceAuthService->logout($request);

        if ($request->expectsJson()) {
            return response()->json($result);
        }

        return redirect('/login')->with('success', $result['message']);
    }

    /**
     * Đăng xuất từ thiết bị cụ thể
     */
    public function logoutDevice(Request $request): JsonResponse
    {
        $request->validate([
            'session_token' => 'required|string'
        ]);

        $user = Auth::user();
        $result = $this->deviceAuthService->logoutFromDevice($user, $request->session_token);

        return response()->json($result);
    }

    /**
     * Đăng xuất từ tất cả thiết bị khác
     */
    public function logoutOtherDevices(Request $request): JsonResponse
    {
        $user = Auth::user();
        $currentSessionToken = session('device_session_token');
        
        $result = $this->deviceAuthService->logoutFromOtherDevices($user, $currentSessionToken);

        return response()->json($result);
    }

    /**
     * Đăng xuất từ tất cả thiết bị
     */
    public function logoutAllDevices(Request $request): JsonResponse
    {
        $user = Auth::user();
        $result = $this->deviceAuthService->logoutFromAllDevices($user);

        // Đăng xuất user hiện tại
        Auth::logout();
        session()->forget('device_session_token');

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'redirect' => '/login'
        ]);
    }

    /**
     * Lấy danh sách thiết bị đang hoạt động
     */
    public function getActiveDevices(): JsonResponse
    {
        $user = Auth::user();
        $devices = $this->deviceAuthService->getActiveDevices($user);

        return response()->json([
            'success' => true,
            'devices' => $devices
        ]);
    }

    /**
     * Cập nhật hoạt động session
     */
    public function updateActivity(Request $request): JsonResponse
    {
        $this->deviceAuthService->updateActivity($request);

        return response()->json([
            'success' => true,
            'message' => 'Hoạt động đã được cập nhật'
        ]);
    }

    /**
     * Lấy thống kê thiết bị
     */
    public function getStatistics(): JsonResponse
    {
        $user = Auth::user();
        $statistics = $this->deviceAuthService->getDeviceStatistics($user);

        return response()->json([
            'success' => true,
            'statistics' => $statistics
        ]);
    }

    /**
     * Kiểm tra trạng thái session
     */
    public function checkSession(Request $request): JsonResponse
    {
        $isValid = $this->deviceAuthService->validateSession($request);

        return response()->json([
            'success' => true,
            'is_valid' => $isValid,
            'message' => $isValid ? 'Session hợp lệ' : 'Session không hợp lệ'
        ]);
    }

    /**
     * Làm mới session
     */
    public function refreshSession(Request $request): JsonResponse
    {
        if (!$this->deviceAuthService->validateSession($request)) {
            return response()->json([
                'success' => false,
                'message' => 'Session không hợp lệ'
            ], 401);
        }

        $this->deviceAuthService->updateActivity($request);

        return response()->json([
            'success' => true,
            'message' => 'Session đã được làm mới'
        ]);
    }
}
