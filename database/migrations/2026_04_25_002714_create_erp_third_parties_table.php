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
        Schema::create('erp_third_parties', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->bigIncrements('erp_third_party_id');
            $table->enum('erp_third_party_type', ['contractor', 'supplier', 'customer', 'other'])->default('contractor');
            $table->string('erp_third_party_name', 180);
            $table->string('erp_third_party_document_type', 30)->nullable();
            $table->string('erp_third_party_document_number', 50)->nullable();
            $table->string('erp_third_party_email', 150)->nullable();
            $table->string('erp_third_party_phone', 50)->nullable();
            $table->string('erp_third_party_address', 255)->nullable();
            $table->string('erp_third_party_bank_name', 120)->nullable();
            $table->string('erp_third_party_bank_account_type', 50)->nullable();
            $table->string('erp_third_party_bank_account_number', 80)->nullable();
            $table->boolean('erp_third_party_is_active')->default(true);
            $table->unsignedBigInteger('erp_third_party_created_by_workspace_user_id')->nullable();
            $table->timestamp('erp_third_party_created_at')->nullable();
            $table->timestamp('erp_third_party_updated_at')->nullable();

            $table->index('erp_third_party_type', 'erp_third_party_type_idx');
            $table->index('erp_third_party_document_number', 'erp_third_party_document_number_idx');
            $table->index('erp_third_party_created_by_workspace_user_id', 'erp_third_party_created_by_wu_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erp_third_parties');
    }
};
