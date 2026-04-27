<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ErpContract;
use App\Models\ErpContractAddendum;
use App\Models\ErpDocument;
use App\Models\ErpProcess;
use App\Models\ErpThirdParty;
use App\Services\ErpProcessEventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
            'erp_document_reference_type' => ['nullable', 'in:contract,contract_addendum,third_party'],
            'erp_document_reference_id' => ['nullable', 'integer', 'min:1'],
            'erp_document_type' => ['nullable', 'string', 'max:100'],
            'file' => ['required', 'file', 'mimes:pdf', 'max:10240'],
        ]);

        $referenceType = $validated['erp_document_reference_type'] ?? null;
        $referenceId = $validated['erp_document_reference_id'] ?? null;

        if ($referenceType === 'contract') {
            ErpContract::query()->findOrFail($referenceId);
        }

        if ($referenceType === 'contract_addendum') {
            ErpContractAddendum::query()->findOrFail($referenceId);
        }

        if ($referenceType === 'third_party') {
            ErpThirdParty::query()->findOrFail($referenceId);
        }

        $file = $request->file('file');
        $folder = $referenceType && $referenceId
            ? "erp/documents/{$referenceType}/{$referenceId}"
            : 'erp/documents/misc';
        $storagePath = "{$folder}/".Str::uuid().'.pdf';

        $file->storeAs(
            dirname($storagePath),
            basename($storagePath),
            'public',
        );

        $document = ErpDocument::query()->create([
            'erp_document_process_id' => $validated['erp_document_process_id'] ?? null,
            'erp_document_reference_type' => $referenceType,
            'erp_document_reference_id' => $referenceId,
            'erp_document_type' => $validated['erp_document_type'] ?? 'attachment',
            'erp_document_name' => $file->getClientOriginalName(),
            'erp_document_file_url' => Storage::disk('public')->url($storagePath),
            'erp_document_file_path' => $storagePath,
            'erp_document_file_mime_type' => $file->getMimeType(),
            'erp_document_file_size' => $file->getSize(),
            'erp_document_uploaded_by_workspace_user_id' => currentWorkspaceUserId(),
        ])->load('process');

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
        if ($document->erp_document_file_path && Storage::disk('public')->exists($document->erp_document_file_path)) {
            Storage::disk('public')->delete($document->erp_document_file_path);
        }

        $document->delete();

        return $this->successResponse(null, 'ERP document deleted successfully');
    }
}
