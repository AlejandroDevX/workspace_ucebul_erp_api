<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('erp_external_signature_requests', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->bigIncrements('erp_external_signature_request_id');
            $table->unsignedBigInteger('erp_external_signature_request_process_id');
            $table->string('erp_external_signature_request_reference_type', 80);
            $table->unsignedBigInteger('erp_external_signature_request_reference_id');
            $table->unsignedBigInteger('erp_external_signature_request_third_party_id');
            $table->unsignedBigInteger('erp_external_signature_request_document_id')->nullable();
            $table->string('erp_external_signature_request_token_hash', 255)->unique('esr_token_hash_uq');
            $table->string('erp_external_signature_request_public_token', 120)->nullable();
            $table->string('erp_external_signature_request_signer_name', 180)->nullable();
            $table->string('erp_external_signature_request_signer_email', 150)->nullable();
            $table->string('erp_external_signature_request_signer_document_number', 50)->nullable();
            $table->enum('erp_external_signature_request_status', ['pending', 'viewed', 'signed', 'expired', 'cancelled'])->default('pending');
            $table->timestamp('erp_external_signature_request_expires_at')->nullable();
            $table->timestamp('erp_external_signature_request_viewed_at')->nullable();
            $table->timestamp('erp_external_signature_request_signed_at')->nullable();
            $table->timestamp('erp_external_signature_request_cancelled_at')->nullable();
            $table->unsignedBigInteger('erp_external_signature_request_created_by_workspace_user_id')->nullable();
            $table->timestamp('erp_external_signature_request_created_at')->nullable();
            $table->timestamp('erp_external_signature_request_updated_at')->nullable();

            $table->index('erp_external_signature_request_process_id', 'esr_process_idx');
            $table->index(['erp_external_signature_request_reference_type', 'erp_external_signature_request_reference_id'], 'esr_ref_idx');
            $table->index('erp_external_signature_request_third_party_id', 'esr_third_party_idx');
            $table->index('erp_external_signature_request_status', 'esr_status_idx');
        });

        DB::statement('ALTER TABLE erp_external_signature_requests ADD CONSTRAINT esr_process_fk FOREIGN KEY (erp_external_signature_request_process_id) REFERENCES erp_processes(erp_process_id) ON DELETE CASCADE');
        DB::statement('ALTER TABLE erp_external_signature_requests ADD CONSTRAINT esr_third_party_fk FOREIGN KEY (erp_external_signature_request_third_party_id) REFERENCES erp_third_parties(erp_third_party_id) ON DELETE CASCADE');
        DB::statement('ALTER TABLE erp_external_signature_requests ADD CONSTRAINT esr_document_fk FOREIGN KEY (erp_external_signature_request_document_id) REFERENCES erp_documents(erp_document_id) ON DELETE SET NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erp_external_signature_requests');
    }
};
