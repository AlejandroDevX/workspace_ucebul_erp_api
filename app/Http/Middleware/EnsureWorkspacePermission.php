<?php

namespace App\Http\Middleware;

use App\Services\WorkspacePermissionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureWorkspacePermission
{
    public function __construct(
        protected WorkspacePermissionService $permissionService,
    ) {}

    public function handle(Request $request, Closure $next, string $permissionSlug): Response
    {
        /** @var array<string, mixed> $permissions */
        $permissions = $request->attributes->get('current_application_permissions', []);

        if (! $this->permissionService->hasPermission($permissions, $permissionSlug)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to perform this action',
                'errors' => [
                    'permission' => ["Required permission: {$permissionSlug}"],
                ],
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
