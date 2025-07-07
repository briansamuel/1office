<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\AuditLog;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
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

        // Check if user has any of the required roles
        if (!$user->hasAnyRole($roles)) {
            // Log unauthorized access attempt
            AuditLog::createLog([
                'event' => 'role_access_denied',
                'user_id' => $user->id,
                'metadata' => [
                    'required_roles' => $roles,
                    'user_roles' => $user->activeRoles->pluck('slug')->toArray(),
                    'route' => $request->route()?->getName(),
                    'url' => $request->url(),
                    'method' => $request->method(),
                ],
                'module' => 'auth',
                'severity' => 'high',
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Insufficient role permissions',
                'required_roles' => $roles,
            ], 403);
        }

        return $next($request);
    }
}
