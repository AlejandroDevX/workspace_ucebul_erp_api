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
        Schema::create('erp_workflow_steps', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->bigIncrements('erp_workflow_step_id');
            $table->unsignedBigInteger('erp_workflow_step_workflow_id');
            $table->string('erp_workflow_step_name', 150);
            $table->string('erp_workflow_step_slug', 120);
            $table->string('erp_workflow_step_description', 255)->nullable();
            $table->string('erp_workflow_step_responsible_role_name', 120)->nullable();
            $table->unsignedInteger('erp_workflow_step_order')->default(0);
            $table->boolean('erp_workflow_step_requires_approval')->default(false);
            $table->boolean('erp_workflow_step_is_final')->default(false);
            $table->boolean('erp_workflow_step_is_active')->default(true);
            $table->timestamp('erp_workflow_step_created_at')->nullable();
            $table->timestamp('erp_workflow_step_updated_at')->nullable();

            $table->unique(
                ['erp_workflow_step_workflow_id', 'erp_workflow_step_slug'],
                'erp_workflow_step_workflow_id_slug_unique'
            );

            $table->foreign('erp_workflow_step_workflow_id')
                ->references('erp_workflow_id')
                ->on('erp_workflows')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erp_workflow_steps');
    }
};
