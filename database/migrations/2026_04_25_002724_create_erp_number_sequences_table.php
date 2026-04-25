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
        Schema::create('erp_number_sequences', function (Blueprint $table) {
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_general_ci';

            $table->bigIncrements('erp_number_sequence_id');
            $table->string('erp_number_sequence_key', 100)->unique();
            $table->string('erp_number_sequence_prefix', 30);
            $table->unsignedBigInteger('erp_number_sequence_current_number')->default(0);
            $table->unsignedInteger('erp_number_sequence_padding')->default(4);
            $table->timestamp('erp_number_sequence_created_at')->nullable();
            $table->timestamp('erp_number_sequence_updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erp_number_sequences');
    }
};
