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
        Schema::create('wf_approval_active_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_id')->constrained('wf_approvals')->onDelete('cascade');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();

            $table->unique(['approval_id', 'user_id']);
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wf_approval_active_users');
    }
};
