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
        Schema::create('erp_contracts', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->bigIncrements('erp_contract_id');
            $table->unsignedBigInteger('erp_contract_process_id');
            $table->unsignedBigInteger('erp_contract_company_id')->nullable();
            $table->unsignedBigInteger('erp_contract_contractor_id');
            $table->string('erp_contract_code', 80)->unique();
            $table->string('erp_contract_title', 180);
            $table->text('erp_contract_object');
            $table->string('erp_contract_contract_type', 100)->nullable();
            $table->date('erp_contract_start_date')->nullable();
            $table->date('erp_contract_end_date')->nullable();
            $table->decimal('erp_contract_total_value', 15, 2)->default(0);
            $table->string('erp_contract_payment_method', 120)->nullable();
            $table->unsignedBigInteger('erp_contract_supervisor_workspace_user_id')->nullable();
            $table->enum('erp_contract_status', [
                'draft',
                'contractor_data',
                'documents_uploaded',
                'documentation_review',
                'legal_review',
                'pending_approval',
                'approved',
                'signed',
                'active',
                'closed',
                'cancelled',
            ])->default('draft');
            $table->unsignedBigInteger('erp_contract_created_by_workspace_user_id')->nullable();
            $table->timestamp('erp_contract_created_at')->nullable();
            $table->timestamp('erp_contract_updated_at')->nullable();

            $table->foreign('erp_contract_process_id')
                ->references('erp_process_id')
                ->on('erp_processes')
                ->cascadeOnDelete();

            $table->foreign('erp_contract_company_id')
                ->references('erp_company_id')
                ->on('erp_companies')
                ->nullOnDelete();

            $table->foreign('erp_contract_contractor_id')
                ->references('erp_third_party_id')
                ->on('erp_third_parties')
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erp_contracts');
    }
};
