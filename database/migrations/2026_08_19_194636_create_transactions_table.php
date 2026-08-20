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
        Schema::create('transactions', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->foreignUuid('user_id')->nullable()->constrained()->nullOnDelete();
        $table->foreignUuid('category_id')->nullable()->constrained('categories')->nullOnDelete();

        $table->enum('type', ['pemasukan', 'pengeluaran']);
        $table->unsignedBigInteger('amount');
        $table->text('note')->nullable();
        $table->timestamp('transaction_date');

        $table->timestamps();
        $table->softDeletes();

        $table->index(['user_id', 'transaction_date']);
        $table->index(['category_id', 'transaction_date']);
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
