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
        Schema::table('products', function (Blueprint $table): void {
            if (! Schema::hasColumn('products', 'brand')) {
                $table->string('brand', 120)->nullable()->after('name_km');
            }
            if (! Schema::hasColumn('products', 'batch_number')) {
                $table->string('batch_number', 80)->nullable()->after('barcode');
            }
            if (! Schema::hasColumn('products', 'expiry_date')) {
                $table->date('expiry_date')->nullable()->after('batch_number');
                $table->index(['business_id', 'expiry_date'], 'products_biz_expiry_idx');
            }
            if (! Schema::hasColumn('products', 'duration_minutes')) {
                $table->integer('duration_minutes')->nullable()->after('unit');
            }
            if (! Schema::hasColumn('products', 'attributes_json')) {
                $table->json('attributes_json')->nullable()->after('duration_minutes');
            }
        });

        Schema::table('product_variants', function (Blueprint $table): void {
            if (! Schema::hasColumn('product_variants', 'barcode')) {
                $table->string('barcode', 100)->nullable()->after('sku');
                $table->index('barcode', 'product_variants_barcode_idx');
            }
            if (! Schema::hasColumn('product_variants', 'size')) {
                $table->string('size', 50)->nullable()->after('name_km');
            }
            if (! Schema::hasColumn('product_variants', 'color')) {
                $table->string('color', 50)->nullable()->after('size');
            }
            if (! Schema::hasColumn('product_variants', 'shade')) {
                $table->string('shade', 80)->nullable()->after('color');
            }
        });

        Schema::table('orders', function (Blueprint $table): void {
            if (! Schema::hasColumn('orders', 'scheduled_for')) {
                $table->timestamp('scheduled_for')->nullable()->after('notes');
            }
            if (! Schema::hasColumn('orders', 'assigned_staff_id')) {
                $table->foreignUuid('assigned_staff_id')->nullable()->after('customer_id')->constrained('business_members')->nullOnDelete();
            }
            if (! Schema::hasColumn('orders', 'pos_metadata')) {
                $table->json('pos_metadata')->nullable()->after('scheduled_for');
            }
        });

        Schema::table('order_items', function (Blueprint $table): void {
            if (! Schema::hasColumn('order_items', 'options_json')) {
                $table->json('options_json')->nullable()->after('notes');
            }
        });

        Schema::table('reservations', function (Blueprint $table): void {
            if (! Schema::hasColumn('reservations', 'booking_type')) {
                $table->string('booking_type', 30)->default('table')->after('branch_id');
            }
            if (! Schema::hasColumn('reservations', 'booking_code')) {
                $table->string('booking_code', 32)->nullable()->after('booking_type');
                $table->index(['business_id', 'booking_code'], 'reservations_biz_code_idx');
            }
            if (! Schema::hasColumn('reservations', 'service_product_id')) {
                $table->foreignUuid('service_product_id')->nullable()->after('table_id')->constrained('products')->nullOnDelete();
            }
            if (! Schema::hasColumn('reservations', 'staff_member_id')) {
                $table->foreignUuid('staff_member_id')->nullable()->after('service_product_id')->constrained('business_members')->nullOnDelete();
            }
            if (! Schema::hasColumn('reservations', 'duration_minutes')) {
                $table->integer('duration_minutes')->nullable()->after('reservation_time');
            }
            if (! Schema::hasColumn('reservations', 'end_time')) {
                $table->time('end_time')->nullable()->after('duration_minutes');
            }
            if (! Schema::hasColumn('reservations', 'price_usd')) {
                $table->decimal('price_usd', 12, 2)->nullable()->after('end_time');
            }
            if (! Schema::hasColumn('reservations', 'payment_status')) {
                $table->string('payment_status', 30)->default('unpaid')->after('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table): void {
            if (Schema::hasColumn('reservations', 'staff_member_id')) {
                $table->dropConstrainedForeignId('staff_member_id');
            }
            if (Schema::hasColumn('reservations', 'service_product_id')) {
                $table->dropConstrainedForeignId('service_product_id');
            }
            if (Schema::hasColumn('reservations', 'booking_code')) {
                $table->dropIndex('reservations_biz_code_idx');
            }
            $dropRes = array_values(array_filter([
                Schema::hasColumn('reservations', 'booking_type') ? 'booking_type' : null,
                Schema::hasColumn('reservations', 'booking_code') ? 'booking_code' : null,
                Schema::hasColumn('reservations', 'duration_minutes') ? 'duration_minutes' : null,
                Schema::hasColumn('reservations', 'end_time') ? 'end_time' : null,
                Schema::hasColumn('reservations', 'price_usd') ? 'price_usd' : null,
                Schema::hasColumn('reservations', 'payment_status') ? 'payment_status' : null,
            ]));
            if ($dropRes !== []) {
                $table->dropColumn($dropRes);
            }
        });

        Schema::table('order_items', function (Blueprint $table): void {
            if (Schema::hasColumn('order_items', 'options_json')) {
                $table->dropColumn('options_json');
            }
        });

        Schema::table('orders', function (Blueprint $table): void {
            if (Schema::hasColumn('orders', 'assigned_staff_id')) {
                $table->dropConstrainedForeignId('assigned_staff_id');
            }
            $dropOrd = array_values(array_filter([
                Schema::hasColumn('orders', 'scheduled_for') ? 'scheduled_for' : null,
                Schema::hasColumn('orders', 'pos_metadata') ? 'pos_metadata' : null,
            ]));
            if ($dropOrd !== []) {
                $table->dropColumn($dropOrd);
            }
        });

        Schema::table('product_variants', function (Blueprint $table): void {
            if (Schema::hasColumn('product_variants', 'barcode')) {
                $table->dropIndex('product_variants_barcode_idx');
            }
            $dropVar = array_values(array_filter([
                Schema::hasColumn('product_variants', 'barcode') ? 'barcode' : null,
                Schema::hasColumn('product_variants', 'size') ? 'size' : null,
                Schema::hasColumn('product_variants', 'color') ? 'color' : null,
                Schema::hasColumn('product_variants', 'shade') ? 'shade' : null,
            ]));
            if ($dropVar !== []) {
                $table->dropColumn($dropVar);
            }
        });

        Schema::table('products', function (Blueprint $table): void {
            if (Schema::hasColumn('products', 'expiry_date')) {
                $table->dropIndex('products_biz_expiry_idx');
            }
            $dropProd = array_values(array_filter([
                Schema::hasColumn('products', 'brand') ? 'brand' : null,
                Schema::hasColumn('products', 'batch_number') ? 'batch_number' : null,
                Schema::hasColumn('products', 'expiry_date') ? 'expiry_date' : null,
                Schema::hasColumn('products', 'duration_minutes') ? 'duration_minutes' : null,
                Schema::hasColumn('products', 'attributes_json') ? 'attributes_json' : null,
            ]));
            if ($dropProd !== []) {
                $table->dropColumn($dropProd);
            }
        });
    }
};
