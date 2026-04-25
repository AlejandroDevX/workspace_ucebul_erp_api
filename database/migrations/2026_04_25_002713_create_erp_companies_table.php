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
        Schema::create('erp_companies', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->bigIncrements('erp_company_id');
            $table->string('erp_company_name', 150);
            $table->string('erp_company_document_type', 30)->nullable();
            $table->string('erp_company_document_number', 50)->nullable();
            $table->string('erp_company_email', 150)->nullable();
            $table->string('erp_company_phone', 50)->nullable();
            $table->string('erp_company_address', 255)->nullable();
            $table->boolean('erp_company_is_active')->default(true);
            $table->timestamp('erp_company_created_at')->nullable();
            $table->timestamp('erp_company_updated_at')->nullable();

            $table->index('erp_company_document_number', 'erp_company_document_number_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erp_companies');
    }
};
