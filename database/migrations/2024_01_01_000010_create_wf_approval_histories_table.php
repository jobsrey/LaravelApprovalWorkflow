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
        Schema::create('wf_approval_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('approval_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('flow_step_id')->nullable();
            $table->string('title', 100)->nullable();
            $table->string('flag', 100)->nullable();
            $table->string('notes', 100)->nullable();
            $table->string('file', 100)->nullable();
            $table->integer('date_time')->nullable();
            $table->timestamps();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->foreign('approval_id')
                ->references('id')
                ->on('wf_approvals')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('flow_step_id')
                ->references('id')
                ->on('wf_flow_steps')
                ->onUpdate('cascade');

            $table->index(['approval_id', 'date_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wf_approval_histories');
    }
};
