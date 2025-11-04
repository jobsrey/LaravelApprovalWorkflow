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
        Schema::create('wf_flow_step_approvers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('flow_step_id');
            $table->enum('type', ['USER', 'GROUP', 'SYSTEM_GROUP']);
            $table->string('data')->nullable();
            $table->timestamps();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->foreign('flow_step_id')
                ->references('id')
                ->on('wf_flow_steps')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->index('flow_step_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wf_flow_step_approvers');
    }
};
