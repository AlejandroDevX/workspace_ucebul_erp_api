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
        Schema::table('erp_process_events', function (Blueprint $table) {
            $table->unsignedBigInteger('erp_process_event_from_step_id')->nullable()->after('erp_process_event_step_id');
            $table->unsignedBigInteger('erp_process_event_to_step_id')->nullable()->after('erp_process_event_from_step_id');
            $table->unsignedBigInteger('erp_process_event_from_workspace_user_id')->nullable()->after('erp_process_event_to_step_id');
            $table->unsignedBigInteger('erp_process_event_to_workspace_user_id')->nullable()->after('erp_process_event_from_workspace_user_id');
            $table->string('erp_process_event_to_role_name', 120)->nullable()->after('erp_process_event_to_workspace_user_id');

            $table->foreign('erp_process_event_from_step_id')
                ->references('erp_workflow_step_id')
                ->on('erp_workflow_steps')
                ->nullOnDelete();

            $table->foreign('erp_process_event_to_step_id')
                ->references('erp_workflow_step_id')
                ->on('erp_workflow_steps')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('erp_process_events', function (Blueprint $table) {
            $table->dropForeign(['erp_process_event_from_step_id']);
            $table->dropForeign(['erp_process_event_to_step_id']);
            $table->dropColumn([
                'erp_process_event_from_step_id',
                'erp_process_event_to_step_id',
                'erp_process_event_from_workspace_user_id',
                'erp_process_event_to_workspace_user_id',
                'erp_process_event_to_role_name',
            ]);
        });
    }
};
