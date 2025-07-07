<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DeviceAuthenticationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class DeviceManagementController extends Controller
{
    protected DeviceAuthenticationService $deviceAuthService;

    public function __construct(DeviceAuthenticationService $deviceAuthService)
    {
        $this->deviceAuthService = $deviceAuthService;
        $this->middleware('auth:sanctum');
    }

    /**
     * Lấy danh sách thiết bị đang hoạt động
     */
    public function getActiveDevices(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $devices = $this->deviceAuthService->getActiveDevices($user);

            return response()->json([
                'success' => true,
                'message' => 'Lấy danh sách thiết bị thành công',
                'data' => [
                    'devices' => $devices,
                    'total' => count($devices)
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Đăng xuất từ thiết bị cụ thể
     */
    public function logoutDevice(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'session_token' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Session token không hợp lệ',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = $request->user();
            $result = $this->deviceAuthService->logoutFromDevice($user, $request->session_token);

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Đăng xuất từ tất cả thiết bị khác
     */
    public function logoutOtherDevices(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $currentSessionToken = session('device_session_token');
            
            $result = $this->deviceAuthService->logoutFromOtherDevices($user, $currentSessionToken);

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Đăng xuất từ tất cả thiết bị
     */
    public function logoutAllDevices(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $result = $this->deviceAuthService->logoutFromAllDevices($user);

            // Xóa tất cả tokens Sanctum
            $user->tokens()->delete();

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => [
                    'affected_devices' => $result['affected_devices'],
                    'redirect_to_login' => true
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Lấy thống kê thiết bị
     */
    public function getStatistics(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $statistics = $this->deviceAuthService->getDeviceStatistics($user);

            return response()->json([
                'success' => true,
                'message' => 'Lấy thống kê thành công',
                'data' => $statistics
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Cập nhật hoạt động session
     */
    public function updateActivity(Request $request): JsonResponse
    {
        try {
            $this->deviceAuthService->updateActivity($request);

            return response()->json([
                'success' => true,
                'message' => 'Hoạt động đã được cập nhật',
                'data' => [
                    'last_activity' => now()->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Kiểm tra trạng thái session
     */
    public function checkSession(Request $request): JsonResponse
    {
        try {
            $isValid = $this->deviceAuthService->validateSession($request);

            return response()->json([
                'success' => true,
                'data' => [
                    'is_valid' => $isValid,
                    'message' => $isValid ? 'Session hợp lệ' : 'Session không hợp lệ',
                    'current_time' => now()->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Làm mới session
     */
    public function refreshSession(Request $request): JsonResponse
    {
        try {
            if (!$this->deviceAuthService->validateSession($request)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Session không hợp lệ'
                ], 401);
            }

            $this->deviceAuthService->updateActivity($request);

            return response()->json([
                'success' => true,
                'message' => 'Session đã được làm mới',
                'data' => [
                    'refreshed_at' => now()->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Lấy thông tin thiết bị hiện tại
     */
    public function getCurrentDevice(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $devices = $this->deviceAuthService->getActiveDevices($user);
            $currentSessionToken = session('device_session_token');
            
            $currentDevice = collect($devices)->firstWhere('is_current', true);

            if (!$currentDevice) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy thiết bị hiện tại'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Lấy thông tin thiết bị hiện tại thành công',
                'data' => $currentDevice
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Đặt tên cho thiết bị
     */
    public function setDeviceName(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'device_name' => 'required|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Tên thiết bị không hợp lệ',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = $request->user();
            $sessionToken = session('device_session_token');
            
            if (!$sessionToken) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy session hiện tại'
                ], 404);
            }

            $session = \App\Models\UserSession::where('user_id', $user->id)
                                             ->where('session_token', $sessionToken)
                                             ->first();

            if (!$session) {
                return response()->json([
                    'success' => false,
                    'message' => 'Không tìm thấy session'
                ], 404);
            }

            $session->update([
                'device_name' => $request->device_name
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Đã cập nhật tên thiết bị',
                'data' => [
                    'device_name' => $request->device_name,
                    'updated_at' => $session->updated_at->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}
