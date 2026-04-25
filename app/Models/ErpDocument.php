<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ErpDocument extends Model
{
    use HasFactory;

    protected $table = 'erp_documents';

    protected $primaryKey = 'erp_document_id';

    public const CREATED_AT = 'erp_document_created_at';

    public const UPDATED_AT = 'erp_document_updated_at';

    protected $fillable = [
        'erp_document_process_id',
        'erp_document_reference_type',
        'erp_document_reference_id',
        'erp_document_type',
        'erp_document_name',
        'erp_document_file_url',
        'erp_document_file_mime_type',
        'erp_document_file_size',
        'erp_document_uploaded_by_workspace_user_id',
    ];

    protected function casts(): array
    {
        return [
            'erp_document_file_size' => 'integer',
        ];
    }

    public function process(): BelongsTo
    {
        return $this->belongsTo(ErpProcess::class, 'erp_document_process_id', 'erp_process_id');
    }
}
