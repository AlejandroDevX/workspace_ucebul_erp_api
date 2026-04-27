<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ErpComment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ErpCommentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $comments = ErpComment::query()
            ->with('process')
            ->when(
                $request->filled('erp_comment_process_id'),
                fn ($query) => $query->where('erp_comment_process_id', $request->integer('erp_comment_process_id'))
            )
            ->when(
                $request->filled('erp_comment_reference_type'),
                fn ($query) => $query->where('erp_comment_reference_type', $request->string('erp_comment_reference_type'))
            )
            ->when(
                $request->filled('erp_comment_reference_id'),
                fn ($query) => $query->where('erp_comment_reference_id', $request->integer('erp_comment_reference_id'))
            )
            ->orderByDesc('erp_comment_id')
            ->get();

        return $this->successResponse($comments, 'ERP comments retrieved successfully');
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'erp_comment_process_id' => ['nullable', 'integer', 'exists:erp_processes,erp_process_id'],
            'erp_comment_reference_type' => ['nullable', 'string', 'max:80'],
            'erp_comment_reference_id' => ['nullable', 'integer', 'min:1'],
            'erp_comment_body' => ['required', 'string'],
        ]);

        $validated['erp_comment_created_by_workspace_user_id'] = currentWorkspaceUserId();

        $comment = ErpComment::query()->create($validated)->load('process');

        return $this->successResponse($comment, 'ERP comment created successfully', Response::HTTP_CREATED);
    }

    public function destroy(ErpComment $comment): JsonResponse
    {
        $comment->delete();

        return $this->successResponse(null, 'ERP comment deleted successfully');
    }
}
