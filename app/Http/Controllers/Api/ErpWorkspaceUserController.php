<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ErpWorkspaceUserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'role' => ['nullable', 'string', 'max:120'],
        ]);

        $query = DB::connection('workspace')
            ->table('workspace_user_applications as wua')
            ->join('workspace_users as wu', 'wu.workspace_user_id', '=', 'wua.workspace_user_application_workspace_user_id')
            ->join('workspace_applications as wa', 'wa.workspace_application_id', '=', 'wua.workspace_user_application_workspace_application_id')
            ->join('workspace_application_roles as war', 'war.workspace_application_role_id', '=', 'wua.workspace_user_application_workspace_application_role_id')
            ->where('wa.workspace_application_slug', 'ucebul-erp')
            ->where('wua.workspace_user_application_is_active', true)
            ->select([
                'wu.workspace_user_id',
                'wu.workspace_user_name',
                'wu.workspace_user_last_name',
                'wu.workspace_user_email',
                'war.workspace_application_role_name',
                'war.workspace_application_role_slug',
            ]);

        if ($request->filled('role')) {
            $query->where('war.workspace_application_role_slug', $request->string('role'));
        }

        $users = $query->orderBy('wu.workspace_user_name')->get();

        return $this->successResponse($users, 'Workspace users retrieved successfully');
    }
}
