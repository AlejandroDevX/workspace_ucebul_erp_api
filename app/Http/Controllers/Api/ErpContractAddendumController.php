<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ErpContractAddendum;
use App\Models\ErpProcess;
use App\Services\ErpNumberSequenceService;
use App\Services\ErpProcessEventService;
use App\Services\ErpProcessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class ErpContractAddendumController extends Controller
{
    public function __construct(
        protected ErpNumberSequenceService $numberSequenceService,
        protected ErpProcessService $processService,
        protected ErpProcessEventService $processEventService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $contractAddendums = ErpContractAddendum::query()
            ->with(['contract', 'process.workflow', 'process.currentStep'])
            ->when(
                $request->filled('erp_contract_addendum_status'),
                fn ($query) => $query->where('erp_contract_addendum_status', $request->string('erp_contract_addendum_status'))
            )
            ->when(
                $request->filled('erp_contract_addendum_contract_id'),
                fn ($query) => $query->where('erp_contract_addendum_contract_id', $request->integer('erp_contract_addendum_contract_id'))
            )
            ->orderByDesc('erp_contract_addendum_id')
            ->get();

        return $this->successResponse($contractAddendums, 'ERP contract addendums retrieved successfully');
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'erp_contract_addendum_contract_id' => ['required', 'integer', 'exists:erp_contracts,erp_contract_id'],
            'erp_contract_addendum_type' => ['nullable', 'in:date,position,functions,value,payment_method,supervisor,other'],
            'erp_contract_addendum_title' => ['required', 'string', 'max:180'],
            'erp_contract_addendum_description' => ['required', 'string'],
            'erp_contract_addendum_previous_value' => ['nullable', 'string'],
            'erp_contract_addendum_new_value' => ['nullable', 'string'],
            'erp_contract_addendum_effective_date' => ['nullable', 'date'],
            'erp_contract_addendum_status' => ['nullable', 'in:draft,supports_uploaded,legal_review,pending_approval,approved,signed,closed,cancelled'],
            'erp_contract_addendum_created_by_workspace_user_id' => ['nullable', 'integer', 'min:1'],
            'erp_process_priority' => ['nullable', 'in:low,medium,high'],
            'erp_process_assigned_to_workspace_user_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $workspaceUserId = $validated['erp_contract_addendum_created_by_workspace_user_id']
            ?? currentWorkspaceUserId($request, $validated);

        $contractAddendum = DB::transaction(function () use ($validated, $workspaceUserId): ErpContractAddendum {
            $process = $this->processService->createProcessForWorkflow('contract_addendums', [
                'erp_process_title' => $validated['erp_contract_addendum_title'],
                'erp_process_description' => $validated['erp_contract_addendum_description'],
                'erp_process_priority' => $validated['erp_process_priority'] ?? 'medium',
                'erp_process_created_by_workspace_user_id' => $workspaceUserId,
                'erp_process_assigned_to_workspace_user_id' => $validated['erp_process_assigned_to_workspace_user_id'] ?? null,
            ]);

            $contractAddendum = ErpContractAddendum::query()->create([
                'erp_contract_addendum_process_id' => $process->erp_process_id,
                'erp_contract_addendum_contract_id' => $validated['erp_contract_addendum_contract_id'],
                'erp_contract_addendum_code' => $this->numberSequenceService->generate('contract_addendum'),
                'erp_contract_addendum_type' => $validated['erp_contract_addendum_type'] ?? 'other',
                'erp_contract_addendum_title' => $validated['erp_contract_addendum_title'],
                'erp_contract_addendum_description' => $validated['erp_contract_addendum_description'],
                'erp_contract_addendum_previous_value' => $validated['erp_contract_addendum_previous_value'] ?? null,
                'erp_contract_addendum_new_value' => $validated['erp_contract_addendum_new_value'] ?? null,
                'erp_contract_addendum_effective_date' => $validated['erp_contract_addendum_effective_date'] ?? null,
                'erp_contract_addendum_status' => $validated['erp_contract_addendum_status'] ?? 'draft',
                'erp_contract_addendum_created_by_workspace_user_id' => $workspaceUserId,
            ]);

            $process->update([
                'erp_process_status' => $this->mapAddendumStatusToProcessStatus($contractAddendum->erp_contract_addendum_status),
            ]);

            $this->processEventService->logCreated(
                process: $process->fresh(),
                title: "Contract addendum {$contractAddendum->erp_contract_addendum_code} created",
                description: $contractAddendum->erp_contract_addendum_title,
                workspaceUserId: $workspaceUserId,
            );

            return $contractAddendum;
        });

        return $this->successResponse(
            $this->loadContractAddendum($contractAddendum),
            'ERP contract addendum created successfully',
            Response::HTTP_CREATED
        );
    }

    public function show(ErpContractAddendum $contractAddendum): JsonResponse
    {
        return $this->successResponse($this->loadContractAddendum($contractAddendum), 'ERP contract addendum retrieved successfully');
    }

    public function update(Request $request, ErpContractAddendum $contractAddendum): JsonResponse
    {
        $validated = $request->validate([
            'erp_contract_addendum_contract_id' => ['sometimes', 'required', 'integer', 'exists:erp_contracts,erp_contract_id'],
            'erp_contract_addendum_type' => ['sometimes', 'nullable', 'in:date,position,functions,value,payment_method,supervisor,other'],
            'erp_contract_addendum_title' => ['sometimes', 'required', 'string', 'max:180'],
            'erp_contract_addendum_description' => ['sometimes', 'required', 'string'],
            'erp_contract_addendum_previous_value' => ['sometimes', 'nullable', 'string'],
            'erp_contract_addendum_new_value' => ['sometimes', 'nullable', 'string'],
            'erp_contract_addendum_effective_date' => ['sometimes', 'nullable', 'date'],
            'erp_contract_addendum_status' => ['sometimes', 'nullable', 'in:draft,supports_uploaded,legal_review,pending_approval,approved,signed,closed,cancelled'],
            'erp_process_assigned_to_workspace_user_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
        ]);

        $workspaceUserId = currentWorkspaceUserId($request, $validated);
        $oldStatus = $contractAddendum->erp_contract_addendum_status;

        DB::transaction(function () use ($validated, $workspaceUserId, $contractAddendum, $oldStatus): void {
            $contractAddendum->update($validated);

            if (array_key_exists('erp_process_assigned_to_workspace_user_id', $validated) && $contractAddendum->process instanceof ErpProcess) {
                $contractAddendum->process->update([
                    'erp_process_assigned_to_workspace_user_id' => $validated['erp_process_assigned_to_workspace_user_id'],
                ]);
            }

            if ($contractAddendum->process instanceof ErpProcess) {
                $this->processEventService->logUpdated(
                    process: $contractAddendum->process,
                    title: "Contract addendum {$contractAddendum->erp_contract_addendum_code} updated",
                    description: $contractAddendum->erp_contract_addendum_title,
                    workspaceUserId: $workspaceUserId,
                );

                if (array_key_exists('erp_contract_addendum_status', $validated) && $validated['erp_contract_addendum_status'] !== $oldStatus) {
                    $newProcessStatus = $this->mapAddendumStatusToProcessStatus($validated['erp_contract_addendum_status']);

                    $contractAddendum->process->update([
                        'erp_process_status' => $newProcessStatus,
                    ]);

                    $this->processEventService->logStatusChanged(
                        process: $contractAddendum->process->fresh(),
                        title: "Contract addendum status changed to {$validated['erp_contract_addendum_status']}",
                        oldStatus: $oldStatus,
                        newStatus: $validated['erp_contract_addendum_status'],
                        workspaceUserId: $workspaceUserId,
                    );
                }
            }
        });

        return $this->successResponse(
            $this->loadContractAddendum($contractAddendum->fresh()),
            'ERP contract addendum updated successfully'
        );
    }

    public function destroy(ErpContractAddendum $contractAddendum): JsonResponse
    {
        DB::transaction(function () use ($contractAddendum): void {
            $contractAddendum->load('process');

            if ($contractAddendum->process instanceof ErpProcess) {
                $contractAddendum->process->delete();

                return;
            }

            $contractAddendum->delete();
        });

        return $this->successResponse(null, 'ERP contract addendum deleted successfully');
    }

    public function moveStep(Request $request, ErpContractAddendum $contractAddendum): JsonResponse
    {
        $validated = $request->validate([
            'erp_workflow_step_slug' => ['required', 'string', 'max:120'],
        ]);

        if (! $contractAddendum->process instanceof ErpProcess) {
            return $this->errorResponse('Contract addendum process not found', [], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $workspaceUserId = currentWorkspaceUserId($request, $validated);

            DB::transaction(function () use ($contractAddendum, $validated, $workspaceUserId): void {
                $this->processService->moveToStep(
                    process: $contractAddendum->process,
                    stepSlug: $validated['erp_workflow_step_slug'],
                    workspaceUserId: $workspaceUserId,
                );

                $newStatus = $this->mapAddendumStepToStatus(
                    $validated['erp_workflow_step_slug'],
                    $contractAddendum->erp_contract_addendum_status
                );

                if ($newStatus !== $contractAddendum->erp_contract_addendum_status) {
                    $oldStatus = $contractAddendum->erp_contract_addendum_status;

                    $contractAddendum->update([
                        'erp_contract_addendum_status' => $newStatus,
                    ]);

                    $this->processEventService->logStatusChanged(
                        process: $contractAddendum->process->fresh(),
                        title: "Contract addendum status changed to {$newStatus}",
                        oldStatus: $oldStatus,
                        newStatus: $newStatus,
                        workspaceUserId: $workspaceUserId,
                    );
                }
            });
        } catch (\RuntimeException $exception) {
            return $this->errorResponse($exception->getMessage(), [], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->successResponse(
            $this->loadContractAddendum($contractAddendum->fresh()),
            'ERP contract addendum step moved successfully'
        );
    }

    public function close(Request $request, ErpContractAddendum $contractAddendum): JsonResponse
    {
        if (! $contractAddendum->process instanceof ErpProcess) {
            return $this->errorResponse('Contract addendum process not found', [], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $workspaceUserId = currentWorkspaceUserId($request, $request->all());

        DB::transaction(function () use ($contractAddendum, $workspaceUserId): void {
            $this->processService->closeProcess($contractAddendum->process, $workspaceUserId);

            if ($contractAddendum->erp_contract_addendum_status !== 'closed') {
                $oldStatus = $contractAddendum->erp_contract_addendum_status;

                $contractAddendum->update([
                    'erp_contract_addendum_status' => 'closed',
                ]);

                $this->processEventService->logStatusChanged(
                    process: $contractAddendum->process->fresh(),
                    title: 'Contract addendum status changed to closed',
                    oldStatus: $oldStatus,
                    newStatus: 'closed',
                    workspaceUserId: $workspaceUserId,
                );
            }
        });

        return $this->successResponse(
            $this->loadContractAddendum($contractAddendum->fresh()),
            'ERP contract addendum closed successfully'
        );
    }

    protected function loadContractAddendum(ErpContractAddendum $contractAddendum): ErpContractAddendum
    {
        return $contractAddendum->load([
            'contract',
            'process.workflow',
            'process.currentStep',
            'process.documents',
            'process.approvals',
            'process.events',
            'process.comments',
        ]);
    }

    protected function mapAddendumStepToStatus(string $stepSlug, string $currentStatus): string
    {
        return match ($stepSlug) {
            'upload_supports' => 'supports_uploaded',
            'legal_preparation' => 'legal_review',
            'approval' => 'pending_approval',
            'signature' => 'signed',
            'closed' => 'closed',
            default => $currentStatus,
        };
    }

    protected function mapAddendumStatusToProcessStatus(string $status): string
    {
        return match ($status) {
            'draft' => 'draft',
            'legal_review' => 'pending_review',
            'pending_approval' => 'pending_approval',
            'approved' => 'approved',
            'signed' => 'signed',
            'closed' => 'closed',
            'cancelled' => 'cancelled',
            default => 'in_progress',
        };
    }
}
