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
        Schema::create('wf_flow_steps', function (Blueprint $table) {
            $table->id();
            $table->integer('order');
            $table->unsignedBigInteger('flow_id');
            $table->string('name', 100)->nullable();
            $table->string('condition', 1000)->nullable();
            
            $table->foreign('flow_id')
                ->references('id')
                ->on('wf_flows')
                ->onDelete('cascade')
                ->onUpdate('cascade');
            
            $table->index(['flow_id', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wf_flow_steps');
    }
};
