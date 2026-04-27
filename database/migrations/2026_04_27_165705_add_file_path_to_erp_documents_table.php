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
        Schema::table('erp_documents', function (Blueprint $table) {
            $table->string('erp_document_file_path', 500)->nullable()->after('erp_document_file_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('erp_documents', function (Blueprint $table) {
            $table->dropColumn('erp_document_file_path');
        });
    }
};
