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
        Schema::create('erp_approvals', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->bigIncrements('erp_approval_id');
            $table->unsignedBigInteger('erp_approval_process_id');
            $table->unsignedBigInteger('erp_approval_step_id')->nullable();
            $table->string('erp_approval_reference_type', 80)->nullable();
            $table->unsignedBigInteger('erp_approval_reference_id')->nullable();
            $table->enum('erp_approval_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->unsignedBigInteger('erp_approval_requested_by_workspace_user_id')->nullable();
            $table->unsignedBigInteger('erp_approval_approved_by_workspace_user_id')->nullable();
            $table->text('erp_approval_comment')->nullable();
            $table->timestamp('erp_approval_requested_at')->nullable();
            $table->timestamp('erp_approval_resolved_at')->nullable();
            $table->timestamp('erp_approval_created_at')->nullable();
            $table->timestamp('erp_approval_updated_at')->nullable();

            $table->foreign('erp_approval_process_id')
                ->references('erp_process_id')
                ->on('erp_processes')
                ->cascadeOnDelete();

            $table->foreign('erp_approval_step_id')
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
        Schema::dropIfExists('erp_approvals');
    }
};
