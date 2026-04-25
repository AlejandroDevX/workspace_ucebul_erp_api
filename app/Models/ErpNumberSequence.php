<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ErpNumberSequence extends Model
{
    use HasFactory;

    protected $table = 'erp_number_sequences';

    protected $primaryKey = 'erp_number_sequence_id';

    public const CREATED_AT = 'erp_number_sequence_created_at';

    public const UPDATED_AT = 'erp_number_sequence_updated_at';

    protected $fillable = [
        'erp_number_sequence_key',
        'erp_number_sequence_prefix',
        'erp_number_sequence_current_number',
        'erp_number_sequence_padding',
    ];

    protected function casts(): array
    {
        return [
            'erp_number_sequence_current_number' => 'integer',
            'erp_number_sequence_padding' => 'integer',
        ];
    }
}
