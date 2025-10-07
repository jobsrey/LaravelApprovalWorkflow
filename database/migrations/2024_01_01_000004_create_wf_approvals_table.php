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
            $table->unsignedBigInteger('company_id');
            $table->foreignId('flow_id')->constrained('wf_flows');
            $table->foreignId('flow_step_id')->nullable()->constrained('wf_flow_steps');
            $table->unsignedBigInteger('user_id'); // Owner of the approval
            $table->enum('status', ['ON_PROGRESS', 'APPROVED', 'REJECTED'])->default('ON_PROGRESS');
            $table->json('parameters')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index(['user_id', 'status']);
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
