<?php

use Illuminate\Http\Request;

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
