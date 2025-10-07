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
        Schema::create('wf_approver_group_users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('approver_group_id');
            $table->unsignedBigInteger('user_id');
            
            $table->foreign('approver_group_id')
                ->references('id')
                ->on('wf_approver_groups')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            
            $table->index(['approver_group_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wf_approver_group_users');
    }
};
