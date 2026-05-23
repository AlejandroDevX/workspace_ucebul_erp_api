<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ErpApproval;
use App\Models\ErpContract;
use App\Models\ErpProcess;
use App\Models\ErpThirdParty;
use App\Models\ErpWorkflowStep;
use App\Services\ErpContractorDocumentService;
use App\Services\ErpExternalSignatureService;
use App\Services\ErpNotificationService;
use App\Services\ErpNumberSequenceService;
use App\Services\ErpProcessEventService;
use App\Services\ErpProcessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class ErpContractController extends Controller
{
    public function __construct(
        protected ErpNumberSequenceService $numberSequenceService,
        protected ErpProcessService $processService,
        protected ErpProcessEventService $processEventService,
        protected ErpContractorDocumentService $contractorDocumentService,
        protected ErpExternalSignatureService $signatureService,
        protected ErpNotificationService $notificationService,
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
            ->when(
                $request->boolean('my_tasks'),
                function ($query) {
                    $userId = currentWorkspaceUserId();
                    $roleSlug = currentWorkspaceUserRoleSlug();

                    $query->whereHas('process', function ($q) use ($userId, $roleSlug) {
                        $q->where(function ($inner) use ($userId, $roleSlug) {
                            if ($userId) {
                                $inner->orWhere('erp_process_assigned_to_workspace_user_id', $userId);
                            }
                            if ($roleSlug) {
                                $inner->orWhere('erp_process_current_responsible_role_name', $roleSlug);
                            }
                        })->whereNotIn('erp_process_status', ['closed', 'cancelled']);
                    });
                }
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
            'erp_process_priority' => ['nullable', 'in:low,medium,high'],
            'erp_process_assigned_to_workspace_user_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $workspaceUserId = currentWorkspaceUserId();

        $contractor = ErpThirdParty::query()->find($validated['erp_contract_contractor_id']);

        if ($contractor instanceof ErpThirdParty) {
            $this->contractorDocumentService->validateRequiredDocumentsOrFail($contractor);
        }

        $contract = DB::transaction(function () use ($validated, $workspaceUserId): ErpContract {
            $process = $this->processService->createProcessForWorkflow('contracts', [
                'erp_process_title' => $validated['erp_contract_title'],
                'erp_process_description' => $validated['erp_contract_object'],
                'erp_process_priority' => $validated['erp_process_priority'] ?? 'medium',
                'erp_process_created_by_workspace_user_id' => $workspaceUserId,
                'erp_process_assigned_to_workspace_user_id' => null,
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
                'erp_contract_status' => 'draft',
                'erp_contract_created_by_workspace_user_id' => $workspaceUserId,
            ]);

            $this->processEventService->logCreated(
                process: $process->fresh(),
                title: "Contrato {$contract->erp_contract_code} creado",
                description: $contract->erp_contract_title,
                workspaceUserId: $workspaceUserId,
            );

            // Step 1 (coordinator_creation) is completed by the act of creating the contract.
            // Auto-advance to step 2 so administrative role can begin review.
            $this->processService->moveToStep(
                process: $process->fresh(),
                stepSlug: 'administrative_review',
                workspaceUserId: $workspaceUserId,
                referenceType: 'contract',
                referenceId: $contract->erp_contract_id,
            );

            $contract->update(['erp_contract_status' => 'administrative_review']);

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

        $workspaceUserId = currentWorkspaceUserId();
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
                    title: "Contrato {$contract->erp_contract_code} actualizado",
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
                        title: 'Estado del contrato actualizado',
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
            'erp_workflow_step_slug' => ['nullable', 'string', 'max:120'],
            'erp_process_assigned_to_workspace_user_id' => ['nullable', 'integer', 'min:1'],
        ]);

        if (! $contract->process instanceof ErpProcess) {
            return $this->errorResponse('Contract process not found', [], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $stepSlug = $validated['erp_workflow_step_slug']
            ?? $this->processService->getNextStepSlug($contract->process);

        if (! $stepSlug) {
            return $this->errorResponse('No next step available.', [], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $workspaceUserId = currentWorkspaceUserId();

            DB::transaction(function () use ($contract, $stepSlug, $workspaceUserId, $validated): void {
                if (isset($validated['erp_process_assigned_to_workspace_user_id'])) {
                    $contract->process->update([
                        'erp_process_assigned_to_workspace_user_id' => $validated['erp_process_assigned_to_workspace_user_id'],
                    ]);
                }

                $contract->process->loadMissing('currentStep');
                $currentStep = $contract->process->currentStep;

                if ($currentStep instanceof ErpWorkflowStep &&
                    $currentStep->erp_workflow_step_slug === 'direction_approval') {
                    ErpApproval::query()->create([
                        'erp_approval_process_id' => $contract->process->erp_process_id,
                        'erp_approval_step_id' => $currentStep->erp_workflow_step_id,
                        'erp_approval_reference_type' => 'contract',
                        'erp_approval_reference_id' => $contract->erp_contract_id,
                        'erp_approval_status' => 'approved',
                        'erp_approval_requested_by_workspace_user_id' => $workspaceUserId,
                        'erp_approval_approved_by_workspace_user_id' => $workspaceUserId,
                        'erp_approval_requested_at' => Carbon::now(),
                        'erp_approval_resolved_at' => Carbon::now(),
                    ]);
                }

                $this->processService->moveToStep(
                    process: $contract->process->fresh(),
                    stepSlug: $stepSlug,
                    workspaceUserId: $workspaceUserId,
                    referenceType: 'contract',
                    referenceId: $contract->erp_contract_id,
                );

                $newStatus = $this->mapContractStepToStatus($stepSlug, $contract->erp_contract_status);

                if ($newStatus !== $contract->erp_contract_status) {
                    $oldStatus = $contract->erp_contract_status;

                    $contract->update([
                        'erp_contract_status' => $newStatus,
                    ]);

                    $this->processEventService->logStatusChanged(
                        process: $contract->process->fresh(),
                        title: 'Estado del contrato actualizado',
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

        $workspaceUserId = currentWorkspaceUserId();

        DB::transaction(function () use ($contract, $workspaceUserId): void {
            $this->processService->closeProcess($contract->process, $workspaceUserId);

            if ($contract->erp_contract_status !== 'closed') {
                $oldStatus = $contract->erp_contract_status;

                $contract->update([
                    'erp_contract_status' => 'closed',
                ]);

                $this->processEventService->logStatusChanged(
                    process: $contract->process->fresh(),
                    title: 'Contrato cerrado',
                    oldStatus: $oldStatus,
                    newStatus: 'closed',
                    workspaceUserId: $workspaceUserId,
                );
            }
        });

        return $this->successResponse($this->loadContract($contract->fresh()), 'ERP contract closed successfully');
    }

    public function requestSignature(Request $request, ErpContract $contract): JsonResponse
    {
        if (! $contract->process instanceof ErpProcess) {
            return $this->errorResponse('Contract process not found', [], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $validated = $request->validate([
            'erp_external_signature_request_document_id' => ['nullable', 'integer', 'exists:erp_documents,erp_document_id'],
            'erp_external_signature_request_expires_in_days' => ['nullable', 'integer', 'min:1', 'max:90'],
        ]);

        $contract->loadMissing('contractor');

        if (! $contract->contractor instanceof ErpThirdParty) {
            return $this->errorResponse('Contract has no contractor', [], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $workspaceUserId = currentWorkspaceUserId();

        $signatureRequest = $this->signatureService->createSignatureRequest(
            process: $contract->process,
            referenceType: 'contract',
            referenceId: $contract->erp_contract_id,
            thirdPartyId: $contract->erp_contract_contractor_id,
            documentId: $validated['erp_external_signature_request_document_id'] ?? null,
            signerName: $contract->contractor->erp_third_party_name,
            signerEmail: $contract->contractor->erp_third_party_email,
            signerDocumentNumber: $contract->contractor->erp_third_party_document_number,
            createdByWorkspaceUserId: $workspaceUserId,
            expiresInDays: $validated['erp_external_signature_request_expires_in_days'] ?? 7,
        );

        if ($workspaceUserId) {
            $this->notificationService->notifySignatureRequested(
                workspaceUserId: $workspaceUserId,
                process: $contract->process,
                signerName: $contract->contractor->erp_third_party_name,
                referenceType: 'contract',
                referenceId: $contract->erp_contract_id,
                createdByWorkspaceUserId: $workspaceUserId,
            );
        }

        return $this->successResponse($signatureRequest, 'Signature request created successfully', Response::HTTP_CREATED);
    }

    protected function loadContract(ErpContract $contract): ErpContract
    {
        return $contract->load([
            'company',
            'contractor',
            'addendums',
            'pdfDocument',
            'signedPdfDocument',
            'process.workflow',
            'process.currentStep',
            'process.workflow.steps' => fn ($q) => $q->where('erp_workflow_step_is_active', true)->orderBy('erp_workflow_step_order'),
            'process.documents',
            'process.approvals',
            'process.events',
            'process.comments',
            'process.signatureRequests',
        ]);
    }

    protected function mapContractStepToStatus(string $stepSlug, string $currentStatus): string
    {
        return match ($stepSlug) {
            'coordinator_creation' => 'draft',
            'administrative_review' => 'administrative_review',
            'legal_review' => 'legal_review',
            'direction_approval' => 'direction_review',
            'contractor_signature' => 'pending_signature',
            'coordinator_close' => 'signed',
            // legacy slugs kept for compatibility
            'contractor_data' => 'contractor_data',
            'upload_documents' => 'documents_uploaded',
            'documentation_review' => 'documentation_review',
            'prepare_contract' => 'legal_review',
            'upload_signed_contract' => 'signed',
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
