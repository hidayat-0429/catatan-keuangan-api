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
        Schema::create('financial_goals', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->foreignUuid('user_id')->nullable()->constrained()->nullOnDelete();

        $table->string('name');
        $table->unsignedBigInteger('target_amount');
        $table->unsignedBigInteger('current_amount')->default(0);
        $table->date('deadline')->nullable();

        $table->timestamps();

        $table->index(['user_id', 'created_at']);
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_goals');
    }
};
