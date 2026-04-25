<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ErpProcess extends Model
{
    use HasFactory;

    protected $table = 'erp_processes';

    protected $primaryKey = 'erp_process_id';

    public const CREATED_AT = 'erp_process_created_at';

    public const UPDATED_AT = 'erp_process_updated_at';

    protected $fillable = [
        'erp_process_workflow_id',
        'erp_process_current_step_id',
        'erp_process_code',
        'erp_process_title',
        'erp_process_description',
        'erp_process_status',
        'erp_process_priority',
        'erp_process_created_by_workspace_user_id',
        'erp_process_assigned_to_workspace_user_id',
        'erp_process_started_at',
        'erp_process_closed_at',
    ];

    protected function casts(): array
    {
        return [
            'erp_process_started_at' => 'datetime',
            'erp_process_closed_at' => 'datetime',
        ];
    }

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(ErpWorkflow::class, 'erp_process_workflow_id', 'erp_workflow_id');
    }

    public function currentStep(): BelongsTo
    {
        return $this->belongsTo(ErpWorkflowStep::class, 'erp_process_current_step_id', 'erp_workflow_step_id');
    }

    public function contract(): HasOne
    {
        return $this->hasOne(ErpContract::class, 'erp_contract_process_id', 'erp_process_id');
    }

    public function contractAddendum(): HasOne
    {
        return $this->hasOne(ErpContractAddendum::class, 'erp_contract_addendum_process_id', 'erp_process_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ErpDocument::class, 'erp_document_process_id', 'erp_process_id');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(ErpApproval::class, 'erp_approval_process_id', 'erp_process_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(ErpProcessEvent::class, 'erp_process_event_process_id', 'erp_process_id')
            ->orderByDesc('erp_process_event_created_at');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(ErpComment::class, 'erp_comment_process_id', 'erp_process_id')
            ->orderByDesc('erp_comment_created_at');
    }
}
