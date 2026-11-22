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
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->onDelete('cascade');
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->onDelete('cascade');
            $table->foreignUuid('branch_id')->nullable()->constrained('business_branches')->onDelete('set null');
            $table->foreignUuid('category_id')->nullable()->constrained('expense_categories')->onDelete('set null');
            $table->string('title');
            $table->decimal('amount', 12, 2);
            $table->string('currency', 10)->default('USD');
            $table->decimal('amount_khr', 15, 2)->default(0.00);
            $table->date('date');
            $table->string('payment_method', 50)->default('cash');
            $table->text('receipt_url')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('expense_categories');
    }
};
