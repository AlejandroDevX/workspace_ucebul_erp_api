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
        Schema::create('erp_contract_addendums', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->bigIncrements('erp_contract_addendum_id');
            $table->unsignedBigInteger('erp_contract_addendum_process_id');
            $table->unsignedBigInteger('erp_contract_addendum_contract_id');
            $table->string('erp_contract_addendum_code', 80)->unique();
            $table->enum('erp_contract_addendum_type', [
                'date',
                'position',
                'functions',
                'value',
                'payment_method',
                'supervisor',
                'other',
            ])->default('other');
            $table->string('erp_contract_addendum_title', 180);
            $table->text('erp_contract_addendum_description');
            $table->text('erp_contract_addendum_previous_value')->nullable();
            $table->text('erp_contract_addendum_new_value')->nullable();
            $table->date('erp_contract_addendum_effective_date')->nullable();
            $table->enum('erp_contract_addendum_status', [
                'draft',
                'supports_uploaded',
                'legal_review',
                'pending_approval',
                'approved',
                'signed',
                'closed',
                'cancelled',
            ])->default('draft');
            $table->unsignedBigInteger('erp_contract_addendum_created_by_workspace_user_id')->nullable();
            $table->timestamp('erp_contract_addendum_created_at')->nullable();
            $table->timestamp('erp_contract_addendum_updated_at')->nullable();

            $table->foreign('erp_contract_addendum_process_id')
                ->references('erp_process_id')
                ->on('erp_processes')
                ->cascadeOnDelete();

            $table->foreign('erp_contract_addendum_contract_id')
                ->references('erp_contract_id')
                ->on('erp_contracts')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erp_contract_addendums');
    }
};
