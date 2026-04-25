<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ErpThirdParty extends Model
{
    use HasFactory;

    protected $table = 'erp_third_parties';

    protected $primaryKey = 'erp_third_party_id';

    public const CREATED_AT = 'erp_third_party_created_at';

    public const UPDATED_AT = 'erp_third_party_updated_at';

    protected $fillable = [
        'erp_third_party_type',
        'erp_third_party_name',
        'erp_third_party_document_type',
        'erp_third_party_document_number',
        'erp_third_party_email',
        'erp_third_party_phone',
        'erp_third_party_address',
        'erp_third_party_bank_name',
        'erp_third_party_bank_account_type',
        'erp_third_party_bank_account_number',
        'erp_third_party_is_active',
        'erp_third_party_created_by_workspace_user_id',
    ];

    protected function casts(): array
    {
        return [
            'erp_third_party_is_active' => 'boolean',
        ];
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(ErpContract::class, 'erp_contract_contractor_id', 'erp_third_party_id');
    }
}
