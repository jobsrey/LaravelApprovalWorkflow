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
        Schema::create('wf_approvals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('flow_id');
            $table->enum('status', ['ON_PROGRESS', 'APPROVED', 'REJECTED']);
            $table->unsignedBigInteger('flow_step_id')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->text('parameters')->nullable();
            $table->unsignedBigInteger('company_id');
            
            $table->foreign('flow_id')
                ->references('id')
                ->on('wf_flows')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            
            $table->foreign('flow_step_id')
                ->references('id')
                ->on('wf_flow_steps');
            
            $table->index(['company_id', 'status']);
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wf_approvals');
    }
};
