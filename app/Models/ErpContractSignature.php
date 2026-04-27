<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ErpContractSignature extends Model
{
    protected $table = 'erp_contract_signatures';

    protected $primaryKey = 'erp_contract_signature_id';

    public const CREATED_AT = 'erp_contract_signature_created_at';

    public const UPDATED_AT = 'erp_contract_signature_updated_at';

    protected $fillable = [
        'erp_contract_signature_signature_request_id',
        'erp_contract_signature_process_id',
        'erp_contract_signature_reference_type',
        'erp_contract_signature_reference_id',
        'erp_contract_signature_third_party_id',
        'erp_contract_signature_image_url',
        'erp_contract_signature_image_path',
        'erp_contract_signature_signed_pdf_document_id',
        'erp_contract_signature_ip_address',
        'erp_contract_signature_user_agent',
        'erp_contract_signature_signed_at',
    ];

    protected function casts(): array
    {
        return [
            'erp_contract_signature_signed_at' => 'datetime',
        ];
    }

    public function signatureRequest(): BelongsTo
    {
        return $this->belongsTo(ErpExternalSignatureRequest::class, 'erp_contract_signature_signature_request_id', 'erp_external_signature_request_id');
    }

    public function process(): BelongsTo
    {
        return $this->belongsTo(ErpProcess::class, 'erp_contract_signature_process_id', 'erp_process_id');
    }

    public function thirdParty(): BelongsTo
    {
        return $this->belongsTo(ErpThirdParty::class, 'erp_contract_signature_third_party_id', 'erp_third_party_id');
    }

    public function signedPdfDocument(): BelongsTo
    {
        return $this->belongsTo(ErpDocument::class, 'erp_contract_signature_signed_pdf_document_id', 'erp_document_id');
    }
}
