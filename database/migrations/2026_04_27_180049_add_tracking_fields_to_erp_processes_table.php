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
        Schema::table('erp_processes', function (Blueprint $table) {
            $table->string('erp_process_current_responsible_role_name', 120)->nullable()->after('erp_process_current_step_id');
            $table->unsignedBigInteger('erp_process_last_moved_by_workspace_user_id')->nullable()->after('erp_process_assigned_to_workspace_user_id');
            $table->timestamp('erp_process_last_moved_at')->nullable()->after('erp_process_last_moved_by_workspace_user_id');
        });

        DB::statement("ALTER TABLE erp_processes MODIFY COLUMN erp_process_status ENUM(
            'draft','in_progress','pending_review','pending_approval','approved',
            'rejected','signed','closed','cancelled','pending_signature'
        ) NOT NULL DEFAULT 'draft'");
    }

    public function down(): void
    {
        Schema::table('erp_processes', function (Blueprint $table) {
            $table->dropColumn([
                'erp_process_current_responsible_role_name',
                'erp_process_last_moved_by_workspace_user_id',
                'erp_process_last_moved_at',
            ]);
        });

        DB::statement("ALTER TABLE erp_processes MODIFY COLUMN erp_process_status ENUM(
            'draft','in_progress','pending_review','pending_approval','approved',
            'rejected','signed','closed','cancelled'
        ) NOT NULL DEFAULT 'draft'");
    }
};
