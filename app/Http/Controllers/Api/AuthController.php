<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    /**
     * Login user and create token
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:6',
            'device_name' => 'nullable|string|max:255',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            // Log failed login attempt
            AuditLog::logAuthEvent('login_failed', $user, [
                'email' => $request->email,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Check if user is active
        if (!$user->is_active) {
            AuditLog::logAuthEvent('login_failed_inactive', $user, [
                'reason' => 'User account is inactive',
            ]);

            throw ValidationException::withMessages([
                'email' => ['Your account has been deactivated.'],
            ]);
        }

        // Check if user is verified
        if (!$user->is_verified) {
            AuditLog::logAuthEvent('login_failed_unverified', $user, [
                'reason' => 'User account is not verified',
            ]);

            throw ValidationException::withMessages([
                'email' => ['Please verify your email address before logging in.'],
            ]);
        }

        // Check if password change is required
        if ($user->force_password_change) {
            return response()->json([
                'success' => false,
                'message' => 'Password change required',
                'requires_password_change' => true,
                'user_id' => $user->id,
            ], 200);
        }

        // Create token
        $deviceName = $request->device_name ?? $request->userAgent();
        $token = $user->createToken($deviceName, ['*'], now()->addDays(30));

        // Update last login
        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        // Log successful login
        AuditLog::logAuthEvent('login', $user, [
            'device_name' => $deviceName,
            'token_id' => $token->accessToken->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'user' => $this->formatUserData($user),
                'token' => $token->plainTextToken,
                'expires_at' => $token->accessToken->expires_at,
            ],
        ]);
    }

    /**
     * Logout user and revoke token
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        $token = $request->user()->currentAccessToken();

        // Log logout
        AuditLog::logAuthEvent('logout', $user, [
            'token_id' => $token?->id,
        ]);

        // Revoke current token
        $token?->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout successful',
        ]);
    }

    /**
     * Logout from all devices
     */
    public function logoutAll(Request $request): JsonResponse
    {
        $user = $request->user();

        // Log logout from all devices
        AuditLog::logAuthEvent('logout_all', $user);

        // Revoke all tokens
        $user->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out from all devices',
        ]);
    }

    /**
     * Get authenticated user
     */
    public function user(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->load(['organization', 'department', 'manager', 'activeRoles.permissions']);

        return response()->json([
            'success' => true,
            'data' => $this->formatUserData($user),
        ]);
    }

    /**
     * Refresh token
     */
    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();
        $currentToken = $request->user()->currentAccessToken();

        // Create new token
        $deviceName = $currentToken->name ?? $request->userAgent();
        $newToken = $user->createToken($deviceName, ['*'], now()->addDays(30));

        // Revoke old token
        $currentToken->delete();

        // Log token refresh
        AuditLog::logAuthEvent('token_refresh', $user, [
            'old_token_id' => $currentToken->id,
            'new_token_id' => $newToken->accessToken->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Token refreshed',
            'data' => [
                'token' => $newToken->plainTextToken,
                'expires_at' => $newToken->accessToken->expires_at,
            ],
        ]);
    }

    /**
     * Change password
     */
    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        $user = $request->user();

        // Verify current password
        if (!Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The current password is incorrect.'],
            ]);
        }

        // Update password
        $user->update([
            'password' => Hash::make($request->new_password),
            'password_changed_at' => now(),
            'force_password_change' => false,
        ]);

        // Log password change
        AuditLog::logAuthEvent('password_changed', $user);

        // Revoke all other tokens for security
        $currentToken = $request->user()->currentAccessToken();
        $user->tokens()->where('id', '!=', $currentToken->id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully',
        ]);
    }

    /**
     * Get user's active sessions
     */
    public function sessions(Request $request): JsonResponse
    {
        $user = $request->user();
        $currentTokenId = $request->user()->currentAccessToken()->id;

        $sessions = $user->tokens()
            ->where('expires_at', '>', now())
            ->orderBy('last_used_at', 'desc')
            ->get()
            ->map(function ($token) use ($currentTokenId) {
                return [
                    'id' => $token->id,
                    'name' => $token->name,
                    'last_used_at' => $token->last_used_at,
                    'created_at' => $token->created_at,
                    'expires_at' => $token->expires_at,
                    'is_current' => $token->id === $currentTokenId,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $sessions,
        ]);
    }

    /**
     * Revoke specific session
     */
    public function revokeSession(Request $request, int $tokenId): JsonResponse
    {
        $user = $request->user();
        $currentTokenId = $request->user()->currentAccessToken()->id;

        if ($tokenId === $currentTokenId) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot revoke current session',
            ], 400);
        }

        $token = $user->tokens()->find($tokenId);

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Session not found',
            ], 404);
        }

        // Log session revocation
        AuditLog::logAuthEvent('session_revoked', $user, [
            'revoked_token_id' => $tokenId,
        ]);

        $token->delete();

        return response()->json([
            'success' => true,
            'message' => 'Session revoked successfully',
        ]);
    }

    /**
     * Format user data for API response
     */
    private function formatUserData(User $user): array
    {
        return [
            'id' => $user->id,
            'uuid' => $user->uuid,
            'username' => $user->username,
            'email' => $user->email,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'full_name' => $user->full_name,
            'phone' => $user->phone,
            'avatar' => $user->avatar,
            'position' => $user->position,
            'employee_id' => $user->employee_id,
            'is_active' => $user->is_active,
            'is_verified' => $user->is_verified,
            'timezone' => $user->timezone,
            'locale' => $user->locale,
            'last_login_at' => $user->last_login_at,
            'organization' => $user->organization ? [
                'id' => $user->organization->id,
                'name' => $user->organization->name,
                'code' => $user->organization->code,
            ] : null,
            'department' => $user->department ? [
                'id' => $user->department->id,
                'name' => $user->department->name,
                'code' => $user->department->code,
            ] : null,
            'manager' => $user->manager ? [
                'id' => $user->manager->id,
                'name' => $user->manager->full_name,
                'email' => $user->manager->email,
            ] : null,
            'roles' => $user->activeRoles->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'slug' => $role->slug,
                    'display_name' => $role->display_name,
                    'module' => $role->module,
                    'level' => $role->level,
                ];
            }),
            'permissions' => $user->getAllPermissions()->map(function ($permission) {
                return [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'slug' => $permission->slug,
                    'module' => $permission->module,
                    'resource' => $permission->resource,
                    'action' => $permission->action,
                    'scope' => $permission->scope,
                ];
            }),
        ];
    }
}
