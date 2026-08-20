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
        Schema::create('budgets', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->foreignUuid('user_id')->nullable()->constrained()->nullOnDelete();
        $table->foreignUuid('category_id')->nullable()->constrained('categories')->nullOnDelete();

        $table->unsignedBigInteger('amount');
        $table->unsignedTinyInteger('month');
        $table->unsignedSmallInteger('year');

        $table->timestamps();

        $table->unique(
            ['user_id', 'category_id', 'month', 'year'],
            'budgets_user_category_month_year_unique'
        );

        $table->index(['user_id', 'year', 'month']);
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budgets');
    }
};
