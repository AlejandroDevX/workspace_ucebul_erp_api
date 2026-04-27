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
        Schema::create('erp_contract_signatures', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->bigIncrements('erp_contract_signature_id');
            $table->unsignedBigInteger('erp_contract_signature_signature_request_id');
            $table->unsignedBigInteger('erp_contract_signature_process_id');
            $table->string('erp_contract_signature_reference_type', 80);
            $table->unsignedBigInteger('erp_contract_signature_reference_id');
            $table->unsignedBigInteger('erp_contract_signature_third_party_id');
            $table->string('erp_contract_signature_image_url', 500)->nullable();
            $table->string('erp_contract_signature_image_path', 500)->nullable();
            $table->unsignedBigInteger('erp_contract_signature_signed_pdf_document_id')->nullable();
            $table->string('erp_contract_signature_ip_address', 80)->nullable();
            $table->text('erp_contract_signature_user_agent')->nullable();
            $table->timestamp('erp_contract_signature_signed_at')->nullable();
            $table->timestamp('erp_contract_signature_created_at')->nullable();
            $table->timestamp('erp_contract_signature_updated_at')->nullable();
        });

        DB::statement('ALTER TABLE erp_contract_signatures ADD CONSTRAINT cs_sig_req_fk FOREIGN KEY (erp_contract_signature_signature_request_id) REFERENCES erp_external_signature_requests(erp_external_signature_request_id) ON DELETE CASCADE');
        DB::statement('ALTER TABLE erp_contract_signatures ADD CONSTRAINT cs_process_fk FOREIGN KEY (erp_contract_signature_process_id) REFERENCES erp_processes(erp_process_id) ON DELETE CASCADE');
        DB::statement('ALTER TABLE erp_contract_signatures ADD CONSTRAINT cs_third_party_fk FOREIGN KEY (erp_contract_signature_third_party_id) REFERENCES erp_third_parties(erp_third_party_id) ON DELETE CASCADE');
        DB::statement('ALTER TABLE erp_contract_signatures ADD CONSTRAINT cs_pdf_doc_fk FOREIGN KEY (erp_contract_signature_signed_pdf_document_id) REFERENCES erp_documents(erp_document_id) ON DELETE SET NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erp_contract_signatures');
    }
};
