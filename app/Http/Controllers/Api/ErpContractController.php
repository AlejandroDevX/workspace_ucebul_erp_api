<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ErpContract;
use App\Models\ErpProcess;
use App\Services\ErpNumberSequenceService;
use App\Services\ErpProcessEventService;
use App\Services\ErpProcessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class ErpContractController extends Controller
{
    public function __construct(
        protected ErpNumberSequenceService $numberSequenceService,
        protected ErpProcessService $processService,
        protected ErpProcessEventService $processEventService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $contracts = ErpContract::query()
            ->with(['company', 'contractor', 'process.workflow', 'process.currentStep'])
            ->when(
                $request->filled('erp_contract_status'),
                fn ($query) => $query->where('erp_contract_status', $request->string('erp_contract_status'))
            )
            ->when(
                $request->filled('erp_contract_contractor_id'),
                fn ($query) => $query->where('erp_contract_contractor_id', $request->integer('erp_contract_contractor_id'))
            )
            ->orderByDesc('erp_contract_id')
            ->get();

        return $this->successResponse($contracts, 'ERP contracts retrieved successfully');
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'erp_contract_company_id' => ['nullable', 'integer', 'exists:erp_companies,erp_company_id'],
            'erp_contract_contractor_id' => ['required', 'integer', 'exists:erp_third_parties,erp_third_party_id'],
            'erp_contract_title' => ['required', 'string', 'max:180'],
            'erp_contract_object' => ['required', 'string'],
            'erp_contract_contract_type' => ['nullable', 'string', 'max:100'],
            'erp_contract_start_date' => ['nullable', 'date'],
            'erp_contract_end_date' => ['nullable', 'date'],
            'erp_contract_total_value' => ['nullable', 'numeric', 'min:0'],
            'erp_contract_payment_method' => ['nullable', 'string', 'max:120'],
            'erp_contract_supervisor_workspace_user_id' => ['nullable', 'integer', 'min:1'],
            'erp_contract_status' => ['nullable', 'in:draft,contractor_data,documents_uploaded,documentation_review,legal_review,pending_approval,approved,signed,active,closed,cancelled'],
            'erp_contract_created_by_workspace_user_id' => ['nullable', 'integer', 'min:1'],
            'erp_process_priority' => ['nullable', 'in:low,medium,high'],
            'erp_process_assigned_to_workspace_user_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $workspaceUserId = $validated['erp_contract_created_by_workspace_user_id']
            ?? currentWorkspaceUserId($request, $validated);

        $contract = DB::transaction(function () use ($validated, $workspaceUserId): ErpContract {
            $process = $this->processService->createProcessForWorkflow('contracts', [
                'erp_process_title' => $validated['erp_contract_title'],
                'erp_process_description' => $validated['erp_contract_object'],
                'erp_process_priority' => $validated['erp_process_priority'] ?? 'medium',
                'erp_process_created_by_workspace_user_id' => $workspaceUserId,
                'erp_process_assigned_to_workspace_user_id' => $validated['erp_process_assigned_to_workspace_user_id'] ?? null,
            ]);

            $contract = ErpContract::query()->create([
                'erp_contract_process_id' => $process->erp_process_id,
                'erp_contract_company_id' => $validated['erp_contract_company_id'] ?? null,
                'erp_contract_contractor_id' => $validated['erp_contract_contractor_id'],
                'erp_contract_code' => $this->numberSequenceService->generate('contract'),
                'erp_contract_title' => $validated['erp_contract_title'],
                'erp_contract_object' => $validated['erp_contract_object'],
                'erp_contract_contract_type' => $validated['erp_contract_contract_type'] ?? null,
                'erp_contract_start_date' => $validated['erp_contract_start_date'] ?? null,
                'erp_contract_end_date' => $validated['erp_contract_end_date'] ?? null,
                'erp_contract_total_value' => $validated['erp_contract_total_value'] ?? 0,
                'erp_contract_payment_method' => $validated['erp_contract_payment_method'] ?? null,
                'erp_contract_supervisor_workspace_user_id' => $validated['erp_contract_supervisor_workspace_user_id'] ?? null,
                'erp_contract_status' => $validated['erp_contract_status'] ?? 'draft',
                'erp_contract_created_by_workspace_user_id' => $workspaceUserId,
            ]);

            $process->update([
                'erp_process_status' => $this->mapContractStatusToProcessStatus($contract->erp_contract_status),
            ]);

            $this->processEventService->logCreated(
                process: $process->fresh(),
                title: "Contract {$contract->erp_contract_code} created",
                description: $contract->erp_contract_title,
                workspaceUserId: $workspaceUserId,
            );

            return $contract;
        });

        return $this->successResponse(
            $this->loadContract($contract),
            'ERP contract created successfully',
            Response::HTTP_CREATED
        );
    }

    public function show(ErpContract $contract): JsonResponse
    {
        return $this->successResponse($this->loadContract($contract), 'ERP contract retrieved successfully');
    }

    public function update(Request $request, ErpContract $contract): JsonResponse
    {
        $validated = $request->validate([
            'erp_contract_company_id' => ['sometimes', 'nullable', 'integer', 'exists:erp_companies,erp_company_id'],
            'erp_contract_contractor_id' => ['sometimes', 'required', 'integer', 'exists:erp_third_parties,erp_third_party_id'],
            'erp_contract_title' => ['sometimes', 'required', 'string', 'max:180'],
            'erp_contract_object' => ['sometimes', 'required', 'string'],
            'erp_contract_contract_type' => ['sometimes', 'nullable', 'string', 'max:100'],
            'erp_contract_start_date' => ['sometimes', 'nullable', 'date'],
            'erp_contract_end_date' => ['sometimes', 'nullable', 'date'],
            'erp_contract_total_value' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'erp_contract_payment_method' => ['sometimes', 'nullable', 'string', 'max:120'],
            'erp_contract_supervisor_workspace_user_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'erp_contract_status' => ['sometimes', 'nullable', 'in:draft,contractor_data,documents_uploaded,documentation_review,legal_review,pending_approval,approved,signed,active,closed,cancelled'],
            'erp_process_assigned_to_workspace_user_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
        ]);

        $workspaceUserId = currentWorkspaceUserId($request, $validated);
        $oldStatus = $contract->erp_contract_status;

        DB::transaction(function () use ($validated, $workspaceUserId, $contract, $oldStatus): void {
            $contract->update($validated);

            if (array_key_exists('erp_process_assigned_to_workspace_user_id', $validated) && $contract->process instanceof ErpProcess) {
                $contract->process->update([
                    'erp_process_assigned_to_workspace_user_id' => $validated['erp_process_assigned_to_workspace_user_id'],
                ]);
            }

            if ($contract->process instanceof ErpProcess) {
                $this->processEventService->logUpdated(
                    process: $contract->process,
                    title: "Contract {$contract->erp_contract_code} updated",
                    description: $contract->erp_contract_title,
                    workspaceUserId: $workspaceUserId,
                );

                if (array_key_exists('erp_contract_status', $validated) && $validated['erp_contract_status'] !== $oldStatus) {
                    $newProcessStatus = $this->mapContractStatusToProcessStatus($validated['erp_contract_status']);

                    $contract->process->update([
                        'erp_process_status' => $newProcessStatus,
                    ]);

                    $this->processEventService->logStatusChanged(
                        process: $contract->process->fresh(),
                        title: "Contract status changed to {$validated['erp_contract_status']}",
                        oldStatus: $oldStatus,
                        newStatus: $validated['erp_contract_status'],
                        workspaceUserId: $workspaceUserId,
                    );
                }
            }
        });

        return $this->successResponse($this->loadContract($contract->fresh()), 'ERP contract updated successfully');
    }

    public function destroy(ErpContract $contract): JsonResponse
    {
        DB::transaction(function () use ($contract): void {
            $contract->load('process');

            if ($contract->process instanceof ErpProcess) {
                $contract->process->delete();

                return;
            }

            $contract->delete();
        });

        return $this->successResponse(null, 'ERP contract deleted successfully');
    }

    public function moveStep(Request $request, ErpContract $contract): JsonResponse
    {
        $validated = $request->validate([
            'erp_workflow_step_slug' => ['required', 'string', 'max:120'],
        ]);

        if (! $contract->process instanceof ErpProcess) {
            return $this->errorResponse('Contract process not found', [], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $workspaceUserId = currentWorkspaceUserId($request, $validated);

            DB::transaction(function () use ($contract, $validated, $workspaceUserId): void {
                $this->processService->moveToStep(
                    process: $contract->process,
                    stepSlug: $validated['erp_workflow_step_slug'],
                    workspaceUserId: $workspaceUserId,
                );

                $newStatus = $this->mapContractStepToStatus($validated['erp_workflow_step_slug'], $contract->erp_contract_status);

                if ($newStatus !== $contract->erp_contract_status) {
                    $oldStatus = $contract->erp_contract_status;

                    $contract->update([
                        'erp_contract_status' => $newStatus,
                    ]);

                    $this->processEventService->logStatusChanged(
                        process: $contract->process->fresh(),
                        title: "Contract status changed to {$newStatus}",
                        oldStatus: $oldStatus,
                        newStatus: $newStatus,
                        workspaceUserId: $workspaceUserId,
                    );
                }
            });
        } catch (\RuntimeException $exception) {
            return $this->errorResponse($exception->getMessage(), [], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->successResponse($this->loadContract($contract->fresh()), 'ERP contract step moved successfully');
    }

    public function close(Request $request, ErpContract $contract): JsonResponse
    {
        if (! $contract->process instanceof ErpProcess) {
            return $this->errorResponse('Contract process not found', [], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $workspaceUserId = currentWorkspaceUserId($request, $request->all());

        DB::transaction(function () use ($contract, $workspaceUserId): void {
            $this->processService->closeProcess($contract->process, $workspaceUserId);

            if ($contract->erp_contract_status !== 'closed') {
                $oldStatus = $contract->erp_contract_status;

                $contract->update([
                    'erp_contract_status' => 'closed',
                ]);

                $this->processEventService->logStatusChanged(
                    process: $contract->process->fresh(),
                    title: 'Contract status changed to closed',
                    oldStatus: $oldStatus,
                    newStatus: 'closed',
                    workspaceUserId: $workspaceUserId,
                );
            }
        });

        return $this->successResponse($this->loadContract($contract->fresh()), 'ERP contract closed successfully');
    }

    protected function loadContract(ErpContract $contract): ErpContract
    {
        return $contract->load([
            'company',
            'contractor',
            'addendums',
            'process.workflow',
            'process.currentStep',
            'process.documents',
            'process.approvals',
            'process.events',
            'process.comments',
        ]);
    }

    protected function mapContractStepToStatus(string $stepSlug, string $currentStatus): string
    {
        return match ($stepSlug) {
            'contractor_data' => 'contractor_data',
            'upload_documents' => 'documents_uploaded',
            'documentation_review' => 'documentation_review',
            'prepare_contract' => 'legal_review',
            'direction_approval' => 'pending_approval',
            'contractor_signature', 'upload_signed_contract' => 'signed',
            'closed' => 'closed',
            default => $currentStatus,
        };
    }

    protected function mapContractStatusToProcessStatus(string $status): string
    {
        return match ($status) {
            'draft' => 'draft',
            'documentation_review', 'legal_review' => 'pending_review',
            'pending_approval' => 'pending_approval',
            'approved' => 'approved',
            'signed' => 'signed',
            'closed' => 'closed',
            'cancelled' => 'cancelled',
            default => 'in_progress',
        };
    }
}
