<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DeviceAuthenticationService;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Carbon\Carbon;

class DeviceAuthController extends Controller
{
    protected DeviceAuthenticationService $deviceAuthService;

    public function __construct(DeviceAuthenticationService $deviceAuthService)
    {
        $this->deviceAuthService = $deviceAuthService;
    }

    /**
     * Đăng ký tài khoản mới
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|min:3|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20',
            'device_name' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Tạo user mới
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone' => $request->phone,
                'email_verified_at' => now(),
            ]);

            // Đăng nhập tự động sau khi đăng ký
            Auth::login($user);

            // Tạo session với device tracking
            $loginResult = $this->deviceAuthService->login($request, [
                'email' => $request->email,
                'password' => $request->password
            ]);

            // Tạo Sanctum token
            $token = $user->createToken($request->device_name ?? 'API Token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Đăng ký thành công',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'created_at' => $user->created_at->toISOString()
                    ],
                    'token' => $token,
                    'device_info' => $loginResult['device_info'] ?? null,
                    'expires_at' => now()->addMinutes(config('sanctum.expiration', 525600))->toISOString()
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi đăng ký',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Đăng nhập với device tracking
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
            'remember' => 'boolean',
            'device_name' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $credentials = $request->only('email', 'password');
            $remember = $request->boolean('remember');

            // Thử đăng nhập với device tracking
            $loginResult = $this->deviceAuthService->login($request, $credentials, $remember);

            if (!$loginResult['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $loginResult['message']
                ], 401);
            }

            $user = Auth::user();

            // Tạo Sanctum token
            $token = $user->createToken($request->device_name ?? 'API Token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Đăng nhập thành công',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'email_verified_at' => $user->email_verified_at?->toISOString(),
                        'created_at' => $user->created_at->toISOString()
                    ],
                    'token' => $token,
                    'device_info' => $loginResult['device_info'],
                    'session' => [
                        'id' => $loginResult['session']->id,
                        'device_type' => $loginResult['session']->device_type,
                        'ip_address' => $loginResult['session']->ip_address,
                        'login_at' => $loginResult['session']->login_at->toISOString(),
                        'expires_at' => $loginResult['session']->expires_at->toISOString()
                    ],
                    'expires_at' => now()->addMinutes(config('sanctum.expiration', 525600))->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi đăng nhập',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Đăng xuất
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $allDevices = $request->boolean('all_devices');

            if ($allDevices) {
                // Đăng xuất tất cả thiết bị
                $result = $this->deviceAuthService->logoutFromAllDevices($user);
                
                // Xóa tất cả tokens
                $user->tokens()->delete();
            } else {
                // Đăng xuất thiết bị hiện tại
                $result = $this->deviceAuthService->logout($request);
                
                // Xóa token hiện tại
                $request->user()->currentAccessToken()->delete();
            }

            return response()->json([
                'success' => true,
                'message' => $allDevices ? 'Đã đăng xuất khỏi tất cả thiết bị' : 'Đăng xuất thành công'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi đăng xuất',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Lấy thông tin user hiện tại
     */
    public function me(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            return response()->json([
                'success' => true,
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'email_verified_at' => $user->email_verified_at?->toISOString(),
                        'created_at' => $user->created_at->toISOString(),
                        'updated_at' => $user->updated_at->toISOString()
                    ],
                    'current_session' => session('device_session_token'),
                    'device_statistics' => $this->deviceAuthService->getDeviceStatistics($user)
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
     * Làm mới token
     */
    public function refresh(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            
            // Xóa token cũ
            $request->user()->currentAccessToken()->delete();
            
            // Tạo token mới
            $newToken = $user->createToken($request->device_name ?? 'API Token')->plainTextToken;
            
            // Cập nhật hoạt động session
            $this->deviceAuthService->updateActivity($request);

            return response()->json([
                'success' => true,
                'message' => 'Token đã được làm mới',
                'data' => [
                    'token' => $newToken,
                    'expires_at' => now()->addMinutes(config('sanctum.expiration', 525600))->toISOString()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi làm mới token',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Quên mật khẩu
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Email không hợp lệ',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $status = Password::sendResetLink($request->only('email'));

            if ($status === Password::RESET_LINK_SENT) {
                return response()->json([
                    'success' => true,
                    'message' => 'Link reset mật khẩu đã được gửi đến email của bạn'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Không thể gửi link reset mật khẩu'
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Reset mật khẩu
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $status = Password::reset(
                $request->only('email', 'password', 'password_confirmation', 'token'),
                function ($user, $password) {
                    $user->forceFill([
                        'password' => Hash::make($password)
                    ])->setRememberToken(Str::random(60));

                    $user->save();
                }
            );

            if ($status === Password::PASSWORD_RESET) {
                return response()->json([
                    'success' => true,
                    'message' => 'Mật khẩu đã được reset thành công'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Token reset không hợp lệ hoặc đã hết hạn'
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}
