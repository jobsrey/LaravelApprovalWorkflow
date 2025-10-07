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
        Schema::create('wf_approval_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_id')->constrained('wf_approvals')->onDelete('cascade');
            $table->foreignId('flow_step_id')->nullable()->constrained('wf_flow_steps');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('title');
            $table->enum('flag', [
                'created', 'reset', 'approved', 'rejected', 
                'system_rejected', 'done', 'skip'
            ]);
            $table->text('notes')->nullable();
            $table->string('file')->nullable(); // Legacy field for backward compatibility
            $table->timestamp('date_time');
            $table->timestamps();

            $table->index(['approval_id', 'date_time']);
            $table->index(['flag', 'date_time']);
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
