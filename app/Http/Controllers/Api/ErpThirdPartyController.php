<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ErpThirdParty;
use App\Services\ErpContractorDocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ErpThirdPartyController extends Controller
{
    public function __construct(
        protected ErpContractorDocumentService $contractorDocumentService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $thirdParties = ErpThirdParty::query()
            ->when(
                $request->filled('erp_third_party_type'),
                fn ($query) => $query->where('erp_third_party_type', $request->string('erp_third_party_type'))
            )
            ->orderByDesc('erp_third_party_id')
            ->get();

        return $this->successResponse($thirdParties, 'ERP third parties retrieved successfully');
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'erp_third_party_type' => ['nullable', 'in:contractor,supplier,customer,other'],
            'erp_third_party_name' => ['required', 'string', 'max:180'],
            'erp_third_party_document_type' => ['nullable', 'string', 'max:30'],
            'erp_third_party_document_number' => ['nullable', 'string', 'max:50'],
            'erp_third_party_email' => ['nullable', 'email', 'max:150'],
            'erp_third_party_phone' => ['nullable', 'string', 'max:50'],
            'erp_third_party_address' => ['nullable', 'string', 'max:255'],
            'erp_third_party_bank_name' => ['nullable', 'string', 'max:120'],
            'erp_third_party_bank_account_type' => ['nullable', 'string', 'max:50'],
            'erp_third_party_bank_account_number' => ['nullable', 'string', 'max:80'],
            'erp_third_party_is_active' => ['nullable', 'boolean'],
        ]);

        $validated['erp_third_party_created_by_workspace_user_id'] = currentWorkspaceUserId();

        $thirdParty = ErpThirdParty::query()->create($validated);

        return $this->successResponse($thirdParty, 'ERP third party created successfully', Response::HTTP_CREATED);
    }

    public function show(ErpThirdParty $thirdParty): JsonResponse
    {
        return $this->successResponse($thirdParty, 'ERP third party retrieved successfully');
    }

    public function update(Request $request, ErpThirdParty $thirdParty): JsonResponse
    {
        $validated = $request->validate([
            'erp_third_party_type' => ['sometimes', 'nullable', 'in:contractor,supplier,customer,other'],
            'erp_third_party_name' => ['sometimes', 'required', 'string', 'max:180'],
            'erp_third_party_document_type' => ['sometimes', 'nullable', 'string', 'max:30'],
            'erp_third_party_document_number' => ['sometimes', 'nullable', 'string', 'max:50'],
            'erp_third_party_email' => ['sometimes', 'nullable', 'email', 'max:150'],
            'erp_third_party_phone' => ['sometimes', 'nullable', 'string', 'max:50'],
            'erp_third_party_address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'erp_third_party_bank_name' => ['sometimes', 'nullable', 'string', 'max:120'],
            'erp_third_party_bank_account_type' => ['sometimes', 'nullable', 'string', 'max:50'],
            'erp_third_party_bank_account_number' => ['sometimes', 'nullable', 'string', 'max:80'],
            'erp_third_party_is_active' => ['sometimes', 'boolean'],
        ]);

        $thirdParty->update($validated);

        return $this->successResponse($thirdParty->fresh(), 'ERP third party updated successfully');
    }

    public function destroy(ErpThirdParty $thirdParty): JsonResponse
    {
        $thirdParty->delete();

        return $this->successResponse(null, 'ERP third party deleted successfully');
    }

    public function requiredDocumentsStatus(ErpThirdParty $thirdParty): JsonResponse
    {
        $status = $this->contractorDocumentService->getRequiredDocumentsStatus($thirdParty);

        return $this->successResponse($status, 'Third party required documents status retrieved successfully');
    }

    public function uploadRequiredDocument(Request $request, ErpThirdParty $thirdParty, string $documentType): JsonResponse
    {
        $allowedTypes = array_keys($this->contractorDocumentService->requiredDocumentTypes());

        $request->validate([
            'file' => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ]);

        if (! in_array($documentType, $allowedTypes, strict: true)) {
            return $this->errorResponse(
                "Invalid document type '{$documentType}'.",
                [],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $document = $this->contractorDocumentService->uploadRequiredDocument(
            thirdParty: $thirdParty,
            documentType: $documentType,
            file: $request->file('file'),
            workspaceUserId: currentWorkspaceUserId(),
        );

        $status = $this->contractorDocumentService->getRequiredDocumentsStatus($thirdParty);

        return $this->successResponse(
            ['document' => $document, 'required_documents_status' => $status],
            'Contractor document uploaded successfully',
            Response::HTTP_CREATED,
        );
    }

    public function deleteRequiredDocument(ErpThirdParty $thirdParty, string $documentType): JsonResponse
    {
        $allowedTypes = array_keys($this->contractorDocumentService->requiredDocumentTypes());

        if (! in_array($documentType, $allowedTypes, strict: true)) {
            return $this->errorResponse(
                "Invalid document type '{$documentType}'.",
                [],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $this->contractorDocumentService->deleteRequiredDocument($thirdParty, $documentType);

        $status = $this->contractorDocumentService->getRequiredDocumentsStatus($thirdParty);

        return $this->successResponse($status, 'Contractor document deleted successfully');
    }
}
