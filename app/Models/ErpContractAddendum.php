<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ErpContractAddendum extends Model
{
    use HasFactory;

    protected $table = 'erp_contract_addendums';

    protected $primaryKey = 'erp_contract_addendum_id';

    public const CREATED_AT = 'erp_contract_addendum_created_at';

    public const UPDATED_AT = 'erp_contract_addendum_updated_at';

    protected $fillable = [
        'erp_contract_addendum_process_id',
        'erp_contract_addendum_contract_id',
        'erp_contract_addendum_code',
        'erp_contract_addendum_type',
        'erp_contract_addendum_title',
        'erp_contract_addendum_description',
        'erp_contract_addendum_previous_value',
        'erp_contract_addendum_new_value',
        'erp_contract_addendum_effective_date',
        'erp_contract_addendum_status',
        'erp_contract_addendum_pdf_document_id',
        'erp_contract_addendum_signed_pdf_document_id',
        'erp_contract_addendum_created_by_workspace_user_id',
    ];

    protected function casts(): array
    {
        return [
            'erp_contract_addendum_effective_date' => 'date',
        ];
    }

    public function process(): BelongsTo
    {
        return $this->belongsTo(ErpProcess::class, 'erp_contract_addendum_process_id', 'erp_process_id');
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(ErpContract::class, 'erp_contract_addendum_contract_id', 'erp_contract_id');
    }

    public function pdfDocument(): BelongsTo
    {
        return $this->belongsTo(ErpDocument::class, 'erp_contract_addendum_pdf_document_id', 'erp_document_id');
    }

    public function signedPdfDocument(): BelongsTo
    {
        return $this->belongsTo(ErpDocument::class, 'erp_contract_addendum_signed_pdf_document_id', 'erp_document_id');
    }
}
