<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\AuditLog;

class PermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission, string $scope = 'any'): Response
    {
        if (!auth()->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        $user = auth()->user();

        // Super admin bypass
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Check if user has the required permission
        if (!$user->hasPermission($permission)) {
            // Log unauthorized access attempt
            AuditLog::createLog([
                'event' => 'unauthorized_access',
                'user_id' => $user->id,
                'metadata' => [
                    'required_permission' => $permission,
                    'required_scope' => $scope,
                    'route' => $request->route()?->getName(),
                    'url' => $request->url(),
                    'method' => $request->method(),
                ],
                'module' => $this->getModuleFromPermission($permission),
                'severity' => 'high',
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Insufficient permissions',
                'required_permission' => $permission,
            ], 403);
        }

        // Additional scope checking if needed
        if ($scope !== 'any' && !$this->checkScope($user, $permission, $scope, $request)) {
            AuditLog::createLog([
                'event' => 'scope_violation',
                'user_id' => $user->id,
                'metadata' => [
                    'permission' => $permission,
                    'required_scope' => $scope,
                    'route' => $request->route()?->getName(),
                ],
                'module' => $this->getModuleFromPermission($permission),
                'severity' => 'medium',
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Access denied for this scope',
                'required_scope' => $scope,
            ], 403);
        }

        return $next($request);
    }

    /**
     * Check scope-based permissions
     */
    private function checkScope($user, string $permission, string $scope, Request $request): bool
    {
        switch ($scope) {
            case 'own':
                return $this->checkOwnScope($user, $request);
            case 'department':
                return $this->checkDepartmentScope($user, $request);
            case 'organization':
                return $this->checkOrganizationScope($user, $request);
            case 'all':
                return true; // Already checked permission existence
            default:
                return false;
        }
    }

    /**
     * Check if user can access own resources
     */
    private function checkOwnScope($user, Request $request): bool
    {
        // Get resource ID from route parameters
        $resourceId = $request->route('id') ?? $request->route('user') ?? $request->route('userId');
        
        if (!$resourceId) {
            return true; // No specific resource, allow access
        }

        // For user resources, check if it's the same user
        if ($request->route()->hasParameter('user') || $request->route()->hasParameter('userId')) {
            return (int) $resourceId === $user->id;
        }

        // For other resources, you might need to check ownership differently
        // This would depend on your specific business logic
        return true;
    }

    /**
     * Check if user can access department resources
     */
    private function checkDepartmentScope($user, Request $request): bool
    {
        if (!$user->department_id) {
            return false;
        }

        // Get resource from route or check if target user is in same department
        $targetUserId = $request->route('user') ?? $request->route('userId');
        
        if ($targetUserId) {
            $targetUser = \App\Models\User::find($targetUserId);
            return $targetUser && $targetUser->department_id === $user->department_id;
        }

        return true; // Allow if no specific target
    }

    /**
     * Check if user can access organization resources
     */
    private function checkOrganizationScope($user, Request $request): bool
    {
        if (!$user->organization_id) {
            return false;
        }

        // Get resource from route or check if target user is in same organization
        $targetUserId = $request->route('user') ?? $request->route('userId');
        
        if ($targetUserId) {
            $targetUser = \App\Models\User::find($targetUserId);
            return $targetUser && $targetUser->organization_id === $user->organization_id;
        }

        return true; // Allow if no specific target
    }

    /**
     * Extract module from permission string
     */
    private function getModuleFromPermission(string $permission): string
    {
        $parts = explode('.', $permission);
        return $parts[0] ?? 'unknown';
    }
}
