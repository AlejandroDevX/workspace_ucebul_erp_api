<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ErpComment extends Model
{
    use HasFactory;

    protected $table = 'erp_comments';

    protected $primaryKey = 'erp_comment_id';

    public const CREATED_AT = 'erp_comment_created_at';

    public const UPDATED_AT = 'erp_comment_updated_at';

    protected $fillable = [
        'erp_comment_process_id',
        'erp_comment_reference_type',
        'erp_comment_reference_id',
        'erp_comment_body',
        'erp_comment_created_by_workspace_user_id',
    ];

    public function process(): BelongsTo
    {
        return $this->belongsTo(ErpProcess::class, 'erp_comment_process_id', 'erp_process_id');
    }
}
