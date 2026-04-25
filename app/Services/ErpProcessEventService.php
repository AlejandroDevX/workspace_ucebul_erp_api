<?php

namespace App\Services;

use App\Models\ErpProcess;
use App\Models\ErpProcessEvent;

class ErpProcessEventService
{
    public function logCreated(
        ErpProcess $process,
        string $title,
        ?string $description = null,
        ?int $workspaceUserId = null,
    ): ErpProcessEvent {
        return $this->createEvent(
            process: $process,
            type: 'created',
            title: $title,
            description: $description,
            workspaceUserId: $workspaceUserId,
        );
    }

    public function logUpdated(
        ErpProcess $process,
        string $title,
        ?string $description = null,
        ?int $workspaceUserId = null,
    ): ErpProcessEvent {
        return $this->createEvent(
            process: $process,
            type: 'updated',
            title: $title,
            description: $description,
            workspaceUserId: $workspaceUserId,
        );
    }

    public function logStatusChanged(
        ErpProcess $process,
        string $title,
        ?string $oldStatus,
        ?string $newStatus,
        ?string $description = null,
        ?int $workspaceUserId = null,
    ): ErpProcessEvent {
        return $this->createEvent(
            process: $process,
            type: 'status_changed',
            title: $title,
            description: $description,
            oldStatus: $oldStatus,
            newStatus: $newStatus,
            workspaceUserId: $workspaceUserId,
        );
    }

    public function logDocumentUploaded(
        ErpProcess $process,
        string $documentName,
        ?int $workspaceUserId = null,
        ?string $description = null,
    ): ErpProcessEvent {
        return $this->createEvent(
            process: $process,
            type: 'document_uploaded',
            title: "Document uploaded: {$documentName}",
            description: $description,
            workspaceUserId: $workspaceUserId,
        );
    }

    public function logApprovalResolved(
        ErpProcess $process,
        string $status,
        ?int $workspaceUserId = null,
        ?string $comment = null,
    ): ErpProcessEvent {
        return $this->createEvent(
            process: $process,
            type: 'approval_resolved',
            title: "Approval {$status}",
            description: $comment,
            workspaceUserId: $workspaceUserId,
        );
    }

    protected function createEvent(
        ErpProcess $process,
        string $type,
        string $title,
        ?string $description = null,
        ?string $oldStatus = null,
        ?string $newStatus = null,
        ?int $workspaceUserId = null,
    ): ErpProcessEvent {
        return ErpProcessEvent::query()->create([
            'erp_process_event_process_id' => $process->erp_process_id,
            'erp_process_event_step_id' => $process->erp_process_current_step_id,
            'erp_process_event_type' => $type,
            'erp_process_event_title' => $title,
            'erp_process_event_description' => $description,
            'erp_process_event_old_status' => $oldStatus,
            'erp_process_event_new_status' => $newStatus,
            'erp_process_event_created_by_workspace_user_id' => $workspaceUserId,
        ]);
    }
}
