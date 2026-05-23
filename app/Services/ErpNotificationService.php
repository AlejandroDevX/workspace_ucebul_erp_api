<?php

namespace App\Services;

use App\Models\ErpNotification;
use App\Models\ErpProcess;
use App\Models\ErpWorkflowStep;

class ErpNotificationService
{
    public function notifyUser(
        int $workspaceUserId,
        string $type,
        string $title,
        ?string $message = null,
        ?string $actionUrl = null,
        ?ErpProcess $process = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?array $metadata = null,
        ?int $createdByWorkspaceUserId = null,
    ): ErpNotification {
        return ErpNotification::query()->create([
            'erp_notification_workspace_user_id' => $workspaceUserId,
            'erp_notification_process_id' => $process?->erp_process_id,
            'erp_notification_reference_type' => $referenceType,
            'erp_notification_reference_id' => $referenceId,
            'erp_notification_type' => $type,
            'erp_notification_title' => $title,
            'erp_notification_message' => $message,
            'erp_notification_action_url' => $actionUrl,
            'erp_notification_metadata' => $metadata,
            'erp_notification_created_by_workspace_user_id' => $createdByWorkspaceUserId,
        ]);
    }

    public function notifyRole(
        string $roleName,
        string $type,
        string $title,
        ?string $message = null,
        ?string $actionUrl = null,
        ?ErpProcess $process = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?array $metadata = null,
        ?int $createdByWorkspaceUserId = null,
    ): ErpNotification {
        return ErpNotification::query()->create([
            'erp_notification_target_role_name' => $roleName,
            'erp_notification_process_id' => $process?->erp_process_id,
            'erp_notification_reference_type' => $referenceType,
            'erp_notification_reference_id' => $referenceId,
            'erp_notification_type' => $type,
            'erp_notification_title' => $title,
            'erp_notification_message' => $message,
            'erp_notification_action_url' => $actionUrl,
            'erp_notification_metadata' => $metadata,
            'erp_notification_created_by_workspace_user_id' => $createdByWorkspaceUserId,
        ]);
    }

    public function notifyProcessMoved(
        ErpProcess $process,
        ErpWorkflowStep $toStep,
        ?int $movedByWorkspaceUserId = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
    ): void {
        $assignedUserId = $process->erp_process_assigned_to_workspace_user_id;

        if ($assignedUserId) {
            $this->notifyUser(
                workspaceUserId: (int) $assignedUserId,
                type: 'process_moved',
                title: "Proceso asignado: {$toStep->erp_workflow_step_name}",
                message: 'Se te ha asignado un proceso.',
                process: $process,
                referenceType: $referenceType,
                referenceId: $referenceId,
                createdByWorkspaceUserId: $movedByWorkspaceUserId,
            );

            return;
        }

        $responsibleRole = $toStep->erp_workflow_step_responsible_role_name;

        if ($responsibleRole) {
            $this->notifyRole(
                roleName: $responsibleRole,
                type: 'process_moved',
                title: "Proceso movido a: {$toStep->erp_workflow_step_name}",
                message: 'Un proceso ha sido asignado a su rol.',
                process: $process,
                referenceType: $referenceType,
                referenceId: $referenceId,
                createdByWorkspaceUserId: $movedByWorkspaceUserId,
            );
        }
    }

    public function notifySignatureRequested(
        int $workspaceUserId,
        ErpProcess $process,
        string $signerName,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?int $createdByWorkspaceUserId = null,
    ): void {
        $this->notifyUser(
            workspaceUserId: $workspaceUserId,
            type: 'signature_requested',
            title: 'Solicitud de firma enviada',
            message: "Se ha enviado una solicitud de firma a {$signerName}.",
            process: $process,
            referenceType: $referenceType,
            referenceId: $referenceId,
            createdByWorkspaceUserId: $createdByWorkspaceUserId,
        );
    }

    public function notifySignatureCompleted(
        ErpProcess $process,
        string $signerName,
        ?string $referenceType = null,
        ?int $referenceId = null,
    ): void {
        $assignedUserId = $process->erp_process_assigned_to_workspace_user_id;

        if ($assignedUserId) {
            $this->notifyUser(
                workspaceUserId: (int) $assignedUserId,
                type: 'signature_completed',
                title: 'Firma completada',
                message: "{$signerName} ha completado la firma del documento.",
                process: $process,
                referenceType: $referenceType,
                referenceId: $referenceId,
            );

            return;
        }

        $this->notifyRole(
            roleName: 'coordinator',
            type: 'signature_completed',
            title: 'Firma completada',
            message: "{$signerName} ha completado la firma del documento.",
            process: $process,
            referenceType: $referenceType,
            referenceId: $referenceId,
        );
    }
}
