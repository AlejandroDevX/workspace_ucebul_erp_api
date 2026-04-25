<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ErpWorkflow extends Model
{
    use HasFactory;

    protected $table = 'erp_workflows';

    protected $primaryKey = 'erp_workflow_id';

    public const CREATED_AT = 'erp_workflow_created_at';

    public const UPDATED_AT = 'erp_workflow_updated_at';

    protected $fillable = [
        'erp_workflow_name',
        'erp_workflow_slug',
        'erp_workflow_description',
        'erp_workflow_is_active',
    ];

    protected function casts(): array
    {
        return [
            'erp_workflow_is_active' => 'boolean',
        ];
    }

    public function steps(): HasMany
    {
        return $this->hasMany(ErpWorkflowStep::class, 'erp_workflow_step_workflow_id', 'erp_workflow_id')
            ->orderBy('erp_workflow_step_order');
    }

    public function processes(): HasMany
    {
        return $this->hasMany(ErpProcess::class, 'erp_process_workflow_id', 'erp_workflow_id');
    }
}
