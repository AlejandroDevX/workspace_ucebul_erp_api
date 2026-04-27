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
        Schema::table('erp_contracts', function (Blueprint $table) {
            $table->unsignedBigInteger('erp_contract_pdf_document_id')->nullable()->after('erp_contract_status');
            $table->unsignedBigInteger('erp_contract_signed_pdf_document_id')->nullable()->after('erp_contract_pdf_document_id');
        });

        DB::statement('ALTER TABLE erp_contracts ADD CONSTRAINT ec_pdf_doc_fk FOREIGN KEY (erp_contract_pdf_document_id) REFERENCES erp_documents(erp_document_id) ON DELETE SET NULL');
        DB::statement('ALTER TABLE erp_contracts ADD CONSTRAINT ec_signed_pdf_doc_fk FOREIGN KEY (erp_contract_signed_pdf_document_id) REFERENCES erp_documents(erp_document_id) ON DELETE SET NULL');

        DB::statement("ALTER TABLE erp_contracts MODIFY COLUMN erp_contract_status ENUM(
            'draft','contractor_data','documents_uploaded','documentation_review',
            'legal_review','pending_approval','approved','signed','active','closed','cancelled',
            'administrative_review','direction_review','pending_signature'
        ) NOT NULL DEFAULT 'draft'");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE erp_contracts DROP FOREIGN KEY ec_pdf_doc_fk');
        DB::statement('ALTER TABLE erp_contracts DROP FOREIGN KEY ec_signed_pdf_doc_fk');

        Schema::table('erp_contracts', function (Blueprint $table) {
            $table->dropColumn(['erp_contract_pdf_document_id', 'erp_contract_signed_pdf_document_id']);
        });

        DB::statement("ALTER TABLE erp_contracts MODIFY COLUMN erp_contract_status ENUM(
            'draft','contractor_data','documents_uploaded','documentation_review',
            'legal_review','pending_approval','approved','signed','active','closed','cancelled'
        ) NOT NULL DEFAULT 'draft'");
    }
};
