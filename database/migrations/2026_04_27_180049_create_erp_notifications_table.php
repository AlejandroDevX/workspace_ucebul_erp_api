<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('erp_notifications', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->bigIncrements('erp_notification_id');
            $table->unsignedBigInteger('erp_notification_workspace_user_id')->nullable();
            $table->string('erp_notification_target_role_name', 120)->nullable();
            $table->unsignedBigInteger('erp_notification_process_id')->nullable();
            $table->string('erp_notification_reference_type', 80)->nullable();
            $table->unsignedBigInteger('erp_notification_reference_id')->nullable();
            $table->string('erp_notification_type', 80);
            $table->string('erp_notification_title', 180);
            $table->text('erp_notification_message')->nullable();
            $table->string('erp_notification_action_url', 500)->nullable();
            $table->json('erp_notification_metadata')->nullable();
            $table->unsignedBigInteger('erp_notification_created_by_workspace_user_id')->nullable();
            $table->timestamp('erp_notification_read_at')->nullable();
            $table->timestamp('erp_notification_created_at')->nullable();

            $table->index('erp_notification_workspace_user_id', 'notif_user_idx');
            $table->index('erp_notification_target_role_name', 'notif_role_idx');
            $table->index('erp_notification_process_id', 'notif_process_idx');
            $table->index(['erp_notification_reference_type', 'erp_notification_reference_id'], 'notif_ref_idx');
            $table->index('erp_notification_type', 'notif_type_idx');
            $table->index('erp_notification_read_at', 'notif_read_idx');
            $table->index('erp_notification_created_at', 'notif_created_idx');

            $table->foreign('erp_notification_process_id')
                ->references('erp_process_id')
                ->on('erp_processes')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erp_notifications');
    }
};
