<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('wf_approval_active_users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('approval_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->foreign('approval_id')
                ->references('id')
                ->on('wf_approvals')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->index(['approval_id', 'user_id']);
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
