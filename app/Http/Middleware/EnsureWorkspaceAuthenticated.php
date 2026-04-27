<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class EnsureWorkspaceAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (empty($token)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
                'errors' => [],
            ], Response::HTTP_UNAUTHORIZED);
        }

        $apiUrl = config('services.workspace.api_url');
        $appSlug = config('services.workspace.application_slug', 'erp');

        try {
            $response = Http::timeout(10)
                ->connectTimeout(5)
                ->withToken($token)
                ->get("{$apiUrl}/auth/me", ['application_slug' => $appSlug]);
        } catch (\Throwable) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication service unavailable',
                'errors' => [],
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        if ($response->status() === Response::HTTP_UNAUTHORIZED) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
                'errors' => [],
            ], Response::HTTP_UNAUTHORIZED);
        }

        if ($response->status() === Response::HTTP_FORBIDDEN) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have access to this application',
                'errors' => [],
            ], Response::HTTP_FORBIDDEN);
        }

        if (! $response->successful()) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication service unavailable',
                'errors' => [],
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        $body = $response->json();

        // Workspace wraps responses in { success, message, data } — the user IS the data object
        $workspaceUser = isset($body['data']) && is_array($body['data']) ? $body['data'] : $body;

        // Primary key in Workspace is workspace_user_id
        $workspaceUserId = $workspaceUser['workspace_user_id'] ?? $workspaceUser['id'] ?? null;

        if ($workspaceUserId === null) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
                'errors' => [],
            ], Response::HTTP_UNAUTHORIZED);
        }

        $request->attributes->set('workspace_user_id', (int) $workspaceUserId);
        $request->attributes->set('workspace_user', $workspaceUser);
        $request->attributes->set('workspace_roles', $workspaceUser['roles'] ?? []);
        $request->attributes->set('current_application', $workspaceUser['current_application'] ?? null);
        $request->attributes->set('current_application_access', $workspaceUser['current_application_access'] ?? null);
        $request->attributes->set('current_application_permissions', $workspaceUser['current_application_permissions'] ?? []);

        return $next($request);
    }
}
