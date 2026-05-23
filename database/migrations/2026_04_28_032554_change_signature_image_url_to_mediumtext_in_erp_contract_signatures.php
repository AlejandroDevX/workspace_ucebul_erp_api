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
        Schema::table('erp_contract_signatures', function (Blueprint $table) {
            $table->mediumText('erp_contract_signature_image_url')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('erp_contract_signatures', function (Blueprint $table) {
            $table->string('erp_contract_signature_image_url', 500)->nullable()->change();
        });
    }
};
