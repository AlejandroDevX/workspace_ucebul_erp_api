<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ErpApproval;
use App\Models\ErpProcess;
use App\Services\ErpProcessEventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

class ErpApprovalController extends Controller
{
    public function __construct(
        protected ErpProcessEventService $processEventService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $approvals = ErpApproval::query()
            ->with(['process', 'step'])
            ->when(
                $request->filled('erp_approval_process_id'),
                fn ($query) => $query->where('erp_approval_process_id', $request->integer('erp_approval_process_id'))
            )
            ->when(
                $request->filled('erp_approval_status'),
                fn ($query) => $query->where('erp_approval_status', $request->string('erp_approval_status'))
            )
            ->orderByDesc('erp_approval_id')
            ->get();

        return $this->successResponse($approvals, 'ERP approvals retrieved successfully');
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'erp_approval_process_id' => ['required', 'integer', 'exists:erp_processes,erp_process_id'],
            'erp_approval_step_id' => ['nullable', 'integer', 'exists:erp_workflow_steps,erp_workflow_step_id'],
            'erp_approval_reference_type' => ['nullable', 'string', 'max:80'],
            'erp_approval_reference_id' => ['nullable', 'integer', 'min:1'],
            'erp_approval_status' => ['nullable', 'in:pending,approved,rejected'],
            'erp_approval_comment' => ['nullable', 'string'],
            'erp_approval_requested_at' => ['nullable', 'date'],
        ]);

        $validated['erp_approval_requested_by_workspace_user_id'] = currentWorkspaceUserId();
        $validated['erp_approval_requested_at'] = $validated['erp_approval_requested_at'] ?? Carbon::now();

        $approval = ErpApproval::query()->create($validated)->load(['process', 'step']);

        return $this->successResponse($approval, 'ERP approval created successfully', Response::HTTP_CREATED);
    }

    public function approve(Request $request, ErpApproval $approval): JsonResponse
    {
        return $this->resolveApproval($request, $approval, 'approved');
    }

    public function reject(Request $request, ErpApproval $approval): JsonResponse
    {
        return $this->resolveApproval($request, $approval, 'rejected');
    }

    protected function resolveApproval(Request $request, ErpApproval $approval, string $status): JsonResponse
    {
        $validated = $request->validate([
            'erp_approval_comment' => ['nullable', 'string'],
        ]);

        $approval->update([
            'erp_approval_status' => $status,
            'erp_approval_comment' => $validated['erp_approval_comment'] ?? $approval->erp_approval_comment,
            'erp_approval_approved_by_workspace_user_id' => currentWorkspaceUserId(),
            'erp_approval_resolved_at' => Carbon::now(),
        ]);

        $approval->load(['process', 'step']);

        if ($approval->process instanceof ErpProcess) {
            $approval->process->update([
                'erp_process_status' => $status === 'approved' ? 'approved' : 'rejected',
            ]);

            $this->processEventService->logApprovalResolved(
                process: $approval->process->fresh(),
                status: $status,
                workspaceUserId: $approval->erp_approval_approved_by_workspace_user_id,
                comment: $approval->erp_approval_comment,
            );
        }

        return $this->successResponse($approval, "ERP approval {$status} successfully");
    }
}
