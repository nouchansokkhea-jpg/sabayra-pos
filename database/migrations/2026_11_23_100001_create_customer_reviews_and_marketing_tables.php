<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_reviews', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->onDelete('cascade');
            $table->foreignUuid('order_id')->nullable()->constrained('orders')->onDelete('set null');
            $table->foreignUuid('customer_id')->nullable()->constrained('customers')->onDelete('set null');
            $table->foreignUuid('product_id')->nullable()->constrained('products')->onDelete('set null');
            $table->string('customer_name');
            $table->integer('rating')->default(5); // 1-5
            $table->text('comment')->nullable();
            $table->string('sentiment', 20)->default('positive'); // positive, neutral, negative
            $table->string('status', 30)->default('published'); // published, pending, hidden
            $table->text('merchant_response')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'status', 'rating']);
        });

        Schema::create('coupons', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->onDelete('cascade');
            $table->string('code', 50);
            $table->string('description')->nullable();
            $table->string('discount_type', 20)->default('percentage'); // percentage, fixed
            $table->decimal('discount_value', 12, 2)->default(10.00);
            $table->decimal('min_order_amount', 12, 2)->default(0.00);
            $table->decimal('max_discount_amount', 12, 2)->nullable();
            $table->integer('usage_limit')->nullable();
            $table->integer('used_count')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['business_id', 'code']);
        });

        Schema::create('happy_hours', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->onDelete('cascade');
            $table->string('name', 100);
            $table->decimal('discount_percent', 5, 2)->default(20.00);
            $table->time('start_time');
            $table->time('end_time');
            $table->json('days_of_week'); // e.g. ["mon","tue","wed","thu","fri"]
            $table->json('applicable_categories')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['business_id', 'is_active']);
        });

        Schema::create('referrals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->onDelete('cascade');
            $table->string('referral_code', 50);
            $table->string('referrer_name');
            $table->string('referrer_phone', 50)->nullable();
            $table->string('reward_description')->default('$2 Off Next Order');
            $table->integer('times_used')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['business_id', 'referral_code']);
        });

        if (!Schema::hasColumn('customers', 'loyalty_points')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->integer('loyalty_points')->default(0);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('customers', 'loyalty_points')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropColumn('loyalty_points');
            });
        }
        Schema::dropIfExists('referrals');
        Schema::dropIfExists('happy_hours');
        Schema::dropIfExists('coupons');
        Schema::dropIfExists('customer_reviews');
    }
};
