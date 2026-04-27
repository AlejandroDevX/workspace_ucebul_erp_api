<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ErpNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ErpNotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $workspaceUserId = currentWorkspaceUserId();

        $query = ErpNotification::query()
            ->where(function ($q) use ($workspaceUserId): void {
                $q->where('erp_notification_workspace_user_id', $workspaceUserId)
                    ->orWhereNull('erp_notification_workspace_user_id');
            })
            ->orderByDesc('erp_notification_created_at');

        if ($request->boolean('unread_only')) {
            $query->whereNull('erp_notification_read_at');
        }

        $notifications = $query->limit(50)->get();

        return $this->successResponse($notifications, 'Notifications retrieved successfully');
    }

    public function unreadCount(): JsonResponse
    {
        $workspaceUserId = currentWorkspaceUserId();

        $count = ErpNotification::query()
            ->where(function ($q) use ($workspaceUserId): void {
                $q->where('erp_notification_workspace_user_id', $workspaceUserId)
                    ->orWhereNull('erp_notification_workspace_user_id');
            })
            ->whereNull('erp_notification_read_at')
            ->count();

        return $this->successResponse(['count' => $count], 'Unread count retrieved successfully');
    }

    public function markRead(ErpNotification $notification): JsonResponse
    {
        $notification->update([
            'erp_notification_read_at' => Carbon::now(),
        ]);

        return $this->successResponse($notification, 'Notification marked as read');
    }

    public function markAllRead(): JsonResponse
    {
        $workspaceUserId = currentWorkspaceUserId();

        ErpNotification::query()
            ->where(function ($q) use ($workspaceUserId): void {
                $q->where('erp_notification_workspace_user_id', $workspaceUserId)
                    ->orWhereNull('erp_notification_workspace_user_id');
            })
            ->whereNull('erp_notification_read_at')
            ->update(['erp_notification_read_at' => Carbon::now()]);

        return $this->successResponse(null, 'All notifications marked as read');
    }
}
