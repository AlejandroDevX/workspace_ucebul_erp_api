<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ErpWorkflowStep extends Model
{
    use HasFactory;

    protected $table = 'erp_workflow_steps';

    protected $primaryKey = 'erp_workflow_step_id';

    public const CREATED_AT = 'erp_workflow_step_created_at';

    public const UPDATED_AT = 'erp_workflow_step_updated_at';

    protected $fillable = [
        'erp_workflow_step_workflow_id',
        'erp_workflow_step_name',
        'erp_workflow_step_slug',
        'erp_workflow_step_description',
        'erp_workflow_step_responsible_role_name',
        'erp_workflow_step_order',
        'erp_workflow_step_requires_approval',
        'erp_workflow_step_is_final',
        'erp_workflow_step_is_active',
    ];

    protected function casts(): array
    {
        return [
            'erp_workflow_step_order' => 'integer',
            'erp_workflow_step_requires_approval' => 'boolean',
            'erp_workflow_step_is_final' => 'boolean',
            'erp_workflow_step_is_active' => 'boolean',
        ];
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(ErpWorkflow::class, 'erp_workflow_step_workflow_id', 'erp_workflow_id');
    }

    public function processes(): HasMany
    {
        return $this->hasMany(ErpProcess::class, 'erp_process_current_step_id', 'erp_workflow_step_id');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(ErpApproval::class, 'erp_approval_step_id', 'erp_workflow_step_id');
    }

    public function processEvents(): HasMany
    {
        return $this->hasMany(ErpProcessEvent::class, 'erp_process_event_step_id', 'erp_workflow_step_id');
    }
}
