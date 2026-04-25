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
        Schema::create('erp_documents', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->bigIncrements('erp_document_id');
            $table->unsignedBigInteger('erp_document_process_id')->nullable();
            $table->string('erp_document_reference_type', 80)->nullable();
            $table->unsignedBigInteger('erp_document_reference_id')->nullable();
            $table->string('erp_document_type', 100);
            $table->string('erp_document_name', 180);
            $table->string('erp_document_file_url', 500);
            $table->string('erp_document_file_mime_type', 120)->nullable();
            $table->unsignedBigInteger('erp_document_file_size')->nullable();
            $table->unsignedBigInteger('erp_document_uploaded_by_workspace_user_id')->nullable();
            $table->timestamp('erp_document_created_at')->nullable();
            $table->timestamp('erp_document_updated_at')->nullable();

            $table->foreign('erp_document_process_id')
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
        Schema::dropIfExists('erp_documents');
    }
};
