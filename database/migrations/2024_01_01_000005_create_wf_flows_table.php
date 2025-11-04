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
        Schema::create('wf_flows', function (Blueprint $table) {
            $table->id();
            $table->string('type', 100); // e.g., 'PR', 'PO', etc.
            $table->unsignedBigInteger('company_id');
            $table->tinyInteger('is_active')->default(0);
            $table->string('label', 100)->nullable();
            $table->timestamps();

            $table->index(['company_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wf_flows');
    }
};
