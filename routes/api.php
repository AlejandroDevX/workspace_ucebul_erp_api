<?php

use App\Http\Controllers\Api\ErpApprovalController;
use App\Http\Controllers\Api\ErpCommentController;
use App\Http\Controllers\Api\ErpContractAddendumController;
use App\Http\Controllers\Api\ErpContractController;
use App\Http\Controllers\Api\ErpDocumentController;
use App\Http\Controllers\Api\ErpNotificationController;
use App\Http\Controllers\Api\ErpPublicSignatureController;
use App\Http\Controllers\Api\ErpThirdPartyController;
use App\Http\Controllers\Api\ErpWorkflowController;
use App\Http\Controllers\Api\ErpWorkspaceUserController;
use Illuminate\Support\Facades\Route;

// Public signature routes (no auth required)
Route::prefix('erp/public')->group(function () {
    Route::get('sign/{token}', [ErpPublicSignatureController::class, 'show']);
    Route::post('sign/{token}', [ErpPublicSignatureController::class, 'sign']);
});

Route::prefix('erp')->middleware('workspace.auth')->group(function () {
    // Third Parties
    Route::get('third-parties', [ErpThirdPartyController::class, 'index'])->middleware('workspace.permission:erp.third_parties.view');
    Route::post('third-parties', [ErpThirdPartyController::class, 'store'])->middleware('workspace.permission:erp.third_parties.create');
    Route::get('third-parties/{third_party}', [ErpThirdPartyController::class, 'show'])->middleware('workspace.permission:erp.third_parties.view');
    Route::put('third-parties/{third_party}', [ErpThirdPartyController::class, 'update'])->middleware('workspace.permission:erp.third_parties.update');
    Route::patch('third-parties/{third_party}', [ErpThirdPartyController::class, 'update'])->middleware('workspace.permission:erp.third_parties.update');
    Route::delete('third-parties/{third_party}', [ErpThirdPartyController::class, 'destroy'])->middleware('workspace.permission:erp.third_parties.delete');
    Route::get('third-parties/{third_party}/required-documents-status', [ErpThirdPartyController::class, 'requiredDocumentsStatus'])->middleware('workspace.permission:erp.third_parties.view');
    Route::post('third-parties/{third_party}/required-documents/{documentType}', [ErpThirdPartyController::class, 'uploadRequiredDocument'])->middleware('workspace.permission:erp.documents.upload');
    Route::delete('third-parties/{third_party}/required-documents/{documentType}', [ErpThirdPartyController::class, 'deleteRequiredDocument'])->middleware('workspace.permission:erp.documents.delete');

    // Contracts
    Route::get('contracts', [ErpContractController::class, 'index'])->middleware('workspace.permission:erp.contracts.view');
    Route::post('contracts', [ErpContractController::class, 'store'])->middleware('workspace.permission:erp.contracts.create');
    Route::get('contracts/{contract}', [ErpContractController::class, 'show'])->middleware('workspace.permission:erp.contracts.view');
    Route::put('contracts/{contract}', [ErpContractController::class, 'update'])->middleware('workspace.permission:erp.contracts.update');
    Route::patch('contracts/{contract}', [ErpContractController::class, 'update'])->middleware('workspace.permission:erp.contracts.update');
    Route::delete('contracts/{contract}', [ErpContractController::class, 'destroy'])->middleware('workspace.permission:erp.contracts.delete');
    Route::post('contracts/{contract}/move-step', [ErpContractController::class, 'moveStep'])->middleware('workspace.permission:erp.contracts.move_step');
    Route::post('contracts/{contract}/close', [ErpContractController::class, 'close'])->middleware('workspace.permission:erp.contracts.close');
    Route::post('contracts/{contract}/request-signature', [ErpContractController::class, 'requestSignature'])->middleware('workspace.permission:erp.contracts.move_step');

    // Contract Addendums
    Route::get('contract-addendums', [ErpContractAddendumController::class, 'index'])->middleware('workspace.permission:erp.contract_addendums.view');
    Route::post('contract-addendums', [ErpContractAddendumController::class, 'store'])->middleware('workspace.permission:erp.contract_addendums.create');
    Route::get('contract-addendums/{contract_addendum}', [ErpContractAddendumController::class, 'show'])->middleware('workspace.permission:erp.contract_addendums.view');
    Route::put('contract-addendums/{contract_addendum}', [ErpContractAddendumController::class, 'update'])->middleware('workspace.permission:erp.contract_addendums.update');
    Route::patch('contract-addendums/{contract_addendum}', [ErpContractAddendumController::class, 'update'])->middleware('workspace.permission:erp.contract_addendums.update');
    Route::delete('contract-addendums/{contract_addendum}', [ErpContractAddendumController::class, 'destroy'])->middleware('workspace.permission:erp.contract_addendums.delete');
    Route::post('contract-addendums/{contract_addendum}/move-step', [ErpContractAddendumController::class, 'moveStep'])->middleware('workspace.permission:erp.contract_addendums.move_step');
    Route::post('contract-addendums/{contract_addendum}/close', [ErpContractAddendumController::class, 'close'])->middleware('workspace.permission:erp.contract_addendums.close');
    Route::post('contract-addendums/{contract_addendum}/request-signature', [ErpContractAddendumController::class, 'requestSignature'])->middleware('workspace.permission:erp.contract_addendums.move_step');

    // Documents
    Route::get('documents', [ErpDocumentController::class, 'index'])->middleware('workspace.permission:erp.documents.view');
    Route::post('documents', [ErpDocumentController::class, 'store'])->middleware('workspace.permission:erp.documents.upload');
    Route::delete('documents/{document}', [ErpDocumentController::class, 'destroy'])->middleware('workspace.permission:erp.documents.delete');

    // Approvals
    Route::get('approvals', [ErpApprovalController::class, 'index'])->middleware('workspace.permission:erp.approvals.view');
    Route::post('approvals', [ErpApprovalController::class, 'store'])->middleware('workspace.permission:erp.approvals.create');
    Route::post('approvals/{approval}/approve', [ErpApprovalController::class, 'approve'])->middleware('workspace.permission:erp.approvals.approve');
    Route::post('approvals/{approval}/reject', [ErpApprovalController::class, 'reject'])->middleware('workspace.permission:erp.approvals.reject');

    // Comments
    Route::get('comments', [ErpCommentController::class, 'index'])->middleware('workspace.permission:erp.comments.view');
    Route::post('comments', [ErpCommentController::class, 'store'])->middleware('workspace.permission:erp.comments.create');
    Route::delete('comments/{comment}', [ErpCommentController::class, 'destroy'])->middleware('workspace.permission:erp.comments.delete');

    // Workflows (read-only)
    Route::get('workflows', [ErpWorkflowController::class, 'index'])->middleware('workspace.permission:erp.workflows.view');
    Route::get('workflows/{workflow}', [ErpWorkflowController::class, 'show'])->middleware('workspace.permission:erp.workflows.view');

    // Workspace users (for step assignment)
    Route::get('workspace-users', [ErpWorkspaceUserController::class, 'index']);

    // Notifications
    Route::get('notifications', [ErpNotificationController::class, 'index']);
    Route::get('notifications/unread-count', [ErpNotificationController::class, 'unreadCount']);
    Route::post('notifications/{notification}/read', [ErpNotificationController::class, 'markRead']);
    Route::post('notifications/read-all', [ErpNotificationController::class, 'markAllRead']);
});
