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
        Schema::create('erp_process_events', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->bigIncrements('erp_process_event_id');
            $table->unsignedBigInteger('erp_process_event_process_id');
            $table->unsignedBigInteger('erp_process_event_step_id')->nullable();
            $table->string('erp_process_event_type', 80);
            $table->string('erp_process_event_title', 180);
            $table->text('erp_process_event_description')->nullable();
            $table->string('erp_process_event_old_status', 80)->nullable();
            $table->string('erp_process_event_new_status', 80)->nullable();
            $table->unsignedBigInteger('erp_process_event_created_by_workspace_user_id')->nullable();
            $table->timestamp('erp_process_event_created_at')->nullable();

            $table->foreign('erp_process_event_process_id')
                ->references('erp_process_id')
                ->on('erp_processes')
                ->cascadeOnDelete();

            $table->foreign('erp_process_event_step_id')
                ->references('erp_workflow_step_id')
                ->on('erp_workflow_steps')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erp_process_events');
    }
};
