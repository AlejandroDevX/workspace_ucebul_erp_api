<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ErpContract;
use App\Models\ErpContractAddendum;
use App\Models\ErpDocument;
use App\Models\ErpProcess;
use App\Services\ErpProcessEventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ErpDocumentController extends Controller
{
    public function __construct(
        protected ErpProcessEventService $processEventService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $documents = ErpDocument::query()
            ->with('process')
            ->when(
                $request->filled('erp_document_process_id'),
                fn ($query) => $query->where('erp_document_process_id', $request->integer('erp_document_process_id'))
            )
            ->when(
                $request->filled('erp_document_reference_type'),
                fn ($query) => $query->where('erp_document_reference_type', $request->string('erp_document_reference_type'))
            )
            ->when(
                $request->filled('erp_document_reference_id'),
                fn ($query) => $query->where('erp_document_reference_id', $request->integer('erp_document_reference_id'))
            )
            ->orderByDesc('erp_document_id')
            ->get();

        return $this->successResponse($documents, 'ERP documents retrieved successfully');
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'erp_document_process_id' => ['nullable', 'integer', 'exists:erp_processes,erp_process_id'],
            'erp_document_reference_type' => ['nullable', 'in:contract,contract_addendum'],
            'erp_document_reference_id' => ['nullable', 'integer', 'min:1'],
            'erp_document_type' => ['required', 'string', 'max:100'],
            'erp_document_name' => ['required', 'string', 'max:180'],
            'erp_document_file_url' => ['required', 'string', 'max:500'],
            'erp_document_file_mime_type' => ['nullable', 'string', 'max:120'],
            'erp_document_file_size' => ['nullable', 'integer', 'min:0'],
            'erp_document_uploaded_by_workspace_user_id' => ['nullable', 'integer', 'min:1'],
        ]);

        if (($validated['erp_document_reference_type'] ?? null) === 'contract') {
            ErpContract::query()->findOrFail($validated['erp_document_reference_id']);
        }

        if (($validated['erp_document_reference_type'] ?? null) === 'contract_addendum') {
            ErpContractAddendum::query()->findOrFail($validated['erp_document_reference_id']);
        }

        $validated['erp_document_uploaded_by_workspace_user_id'] = $validated['erp_document_uploaded_by_workspace_user_id']
            ?? currentWorkspaceUserId($request, $validated);

        $document = ErpDocument::query()->create($validated)->load('process');

        if ($document->process instanceof ErpProcess) {
            $this->processEventService->logDocumentUploaded(
                process: $document->process,
                documentName: $document->erp_document_name,
                workspaceUserId: $document->erp_document_uploaded_by_workspace_user_id,
                description: $document->erp_document_type,
            );
        }

        return $this->successResponse($document, 'ERP document created successfully', Response::HTTP_CREATED);
    }

    public function destroy(ErpDocument $document): JsonResponse
    {
        $document->delete();

        return $this->successResponse(null, 'ERP document deleted successfully');
    }
}
