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
        if (! Schema::hasColumn('erp_contract_addendums', 'erp_contract_addendum_pdf_document_id')) {
            Schema::table('erp_contract_addendums', function (Blueprint $table) {
                $table->unsignedBigInteger('erp_contract_addendum_pdf_document_id')->nullable()->after('erp_contract_addendum_status');
                $table->unsignedBigInteger('erp_contract_addendum_signed_pdf_document_id')->nullable()->after('erp_contract_addendum_pdf_document_id');
            });
        }

        DB::statement('ALTER TABLE erp_contract_addendums ADD CONSTRAINT eca_pdf_doc_fk FOREIGN KEY (erp_contract_addendum_pdf_document_id) REFERENCES erp_documents(erp_document_id) ON DELETE SET NULL');
        DB::statement('ALTER TABLE erp_contract_addendums ADD CONSTRAINT eca_signed_pdf_doc_fk FOREIGN KEY (erp_contract_addendum_signed_pdf_document_id) REFERENCES erp_documents(erp_document_id) ON DELETE SET NULL');

        DB::statement("ALTER TABLE erp_contract_addendums MODIFY COLUMN erp_contract_addendum_status ENUM(
            'draft','supports_uploaded','legal_review','pending_approval','approved',
            'signed','closed','cancelled',
            'administrative_review','direction_review','pending_signature'
        ) NOT NULL DEFAULT 'draft'");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE erp_contract_addendums DROP FOREIGN KEY eca_pdf_doc_fk');
        DB::statement('ALTER TABLE erp_contract_addendums DROP FOREIGN KEY eca_signed_pdf_doc_fk');

        Schema::table('erp_contract_addendums', function (Blueprint $table) {
            $table->dropColumn(['erp_contract_addendum_pdf_document_id', 'erp_contract_addendum_signed_pdf_document_id']);
        });

        DB::statement("ALTER TABLE erp_contract_addendums MODIFY COLUMN erp_contract_addendum_status ENUM(
            'draft','supports_uploaded','legal_review','pending_approval','approved',
            'signed','closed','cancelled'
        ) NOT NULL DEFAULT 'draft'");
    }
};
