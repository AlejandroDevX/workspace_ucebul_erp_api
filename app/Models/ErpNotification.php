<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ErpNotification extends Model
{
    protected $table = 'erp_notifications';

    protected $primaryKey = 'erp_notification_id';

    public const CREATED_AT = 'erp_notification_created_at';

    public const UPDATED_AT = null;

    protected $fillable = [
        'erp_notification_workspace_user_id',
        'erp_notification_target_role_name',
        'erp_notification_process_id',
        'erp_notification_reference_type',
        'erp_notification_reference_id',
        'erp_notification_type',
        'erp_notification_title',
        'erp_notification_message',
        'erp_notification_action_url',
        'erp_notification_metadata',
        'erp_notification_created_by_workspace_user_id',
        'erp_notification_read_at',
    ];

    protected function casts(): array
    {
        return [
            'erp_notification_metadata' => 'array',
            'erp_notification_read_at' => 'datetime',
            'erp_notification_created_at' => 'datetime',
        ];
    }

    public function process(): BelongsTo
    {
        return $this->belongsTo(ErpProcess::class, 'erp_notification_process_id', 'erp_process_id');
    }
}
