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
        Schema::create('erp_workflows', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->bigIncrements('erp_workflow_id');
            $table->string('erp_workflow_name', 120);
            $table->string('erp_workflow_slug', 100)->unique();
            $table->string('erp_workflow_description', 255)->nullable();
            $table->boolean('erp_workflow_is_active')->default(true);
            $table->timestamp('erp_workflow_created_at')->nullable();
            $table->timestamp('erp_workflow_updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erp_workflows');
    }
};
