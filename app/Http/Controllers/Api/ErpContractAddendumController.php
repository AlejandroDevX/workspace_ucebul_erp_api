<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ErpContractAddendum;
use App\Models\ErpProcess;
use App\Services\ErpExternalSignatureService;
use App\Services\ErpNotificationService;
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
        protected ErpExternalSignatureService $signatureService,
        protected ErpNotificationService $notificationService,
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
            'erp_process_priority' => ['nullable', 'in:low,medium,high'],
            'erp_process_assigned_to_workspace_user_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $workspaceUserId = currentWorkspaceUserId();

        $contractAddendum = DB::transaction(function () use ($validated, $workspaceUserId): ErpContractAddendum {
            $process = $this->processService->createProcessForWorkflow('contract_addendums', [
                'erp_process_title' => $validated['erp_contract_addendum_title'],
                'erp_process_description' => $validated['erp_contract_addendum_description'],
                'erp_process_priority' => $validated['erp_process_priority'] ?? 'medium',
                'erp_process_created_by_workspace_user_id' => $workspaceUserId,
                'erp_process_assigned_to_workspace_user_id' => null,
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
                'erp_contract_addendum_status' => 'draft',
                'erp_contract_addendum_created_by_workspace_user_id' => $workspaceUserId,
            ]);

            $this->processEventService->logCreated(
                process: $process->fresh(),
                title: "Otrosí {$contractAddendum->erp_contract_addendum_code} creado",
                description: $contractAddendum->erp_contract_addendum_title,
                workspaceUserId: $workspaceUserId,
            );

            // Step 1 completed by creation — auto-advance to administrative_review.
            $this->processService->moveToStep(
                process: $process->fresh(),
                stepSlug: 'administrative_review',
                workspaceUserId: $workspaceUserId,
                referenceType: 'contract_addendum',
                referenceId: $contractAddendum->erp_contract_addendum_id,
            );

            $contractAddendum->update(['erp_contract_addendum_status' => 'administrative_review']);

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

        $workspaceUserId = currentWorkspaceUserId();
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
                    title: "Otrosí {$contractAddendum->erp_contract_addendum_code} actualizado",
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
                        title: 'Estado del otrosí actualizado',
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
            'erp_workflow_step_slug' => ['nullable', 'string', 'max:120'],
            'erp_process_assigned_to_workspace_user_id' => ['nullable', 'integer', 'min:1'],
        ]);

        if (! $contractAddendum->process instanceof ErpProcess) {
            return $this->errorResponse('Contract addendum process not found', [], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $stepSlug = $validated['erp_workflow_step_slug']
            ?? $this->processService->getNextStepSlug($contractAddendum->process);

        if (! $stepSlug) {
            return $this->errorResponse('No next step available.', [], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $workspaceUserId = currentWorkspaceUserId();

            DB::transaction(function () use ($contractAddendum, $stepSlug, $workspaceUserId, $validated): void {
                if (isset($validated['erp_process_assigned_to_workspace_user_id'])) {
                    $contractAddendum->process->update([
                        'erp_process_assigned_to_workspace_user_id' => $validated['erp_process_assigned_to_workspace_user_id'],
                    ]);
                }

                $this->processService->moveToStep(
                    process: $contractAddendum->process->fresh(),
                    stepSlug: $stepSlug,
                    workspaceUserId: $workspaceUserId,
                    referenceType: 'contract_addendum',
                    referenceId: $contractAddendum->erp_contract_addendum_id,
                );

                $newStatus = $this->mapAddendumStepToStatus(
                    $stepSlug,
                    $contractAddendum->erp_contract_addendum_status
                );

                if ($newStatus !== $contractAddendum->erp_contract_addendum_status) {
                    $oldStatus = $contractAddendum->erp_contract_addendum_status;

                    $contractAddendum->update([
                        'erp_contract_addendum_status' => $newStatus,
                    ]);

                    $this->processEventService->logStatusChanged(
                        process: $contractAddendum->process->fresh(),
                        title: 'Estado del otrosí actualizado',
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

    public function close(ErpContractAddendum $contractAddendum): JsonResponse
    {
        if (! $contractAddendum->process instanceof ErpProcess) {
            return $this->errorResponse('Contract addendum process not found', [], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $workspaceUserId = currentWorkspaceUserId();

        DB::transaction(function () use ($contractAddendum, $workspaceUserId): void {
            $this->processService->closeProcess($contractAddendum->process, $workspaceUserId);

            if ($contractAddendum->erp_contract_addendum_status !== 'closed') {
                $oldStatus = $contractAddendum->erp_contract_addendum_status;

                $contractAddendum->update([
                    'erp_contract_addendum_status' => 'closed',
                ]);

                $this->processEventService->logStatusChanged(
                    process: $contractAddendum->process->fresh(),
                    title: 'Otrosí cerrado',
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

    public function requestSignature(Request $request, ErpContractAddendum $contractAddendum): JsonResponse
    {
        if (! $contractAddendum->process instanceof ErpProcess) {
            return $this->errorResponse('Contract addendum process not found', [], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $validated = $request->validate([
            'erp_external_signature_request_document_id' => ['nullable', 'integer', 'exists:erp_documents,erp_document_id'],
            'erp_external_signature_request_expires_in_days' => ['nullable', 'integer', 'min:1', 'max:90'],
        ]);

        $contractAddendum->loadMissing('contract.contractor');
        $contractor = $contractAddendum->contract?->contractor;

        if (! $contractor) {
            return $this->errorResponse('Contract has no contractor', [], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $workspaceUserId = currentWorkspaceUserId();

        $signatureRequest = $this->signatureService->createSignatureRequest(
            process: $contractAddendum->process,
            referenceType: 'contract_addendum',
            referenceId: $contractAddendum->erp_contract_addendum_id,
            thirdPartyId: $contractor->erp_third_party_id,
            documentId: $validated['erp_external_signature_request_document_id'] ?? null,
            signerName: $contractor->erp_third_party_name,
            signerEmail: $contractor->erp_third_party_email,
            signerDocumentNumber: $contractor->erp_third_party_document_number,
            createdByWorkspaceUserId: $workspaceUserId,
            expiresInDays: $validated['erp_external_signature_request_expires_in_days'] ?? 7,
        );

        if ($workspaceUserId) {
            $this->notificationService->notifySignatureRequested(
                workspaceUserId: $workspaceUserId,
                process: $contractAddendum->process,
                signerName: $contractor->erp_third_party_name,
                referenceType: 'contract_addendum',
                referenceId: $contractAddendum->erp_contract_addendum_id,
                createdByWorkspaceUserId: $workspaceUserId,
            );
        }

        return $this->successResponse($signatureRequest, 'Signature request created successfully', Response::HTTP_CREATED);
    }

    protected function loadContractAddendum(ErpContractAddendum $contractAddendum): ErpContractAddendum
    {
        return $contractAddendum->load([
            'contract',
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

    protected function mapAddendumStepToStatus(string $stepSlug, string $currentStatus): string
    {
        return match ($stepSlug) {
            'coordinator_creation' => 'draft',
            'administrative_review' => 'administrative_review',
            'legal_review' => 'legal_review',
            'direction_approval' => 'direction_review',
            'contractor_signature' => 'pending_signature',
            'coordinator_close' => 'signed',
            // legacy slugs
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
