<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

if (! function_exists('currentWorkspaceUserId')) {
    /**
     * Retrieve the authenticated workspace user ID from the current request.
     *
     * The value is injected into request attributes by EnsureWorkspaceAuthenticated middleware.
     */
    function currentWorkspaceUserId(?Request $request = null): ?int
    {
        $request ??= request();

        $value = $request->attributes->get('workspace_user_id');

        return $value !== null ? (int) $value : null;
    }
}

if (! function_exists('currentWorkspaceUserRoleSlug')) {
    /**
     * Retrieve the ERP application role slug for the authenticated workspace user.
     */
    function currentWorkspaceUserRoleSlug(?Request $request = null): ?string
    {
        $userId = currentWorkspaceUserId($request);

        if (! $userId) {
            return null;
        }

        return DB::connection('workspace')
            ->table('workspace_user_applications as wua')
            ->join('workspace_application_roles as war', 'war.workspace_application_role_id', '=', 'wua.workspace_user_application_workspace_application_role_id')
            ->join('workspace_applications as wa', 'wa.workspace_application_id', '=', 'wua.workspace_user_application_workspace_application_id')
            ->where('wua.workspace_user_application_workspace_user_id', $userId)
            ->where('wa.workspace_application_slug', 'ucebul-erp')
            ->where('wua.workspace_user_application_is_active', true)
            ->value('war.workspace_application_role_slug');
    }
}
