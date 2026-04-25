<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ErpWorkflow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ErpWorkflowController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $workflows = ErpWorkflow::query()
            ->with('steps')
            ->when(
                $request->filled('erp_workflow_is_active'),
                fn ($query) => $query->where('erp_workflow_is_active', $request->boolean('erp_workflow_is_active'))
            )
            ->orderBy('erp_workflow_name')
            ->get();

        return $this->successResponse($workflows, 'ERP workflows retrieved successfully');
    }

    public function show(ErpWorkflow $workflow): JsonResponse
    {
        return $this->successResponse($workflow->load('steps'), 'ERP workflow retrieved successfully');
    }
}
