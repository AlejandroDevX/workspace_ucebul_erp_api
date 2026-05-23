<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ErpExternalSignatureService;
use App\Services\ErpNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class ErpPublicSignatureController extends Controller
{
    public function __construct(
        protected ErpExternalSignatureService $signatureService,
        protected ErpNotificationService $notificationService,
    ) {}

    public function show(string $token): JsonResponse
    {
        try {
            $signatureRequest = $this->signatureService->getByToken($token);
        } catch (RuntimeException) {
            return $this->errorResponse('Signature request not found.', [], Response::HTTP_NOT_FOUND);
        }

        if (in_array($signatureRequest->erp_external_signature_request_status, ['cancelled', 'expired'])) {
            return $this->errorResponse('This signature link is no longer valid.', [], Response::HTTP_GONE);
        }

        if ($signatureRequest->erp_external_signature_request_expires_at?->isPast()) {
            $signatureRequest->update(['erp_external_signature_request_status' => 'expired']);

            return $this->errorResponse('This signature link has expired.', [], Response::HTTP_GONE);
        }

        $this->signatureService->markViewed($signatureRequest);

        return $this->successResponse($signatureRequest->load(['document', 'thirdParty']), 'Signature request retrieved successfully');
    }

    public function sign(Request $request, string $token): JsonResponse
    {
        try {
            $signatureRequest = $this->signatureService->getByToken($token);
        } catch (RuntimeException) {
            return $this->errorResponse('Signature request not found.', [], Response::HTTP_NOT_FOUND);
        }

        $validated = $request->validate([
            'signature_image_url' => ['required', 'string'],
        ]);

        try {
            $signature = $this->signatureService->sign(
                signatureRequest: $signatureRequest,
                signatureImageUrl: $validated['signature_image_url'],
                ipAddress: $request->ip() ?? '',
                userAgent: $request->userAgent() ?? '',
            );
        } catch (RuntimeException $e) {
            return $this->errorResponse($e->getMessage(), [], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $signerName = $signatureRequest->erp_external_signature_request_signer_name ?? 'El firmante';

        $this->notificationService->notifySignatureCompleted(
            process: $signatureRequest->process,
            signerName: $signerName,
            referenceType: $signatureRequest->erp_external_signature_request_reference_type,
            referenceId: $signatureRequest->erp_external_signature_request_reference_id,
        );

        return $this->successResponse($signature, 'Document signed successfully', Response::HTTP_CREATED);
    }
}
