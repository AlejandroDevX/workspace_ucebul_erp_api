<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ErpContract extends Model
{
    use HasFactory;

    protected $table = 'erp_contracts';

    protected $primaryKey = 'erp_contract_id';

    public const CREATED_AT = 'erp_contract_created_at';

    public const UPDATED_AT = 'erp_contract_updated_at';

    protected $fillable = [
        'erp_contract_process_id',
        'erp_contract_company_id',
        'erp_contract_contractor_id',
        'erp_contract_code',
        'erp_contract_title',
        'erp_contract_object',
        'erp_contract_contract_type',
        'erp_contract_start_date',
        'erp_contract_end_date',
        'erp_contract_total_value',
        'erp_contract_payment_method',
        'erp_contract_supervisor_workspace_user_id',
        'erp_contract_status',
        'erp_contract_pdf_document_id',
        'erp_contract_signed_pdf_document_id',
        'erp_contract_created_by_workspace_user_id',
    ];

    protected function casts(): array
    {
        return [
            'erp_contract_start_date' => 'date',
            'erp_contract_end_date' => 'date',
            'erp_contract_total_value' => 'decimal:2',
        ];
    }

    public function process(): BelongsTo
    {
        return $this->belongsTo(ErpProcess::class, 'erp_contract_process_id', 'erp_process_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(ErpCompany::class, 'erp_contract_company_id', 'erp_company_id');
    }

    public function contractor(): BelongsTo
    {
        return $this->belongsTo(ErpThirdParty::class, 'erp_contract_contractor_id', 'erp_third_party_id');
    }

    public function addendums(): HasMany
    {
        return $this->hasMany(ErpContractAddendum::class, 'erp_contract_addendum_contract_id', 'erp_contract_id');
    }

    public function pdfDocument(): BelongsTo
    {
        return $this->belongsTo(ErpDocument::class, 'erp_contract_pdf_document_id', 'erp_document_id');
    }

    public function signedPdfDocument(): BelongsTo
    {
        return $this->belongsTo(ErpDocument::class, 'erp_contract_signed_pdf_document_id', 'erp_document_id');
    }
}
