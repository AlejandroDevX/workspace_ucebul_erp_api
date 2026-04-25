<?php

use Illuminate\Http\Request;

if (! function_exists('currentWorkspaceUserId')) {
    function currentWorkspaceUserId(?Request $request = null, ?array $payload = null): ?int
    {
        $request ??= request();
        $payload ??= $request->all();

        $headerValue = $request->header('X-Workspace-User-Id');

        if ($headerValue !== null && $headerValue !== '') {
            return (int) $headerValue;
        }

        $candidateKeys = [
            'workspace_user_id',
            'created_by_workspace_user_id',
            'assigned_to_workspace_user_id',
            'approved_by_workspace_user_id',
            'uploaded_by_workspace_user_id',
        ];

        foreach ($candidateKeys as $candidateKey) {
            if (array_key_exists($candidateKey, $payload) && $payload[$candidateKey] !== null && $payload[$candidateKey] !== '') {
                return (int) $payload[$candidateKey];
            }
        }

        return null;
    }
}
