<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ErpExternalSignatureRequest extends Model
{
    protected $table = 'erp_external_signature_requests';

    protected $primaryKey = 'erp_external_signature_request_id';

    public const CREATED_AT = 'erp_external_signature_request_created_at';

    public const UPDATED_AT = 'erp_external_signature_request_updated_at';

    protected $fillable = [
        'erp_external_signature_request_process_id',
        'erp_external_signature_request_reference_type',
        'erp_external_signature_request_reference_id',
        'erp_external_signature_request_third_party_id',
        'erp_external_signature_request_document_id',
        'erp_external_signature_request_token_hash',
        'erp_external_signature_request_public_token',
        'erp_external_signature_request_signer_name',
        'erp_external_signature_request_signer_email',
        'erp_external_signature_request_signer_document_number',
        'erp_external_signature_request_status',
        'erp_external_signature_request_expires_at',
        'erp_external_signature_request_viewed_at',
        'erp_external_signature_request_signed_at',
        'erp_external_signature_request_cancelled_at',
        'erp_external_signature_request_created_by_workspace_user_id',
    ];

    protected $hidden = [
        'erp_external_signature_request_token_hash',
    ];

    protected function casts(): array
    {
        return [
            'erp_external_signature_request_expires_at' => 'datetime',
            'erp_external_signature_request_viewed_at' => 'datetime',
            'erp_external_signature_request_signed_at' => 'datetime',
            'erp_external_signature_request_cancelled_at' => 'datetime',
        ];
    }

    public function process(): BelongsTo
    {
        return $this->belongsTo(ErpProcess::class, 'erp_external_signature_request_process_id', 'erp_process_id');
    }

    public function thirdParty(): BelongsTo
    {
        return $this->belongsTo(ErpThirdParty::class, 'erp_external_signature_request_third_party_id', 'erp_third_party_id');
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(ErpDocument::class, 'erp_external_signature_request_document_id', 'erp_document_id');
    }

    public function signatures(): HasMany
    {
        return $this->hasMany(ErpContractSignature::class, 'erp_contract_signature_signature_request_id', 'erp_external_signature_request_id');
    }
}
