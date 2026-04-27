<?php

namespace App\Services;

use App\Models\ErpDocument;
use App\Models\ErpThirdParty;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class ErpContractorDocumentService
{
    /**
     * @return array<string, string> Document type slug → human label
     */
    public function requiredDocumentTypes(): array
    {
        return [
            'contractor_resume' => 'Hoja de vida',
            'contractor_id_card_pdf' => 'Cédula en PDF',
            'contractor_bank_certificate' => 'Certificación bancaria',
            'contractor_arl_certificate' => 'Certificado ARL',
            'contractor_eps_certificate' => 'Certificado EPS',
        ];
    }

    /**
     * @return array{third_party_id: int, is_complete: bool, loaded_count: int, required_count: int, required_documents: list<array{type: string, label: string, is_uploaded: bool, document: ErpDocument|null}>}
     */
    public function getRequiredDocumentsStatus(ErpThirdParty $thirdParty): array
    {
        $requiredTypes = $this->requiredDocumentTypes();

        $uploadedDocuments = ErpDocument::query()
            ->where('erp_document_reference_type', 'third_party')
            ->where('erp_document_reference_id', $thirdParty->erp_third_party_id)
            ->whereIn('erp_document_type', array_keys($requiredTypes))
            ->get()
            ->keyBy('erp_document_type');

        $requiredDocuments = [];

        foreach ($requiredTypes as $type => $label) {
            $document = $uploadedDocuments->get($type);

            $requiredDocuments[] = [
                'type' => $type,
                'label' => $label,
                'is_uploaded' => $document !== null,
                'document' => $document,
            ];
        }

        $loadedCount = $uploadedDocuments->count();
        $requiredCount = count($requiredDocuments);

        return [
            'third_party_id' => $thirdParty->erp_third_party_id,
            'is_complete' => $loadedCount === $requiredCount,
            'loaded_count' => $loadedCount,
            'required_count' => $requiredCount,
            'required_documents' => $requiredDocuments,
        ];
    }

    /**
     * Upload (or replace) a required document for a contractor.
     * Deletes the existing file and DB record before saving the new one.
     */
    public function uploadRequiredDocument(
        ErpThirdParty $thirdParty,
        string $documentType,
        UploadedFile $file,
        ?int $workspaceUserId = null,
    ): ErpDocument {
        $storagePath = $this->storagePath($thirdParty, $documentType);

        // Replace existing file on disk
        if (Storage::disk('public')->exists($storagePath)) {
            Storage::disk('public')->delete($storagePath);
        }

        // Replace existing DB record
        ErpDocument::query()
            ->where('erp_document_reference_type', 'third_party')
            ->where('erp_document_reference_id', $thirdParty->erp_third_party_id)
            ->where('erp_document_type', $documentType)
            ->delete();

        // Store new file
        $file->storeAs(
            "erp/documents/contractors/{$thirdParty->erp_third_party_id}",
            "{$documentType}.pdf",
            'public',
        );

        return ErpDocument::query()->create([
            'erp_document_reference_type' => 'third_party',
            'erp_document_reference_id' => $thirdParty->erp_third_party_id,
            'erp_document_type' => $documentType,
            'erp_document_name' => $file->getClientOriginalName(),
            'erp_document_file_url' => Storage::disk('public')->url($storagePath),
            'erp_document_file_mime_type' => $file->getMimeType(),
            'erp_document_file_size' => $file->getSize(),
            'erp_document_uploaded_by_workspace_user_id' => $workspaceUserId,
        ]);
    }

    /**
     * Delete a required document (file + DB record) for a contractor.
     */
    public function deleteRequiredDocument(ErpThirdParty $thirdParty, string $documentType): void
    {
        $storagePath = $this->storagePath($thirdParty, $documentType);

        if (Storage::disk('public')->exists($storagePath)) {
            Storage::disk('public')->delete($storagePath);
        }

        ErpDocument::query()
            ->where('erp_document_reference_type', 'third_party')
            ->where('erp_document_reference_id', $thirdParty->erp_third_party_id)
            ->where('erp_document_type', $documentType)
            ->delete();
    }

    /**
     * Throw a 422 HttpResponseException if any required documents are missing.
     *
     * @throws HttpResponseException
     */
    public function validateRequiredDocumentsOrFail(ErpThirdParty $thirdParty): void
    {
        $missing = $this->getMissingDocumentLabels($thirdParty);

        if (! empty($missing)) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => 'Contractor required documents are incomplete',
                'errors' => [
                    'required_documents' => $missing,
                ],
            ], Response::HTTP_UNPROCESSABLE_ENTITY));
        }
    }

    /**
     * @return list<string> Labels of document types not yet uploaded
     */
    public function getMissingDocumentLabels(ErpThirdParty $thirdParty): array
    {
        $requiredTypes = $this->requiredDocumentTypes();

        $uploadedTypes = ErpDocument::query()
            ->where('erp_document_reference_type', 'third_party')
            ->where('erp_document_reference_id', $thirdParty->erp_third_party_id)
            ->whereIn('erp_document_type', array_keys($requiredTypes))
            ->pluck('erp_document_type')
            ->all();

        $missing = [];

        foreach ($requiredTypes as $type => $label) {
            if (! in_array($type, $uploadedTypes, strict: true)) {
                $missing[] = $label;
            }
        }

        return $missing;
    }

    private function storagePath(ErpThirdParty $thirdParty, string $documentType): string
    {
        return "erp/documents/contractors/{$thirdParty->erp_third_party_id}/{$documentType}.pdf";
    }
}
