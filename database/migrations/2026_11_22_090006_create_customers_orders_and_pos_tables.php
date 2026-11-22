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
        Schema::create('customers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->onDelete('cascade');
            $table->string('name');
            $table->string('phone', 50)->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->decimal('total_spent', 12, 2)->default(0.00);
            $table->integer('total_orders')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'phone']);
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->onDelete('cascade');
            $table->foreignUuid('branch_id')->nullable()->constrained('business_branches')->onDelete('set null');
            $table->string('order_number', 50)->unique()->index();
            $table->string('order_type', 50)->default('dine_in'); // dine_in, takeaway, delivery, pos
            $table->foreignUuid('table_id')->nullable()->constrained('restaurant_tables')->onDelete('set null');
            $table->foreignUuid('customer_id')->nullable()->constrained('customers')->onDelete('set null');
            $table->string('customer_name')->nullable();
            $table->string('customer_phone', 50)->nullable();
            $table->text('delivery_address')->nullable();
            $table->decimal('subtotal', 12, 2)->default(0.00);
            $table->decimal('discount_amount', 12, 2)->default(0.00);
            $table->decimal('tax_amount', 12, 2)->default(0.00);
            $table->decimal('total_amount', 12, 2)->default(0.00);
            $table->decimal('total_khr', 15, 2)->default(0.00);
            $table->string('currency', 10)->default('USD');
            $table->decimal('exchange_rate_khr', 10, 2)->default(4100.00);
            $table->string('status', 50)->default('pending'); // pending, confirmed, preparing, ready, out_for_delivery, completed, cancelled
            $table->string('payment_status', 50)->default('unpaid'); // unpaid, paid, refunded, partial
            $table->string('payment_method', 50)->nullable(); // cash, khqr, aba_payway, card
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'status', 'created_at']);
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignUuid('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignUuid('variant_id')->nullable()->constrained('product_variants')->onDelete('set null');
            $table->string('product_name');
            $table->string('variant_name')->nullable();
            $table->decimal('unit_price', 12, 2);
            $table->decimal('quantity', 12, 2)->default(1.00);
            $table->decimal('subtotal', 12, 2);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('order_item_addons', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_item_id')->constrained('order_items')->onDelete('cascade');
            $table->foreignUuid('addon_id')->constrained('product_addons')->onDelete('cascade');
            $table->string('name');
            $table->decimal('price', 12, 2);
            $table->integer('quantity')->default(1);
        });

        Schema::create('order_status_histories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('from_status', 50)->nullable();
            $table->string('to_status', 50);
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('pos_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->onDelete('cascade');
            $table->foreignUuid('branch_id')->nullable()->constrained('business_branches')->onDelete('set null');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->decimal('opening_cash', 12, 2)->default(0.00);
            $table->decimal('closing_cash', 12, 2)->nullable();
            $table->decimal('expected_cash', 12, 2)->nullable();
            $table->string('status', 30)->default('open'); // open, closed
            $table->timestamp('opened_at')->useCurrent();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('pos_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('pos_session_id')->constrained('pos_sessions')->onDelete('cascade');
            $table->foreignUuid('business_id')->constrained('businesses')->onDelete('cascade');
            $table->foreignUuid('order_id')->constrained('orders')->onDelete('cascade');
            $table->decimal('amount', 12, 2);
            $table->decimal('amount_khr', 15, 2);
            $table->string('payment_method', 50)->default('cash');
            $table->decimal('amount_tendered', 12, 2)->nullable();
            $table->decimal('change_given', 12, 2)->default(0.00);
            $table->string('reference_number')->nullable();
            $table->string('receipt_number')->unique();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pos_transactions');
        Schema::dropIfExists('pos_sessions');
        Schema::dropIfExists('order_status_histories');
        Schema::dropIfExists('order_item_addons');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('customers');
    }
};
