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
        Schema::create('wf_flow_step_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flow_step_id')->constrained('wf_flow_steps')->onDelete('cascade');
            $table->string('type'); // USER, SYSTEM_GROUP
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('user_group_id')->nullable();
            $table->timestamps();

            $table->index(['flow_step_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wf_flow_step_users');
    }
};
