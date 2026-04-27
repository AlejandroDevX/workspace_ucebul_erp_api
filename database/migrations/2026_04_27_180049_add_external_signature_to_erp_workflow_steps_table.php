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
        Schema::table('erp_workflow_steps', function (Blueprint $table) {
            $table->boolean('erp_workflow_step_requires_external_signature')->default(false)->after('erp_workflow_step_is_final');
        });
    }

    public function down(): void
    {
        Schema::table('erp_workflow_steps', function (Blueprint $table) {
            $table->dropColumn('erp_workflow_step_requires_external_signature');
        });
    }
};
