<?php

namespace App\Services;

use App\Models\ErpContractSignature;
use App\Models\ErpExternalSignatureRequest;
use App\Models\ErpProcess;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use RuntimeException;

class ErpExternalSignatureService
{
    public function createSignatureRequest(
        ErpProcess $process,
        string $referenceType,
        int $referenceId,
        int $thirdPartyId,
        ?int $documentId = null,
        ?string $signerName = null,
        ?string $signerEmail = null,
        ?string $signerDocumentNumber = null,
        ?int $createdByWorkspaceUserId = null,
        int $expiresInDays = 7,
    ): ErpExternalSignatureRequest {
        $token = Str::random(64);
        $tokenHash = hash('sha256', $token);
        $publicToken = Str::random(32);

        return ErpExternalSignatureRequest::query()->create([
            'erp_external_signature_request_process_id' => $process->erp_process_id,
            'erp_external_signature_request_reference_type' => $referenceType,
            'erp_external_signature_request_reference_id' => $referenceId,
            'erp_external_signature_request_third_party_id' => $thirdPartyId,
            'erp_external_signature_request_document_id' => $documentId,
            'erp_external_signature_request_token_hash' => $tokenHash,
            'erp_external_signature_request_public_token' => $publicToken,
            'erp_external_signature_request_signer_name' => $signerName,
            'erp_external_signature_request_signer_email' => $signerEmail,
            'erp_external_signature_request_signer_document_number' => $signerDocumentNumber,
            'erp_external_signature_request_status' => 'pending',
            'erp_external_signature_request_expires_at' => Carbon::now()->addDays($expiresInDays),
            'erp_external_signature_request_created_by_workspace_user_id' => $createdByWorkspaceUserId,
        ]);
    }

    public function getByToken(string $token): ErpExternalSignatureRequest
    {
        $tokenHash = hash('sha256', $token);

        $request = ErpExternalSignatureRequest::query()
            ->where('erp_external_signature_request_token_hash', $tokenHash)
            ->with(['document', 'thirdParty'])
            ->first();

        if (! $request instanceof ErpExternalSignatureRequest) {
            throw new RuntimeException('Signature request not found.');
        }

        return $request;
    }

    public function markViewed(ErpExternalSignatureRequest $request): void
    {
        if ($request->erp_external_signature_request_status === 'pending') {
            $request->update([
                'erp_external_signature_request_status' => 'viewed',
                'erp_external_signature_request_viewed_at' => Carbon::now(),
            ]);
        }
    }

    public function sign(
        ErpExternalSignatureRequest $signatureRequest,
        string $signatureImageUrl,
        string $ipAddress,
        string $userAgent,
    ): ErpContractSignature {
        if ($signatureRequest->erp_external_signature_request_status === 'signed') {
            throw new RuntimeException('This signature request has already been signed.');
        }

        if ($signatureRequest->erp_external_signature_request_status === 'cancelled') {
            throw new RuntimeException('This signature request has been cancelled.');
        }

        if ($signatureRequest->erp_external_signature_request_expires_at?->isPast()) {
            $signatureRequest->update(['erp_external_signature_request_status' => 'expired']);
            throw new RuntimeException('This signature request has expired.');
        }

        $signature = ErpContractSignature::query()->create([
            'erp_contract_signature_signature_request_id' => $signatureRequest->erp_external_signature_request_id,
            'erp_contract_signature_process_id' => $signatureRequest->erp_external_signature_request_process_id,
            'erp_contract_signature_reference_type' => $signatureRequest->erp_external_signature_request_reference_type,
            'erp_contract_signature_reference_id' => $signatureRequest->erp_external_signature_request_reference_id,
            'erp_contract_signature_third_party_id' => $signatureRequest->erp_external_signature_request_third_party_id,
            'erp_contract_signature_image_url' => $signatureImageUrl,
            'erp_contract_signature_ip_address' => $ipAddress,
            'erp_contract_signature_user_agent' => $userAgent,
            'erp_contract_signature_signed_at' => Carbon::now(),
        ]);

        $signatureRequest->update([
            'erp_external_signature_request_status' => 'signed',
            'erp_external_signature_request_signed_at' => Carbon::now(),
        ]);

        return $signature;
    }
}
