<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ErpProcessEvent extends Model
{
    use HasFactory;

    protected $table = 'erp_process_events';

    protected $primaryKey = 'erp_process_event_id';

    public const CREATED_AT = 'erp_process_event_created_at';

    public const UPDATED_AT = null;

    protected $fillable = [
        'erp_process_event_process_id',
        'erp_process_event_step_id',
        'erp_process_event_type',
        'erp_process_event_title',
        'erp_process_event_description',
        'erp_process_event_old_status',
        'erp_process_event_new_status',
        'erp_process_event_from_step_id',
        'erp_process_event_to_step_id',
        'erp_process_event_from_workspace_user_id',
        'erp_process_event_to_workspace_user_id',
        'erp_process_event_to_role_name',
        'erp_process_event_created_by_workspace_user_id',
    ];

    public function process(): BelongsTo
    {
        return $this->belongsTo(ErpProcess::class, 'erp_process_event_process_id', 'erp_process_id');
    }

    public function step(): BelongsTo
    {
        return $this->belongsTo(ErpWorkflowStep::class, 'erp_process_event_step_id', 'erp_workflow_step_id');
    }
}
