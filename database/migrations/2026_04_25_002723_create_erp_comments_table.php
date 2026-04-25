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
        Schema::create('erp_comments', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->bigIncrements('erp_comment_id');
            $table->unsignedBigInteger('erp_comment_process_id')->nullable();
            $table->string('erp_comment_reference_type', 80)->nullable();
            $table->unsignedBigInteger('erp_comment_reference_id')->nullable();
            $table->text('erp_comment_body');
            $table->unsignedBigInteger('erp_comment_created_by_workspace_user_id')->nullable();
            $table->timestamp('erp_comment_created_at')->nullable();
            $table->timestamp('erp_comment_updated_at')->nullable();

            $table->foreign('erp_comment_process_id')
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
        Schema::dropIfExists('erp_comments');
    }
};
