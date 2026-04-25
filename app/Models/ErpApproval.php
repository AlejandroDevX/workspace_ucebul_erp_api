<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ErpApproval extends Model
{
    use HasFactory;

    protected $table = 'erp_approvals';

    protected $primaryKey = 'erp_approval_id';

    public const CREATED_AT = 'erp_approval_created_at';

    public const UPDATED_AT = 'erp_approval_updated_at';

    protected $fillable = [
        'erp_approval_process_id',
        'erp_approval_step_id',
        'erp_approval_reference_type',
        'erp_approval_reference_id',
        'erp_approval_status',
        'erp_approval_requested_by_workspace_user_id',
        'erp_approval_approved_by_workspace_user_id',
        'erp_approval_comment',
        'erp_approval_requested_at',
        'erp_approval_resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'erp_approval_requested_at' => 'datetime',
            'erp_approval_resolved_at' => 'datetime',
        ];
    }

    public function process(): BelongsTo
    {
        return $this->belongsTo(ErpProcess::class, 'erp_approval_process_id', 'erp_process_id');
    }

    public function step(): BelongsTo
    {
        return $this->belongsTo(ErpWorkflowStep::class, 'erp_approval_step_id', 'erp_workflow_step_id');
    }
}
