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
        Schema::table('wf_approval_histories', function (Blueprint $table) {
            // Cek dan tambahkan step_name jika belum ada
            if (!Schema::hasColumn('wf_approval_histories', 'step_name')) {
                $table->string('step_name', 100)->nullable()->after('flow_step_id');
            }

            // Cek dan tambahkan step_order jika belum ada
            if (!Schema::hasColumn('wf_approval_histories', 'step_order')) {
                $table->integer('step_order')->nullable()->after('step_name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wf_approval_histories', function (Blueprint $table) {
            // Hapus kolom hanya jika ada
            if (Schema::hasColumn('wf_approval_histories', 'step_name')) {
                $table->dropColumn('step_name');
            }
            if (Schema::hasColumn('wf_approval_histories', 'step_order')) {
                $table->dropColumn('step_order');
            }
        });
    }
};
