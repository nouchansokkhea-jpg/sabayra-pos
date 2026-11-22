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
        Schema::create('plans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('monthly_price', 10, 2)->default(0.00);
            $table->decimal('yearly_price', 10, 2)->default(0.00);
            $table->string('currency', 10)->default('USD');
            $table->integer('product_limit')->default(50);
            $table->integer('branch_limit')->default(1);
            $table->integer('staff_limit')->default(2);
            $table->boolean('has_pos')->default(false);
            $table->boolean('has_inventory')->default(false);
            $table->boolean('has_online_ordering')->default(true);
            $table->boolean('has_analytics')->default(false);
            $table->boolean('has_custom_branding')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_popular')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->onDelete('cascade');
            $table->foreignUuid('plan_id')->constrained('plans')->onDelete('cascade');
            $table->string('billing_cycle', 20)->default('monthly'); // monthly, yearly
            $table->decimal('price', 10, 2)->default(0.00);
            $table->string('status', 30)->default('active'); // active, expired, cancelled, pending
            $table->timestamp('starts_at')->useCurrent();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('payment_method', 50)->nullable();
            $table->timestamps();
        });

        Schema::create('subscription_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('subscription_id')->constrained('subscriptions')->onDelete('cascade');
            $table->foreignUuid('business_id')->constrained('businesses')->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->string('currency', 10)->default('USD');
            $table->string('payment_method', 50)->default('manual');
            $table->string('transaction_reference')->nullable();
            $table->string('status', 30)->default('pending'); // pending, completed, failed, refunded
            $table->json('payment_details')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_payments');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('plans');
    }
};
