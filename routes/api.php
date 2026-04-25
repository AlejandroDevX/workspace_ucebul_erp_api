<?php

use App\Http\Controllers\Api\ErpApprovalController;
use App\Http\Controllers\Api\ErpCommentController;
use App\Http\Controllers\Api\ErpContractAddendumController;
use App\Http\Controllers\Api\ErpContractController;
use App\Http\Controllers\Api\ErpDocumentController;
use App\Http\Controllers\Api\ErpThirdPartyController;
use App\Http\Controllers\Api\ErpWorkflowController;
use Illuminate\Support\Facades\Route;

Route::prefix('erp')->group(function () {
    Route::apiResource('third-parties', ErpThirdPartyController::class);
    Route::apiResource('contracts', ErpContractController::class);
    Route::post('contracts/{contract}/move-step', [ErpContractController::class, 'moveStep']);
    Route::post('contracts/{contract}/close', [ErpContractController::class, 'close']);

    Route::apiResource('contract-addendums', ErpContractAddendumController::class);
    Route::post('contract-addendums/{contractAddendum}/move-step', [ErpContractAddendumController::class, 'moveStep']);
    Route::post('contract-addendums/{contractAddendum}/close', [ErpContractAddendumController::class, 'close']);

    Route::apiResource('documents', ErpDocumentController::class)->only(['index', 'store', 'destroy']);
    Route::apiResource('approvals', ErpApprovalController::class)->only(['index', 'store']);
    Route::post('approvals/{approval}/approve', [ErpApprovalController::class, 'approve']);
    Route::post('approvals/{approval}/reject', [ErpApprovalController::class, 'reject']);

    Route::apiResource('comments', ErpCommentController::class)->only(['index', 'store', 'destroy']);
    Route::apiResource('workflows', ErpWorkflowController::class)->only(['index', 'show']);
});
