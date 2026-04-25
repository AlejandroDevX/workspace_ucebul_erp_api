<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ErpCompany extends Model
{
    use HasFactory;

    protected $table = 'erp_companies';

    protected $primaryKey = 'erp_company_id';

    public const CREATED_AT = 'erp_company_created_at';

    public const UPDATED_AT = 'erp_company_updated_at';

    protected $fillable = [
        'erp_company_name',
        'erp_company_document_type',
        'erp_company_document_number',
        'erp_company_email',
        'erp_company_phone',
        'erp_company_address',
        'erp_company_is_active',
    ];

    protected function casts(): array
    {
        return [
            'erp_company_is_active' => 'boolean',
        ];
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(ErpContract::class, 'erp_contract_company_id', 'erp_company_id');
    }
}
