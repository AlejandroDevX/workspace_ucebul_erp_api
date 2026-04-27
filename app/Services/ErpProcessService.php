<?php

namespace App\Services;

use App\Models\ErpProcess;
use App\Models\ErpWorkflow;
use App\Models\ErpWorkflowStep;
use Illuminate\Support\Carbon;
use RuntimeException;

class ErpProcessService
{
    public function __construct(
        protected ErpNumberSequenceService $numberSequenceService,
        protected ErpProcessEventService $processEventService,
        protected ErpNotificationService $notificationService,
    ) {}

    public function createProcessForWorkflow(string $workflowSlug, array $data): ErpProcess
    {
        $workflow = ErpWorkflow::query()
            ->where('erp_workflow_slug', $workflowSlug)
            ->where('erp_workflow_is_active', true)
            ->with(['steps' => fn ($query) => $query->where('erp_workflow_step_is_active', true)])
            ->first();

        if (! $workflow instanceof ErpWorkflow) {
            throw new RuntimeException("Workflow [{$workflowSlug}] not found.");
        }

        $firstStep = $workflow->steps->sortBy('erp_workflow_step_order')->first();
        $initialStatus = $data['erp_process_status']
            ?? ($firstStep instanceof ErpWorkflowStep ? $this->resolveStatusForStep($firstStep, true) : 'draft');

        $process = ErpProcess::query()->create([
            'erp_process_workflow_id' => $workflow->erp_workflow_id,
            'erp_process_current_step_id' => $firstStep?->erp_workflow_step_id,
            'erp_process_code' => $this->numberSequenceService->generate('process'),
            'erp_process_title' => $data['erp_process_title'],
            'erp_process_description' => $data['erp_process_description'] ?? null,
            'erp_process_status' => $initialStatus,
            'erp_process_priority' => $data['erp_process_priority'] ?? 'medium',
            'erp_process_created_by_workspace_user_id' => $data['erp_process_created_by_workspace_user_id'] ?? null,
            'erp_process_assigned_to_workspace_user_id' => $data['erp_process_assigned_to_workspace_user_id'] ?? null,
            'erp_process_started_at' => $data['erp_process_started_at'] ?? Carbon::now(),
        ]);

        $this->processEventService->logCreated(
            process: $process,
            title: "Process created for workflow {$workflow->erp_workflow_name}",
            description: $process->erp_process_title,
            workspaceUserId: $data['erp_process_created_by_workspace_user_id'] ?? null,
        );

        return $process->load(['workflow', 'currentStep']);
    }

    public function moveToStep(
        ErpProcess $process,
        string $stepSlug,
        ?int $workspaceUserId = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
    ): ErpProcess {
        $step = ErpWorkflowStep::query()
            ->where('erp_workflow_step_workflow_id', $process->erp_process_workflow_id)
            ->where('erp_workflow_step_slug', $stepSlug)
            ->where('erp_workflow_step_is_active', true)
            ->first();

        if (! $step instanceof ErpWorkflowStep) {
            throw new RuntimeException("Workflow step [{$stepSlug}] not found.");
        }

        $oldStatus = $process->erp_process_status;
        $newStatus = $this->resolveStatusForStep($step);

        $process->fill([
            'erp_process_current_step_id' => $step->erp_workflow_step_id,
            'erp_process_status' => $newStatus,
            'erp_process_current_responsible_role_name' => $step->erp_workflow_step_responsible_role_name,
            'erp_process_last_moved_by_workspace_user_id' => $workspaceUserId,
            'erp_process_last_moved_at' => Carbon::now(),
        ]);

        if ($newStatus === 'closed') {
            $process->erp_process_closed_at = Carbon::now();
        }

        $process->save();

        $this->processEventService->logStatusChanged(
            process: $process,
            title: "Process moved to step {$step->erp_workflow_step_name}",
            oldStatus: $oldStatus,
            newStatus: $newStatus,
            description: $step->erp_workflow_step_description,
            workspaceUserId: $workspaceUserId,
        );

        $this->notificationService->notifyProcessMoved(
            process: $process,
            toStep: $step,
            movedByWorkspaceUserId: $workspaceUserId,
            referenceType: $referenceType,
            referenceId: $referenceId,
        );

        return $process->load(['workflow', 'currentStep']);
    }

    public function closeProcess(ErpProcess $process, ?int $workspaceUserId = null): ErpProcess
    {
        $oldStatus = $process->erp_process_status;

        $finalStep = ErpWorkflowStep::query()
            ->where('erp_workflow_step_workflow_id', $process->erp_process_workflow_id)
            ->where('erp_workflow_step_is_final', true)
            ->where('erp_workflow_step_is_active', true)
            ->orderBy('erp_workflow_step_order')
            ->first();

        $process->fill([
            'erp_process_current_step_id' => $finalStep?->erp_workflow_step_id ?? $process->erp_process_current_step_id,
            'erp_process_status' => 'closed',
            'erp_process_closed_at' => Carbon::now(),
        ]);
        $process->save();

        $this->processEventService->logStatusChanged(
            process: $process,
            title: 'Process closed',
            oldStatus: $oldStatus,
            newStatus: 'closed',
            workspaceUserId: $workspaceUserId,
        );

        return $process->load(['workflow', 'currentStep']);
    }

    public function getNextStepSlug(ErpProcess $process): ?string
    {
        $process->loadMissing('currentStep');

        $currentOrder = $process->currentStep instanceof ErpWorkflowStep
            ? $process->currentStep->erp_workflow_step_order
            : 0;

        $nextStep = ErpWorkflowStep::query()
            ->where('erp_workflow_step_workflow_id', $process->erp_process_workflow_id)
            ->where('erp_workflow_step_is_active', true)
            ->where('erp_workflow_step_order', '>', $currentOrder)
            ->orderBy('erp_workflow_step_order')
            ->first();

        return $nextStep?->erp_workflow_step_slug;
    }

    protected function resolveStatusForStep(ErpWorkflowStep $step, bool $isInitial = false): string
    {
        if ($isInitial && $step->erp_workflow_step_slug === 'create_request') {
            return 'draft';
        }

        if ($step->erp_workflow_step_is_final) {
            return 'closed';
        }

        if ($step->erp_workflow_step_requires_approval) {
            return 'pending_approval';
        }

        if (str_contains($step->erp_workflow_step_slug, 'review')) {
            return 'pending_review';
        }

        return 'in_progress';
    }
}
