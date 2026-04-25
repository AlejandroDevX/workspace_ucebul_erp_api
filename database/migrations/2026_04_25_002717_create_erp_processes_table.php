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
        Schema::create('erp_processes', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->bigIncrements('erp_process_id');
            $table->unsignedBigInteger('erp_process_workflow_id');
            $table->unsignedBigInteger('erp_process_current_step_id')->nullable();
            $table->string('erp_process_code', 80)->unique();
            $table->string('erp_process_title', 180);
            $table->text('erp_process_description')->nullable();
            $table->enum('erp_process_status', [
                'draft',
                'in_progress',
                'pending_review',
                'pending_approval',
                'approved',
                'rejected',
                'signed',
                'closed',
                'cancelled',
            ])->default('draft');
            $table->enum('erp_process_priority', ['low', 'medium', 'high'])->default('medium');
            $table->unsignedBigInteger('erp_process_created_by_workspace_user_id')->nullable();
            $table->unsignedBigInteger('erp_process_assigned_to_workspace_user_id')->nullable();
            $table->timestamp('erp_process_started_at')->nullable();
            $table->timestamp('erp_process_closed_at')->nullable();
            $table->timestamp('erp_process_created_at')->nullable();
            $table->timestamp('erp_process_updated_at')->nullable();

            $table->foreign('erp_process_workflow_id')
                ->references('erp_workflow_id')
                ->on('erp_workflows')
                ->restrictOnDelete();

            $table->foreign('erp_process_current_step_id')
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
        Schema::dropIfExists('erp_processes');
    }
};
