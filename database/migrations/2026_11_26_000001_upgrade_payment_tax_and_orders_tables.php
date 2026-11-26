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
        Schema::table('businesses', function (Blueprint $table): void {
            if (! Schema::hasColumn('businesses', 'tax_name')) {
                $table->string('tax_name', 50)->default('VAT')->after('tax_percent');
            }
            if (! Schema::hasColumn('businesses', 'tax_inclusive')) {
                $table->boolean('tax_inclusive')->default(false)->after('tax_name');
            }
            if (! Schema::hasColumn('businesses', 'service_charge_enabled')) {
                $table->boolean('service_charge_enabled')->default(false)->after('tax_inclusive');
            }
            if (! Schema::hasColumn('businesses', 'service_charge_rate')) {
                $table->decimal('service_charge_rate', 5, 2)->default(0.00)->after('service_charge_enabled');
            }
        });

        if (! Schema::hasTable('platform_settings')) {
            Schema::create('platform_settings', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->string('key', 120)->unique();
                $table->text('value')->nullable();
                $table->boolean('is_encrypted')->default(false);
                $table->string('description', 255)->nullable();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('business_payment_configs')) {
            Schema::create('business_payment_configs', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->foreignUuid('business_id')->constrained('businesses')->cascadeOnDelete()->unique();

                // 1. Bank Transfer
                $table->boolean('bank_transfer_enabled')->default(false);
                $table->string('bank_name', 120)->nullable();
                $table->string('bank_account_holder', 150)->nullable();
                $table->string('bank_account_number', 80)->nullable();
                $table->text('bank_instructions')->nullable();
                $table->string('bank_reference_instructions', 255)->nullable();

                // 2. Static QR Payment
                $table->boolean('static_qr_enabled')->default(false);
                $table->string('static_qr_label', 100)->default('QR Payment');
                $table->text('static_qr_image_path')->nullable();
                $table->text('static_qr_instructions')->nullable();
                $table->boolean('static_qr_require_proof')->default(true);

                // 3. Cash & Cash on Delivery
                $table->boolean('cash_enabled')->default(true);
                $table->boolean('cod_enabled')->default(true);
                $table->string('cod_instructions', 255)->nullable();

                // 4. ABA PayWay (Dynamic KHQR / Merchant API / Hosted Link)
                $table->boolean('aba_payway_enabled')->default(false);
                $table->string('aba_payway_link', 500)->nullable();
                $table->string('aba_merchant_id', 120)->nullable();
                $table->text('aba_api_key')->nullable(); // Encrypted at rest via Eloquent cast
                $table->string('aba_environment', 20)->default('sandbox');
                $table->timestamp('aba_last_tested_at')->nullable();
                $table->string('aba_test_status', 30)->default('untested');
                $table->string('aba_test_message', 500)->nullable();

                // 5. Bakong KHQR
                $table->boolean('bakong_enabled')->default(false);
                $table->string('bakong_account_id', 120)->nullable();
                $table->string('bakong_merchant_name', 100)->nullable();
                $table->boolean('bakong_verified')->default(false);
                $table->timestamp('bakong_verified_at')->nullable();
                $table->string('bakong_verification_status', 30)->default('not_verified');
                $table->string('bakong_verification_message', 500)->nullable();

                $table->timestamps();
            });
        }

        Schema::table('orders', function (Blueprint $table): void {
            if (! Schema::hasColumn('orders', 'tax_name')) {
                $table->string('tax_name', 50)->default('VAT')->after('discount_amount');
            }
            if (! Schema::hasColumn('orders', 'tax_rate')) {
                $table->decimal('tax_rate', 5, 2)->default(0.00)->after('tax_name');
            }
            if (! Schema::hasColumn('orders', 'tax_inclusive')) {
                $table->boolean('tax_inclusive')->default(false)->after('tax_rate');
            }
            if (! Schema::hasColumn('orders', 'service_charge_enabled')) {
                $table->boolean('service_charge_enabled')->default(false)->after('tax_amount');
            }
            if (! Schema::hasColumn('orders', 'service_charge_rate')) {
                $table->decimal('service_charge_rate', 5, 2)->default(0.00)->after('service_charge_enabled');
            }
            if (! Schema::hasColumn('orders', 'service_charge_amount')) {
                $table->decimal('service_charge_amount', 12, 2)->default(0.00)->after('service_charge_rate');
            }
            if (! Schema::hasColumn('orders', 'payment_reference')) {
                $table->string('payment_reference', 120)->nullable()->index()->after('payment_method');
            }
            if (! Schema::hasColumn('orders', 'gateway_transaction_id')) {
                $table->string('gateway_transaction_id', 150)->nullable()->index()->after('payment_reference');
            }
            if (! Schema::hasColumn('orders', 'payment_verified_at')) {
                $table->timestamp('payment_verified_at')->nullable()->after('gateway_transaction_id');
            }
            if (! Schema::hasColumn('orders', 'payment_verified_by')) {
                $table->foreignId('payment_verified_by')->nullable()->constrained('users')->nullOnDelete()->after('payment_verified_at');
            }
            if (! Schema::hasColumn('orders', 'payment_collected_at')) {
                $table->timestamp('payment_collected_at')->nullable()->after('payment_verified_by');
            }
            if (! Schema::hasColumn('orders', 'payment_collected_by')) {
                $table->foreignId('payment_collected_by')->nullable()->constrained('users')->nullOnDelete()->after('payment_collected_at');
            }
            if (! Schema::hasColumn('orders', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('payment_collected_by');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            if (Schema::hasColumn('orders', 'payment_verified_by')) {
                $table->dropConstrainedForeignId('payment_verified_by');
            }
            if (Schema::hasColumn('orders', 'payment_collected_by')) {
                $table->dropConstrainedForeignId('payment_collected_by');
            }

            $columns = array_values(array_filter([
                Schema::hasColumn('orders', 'tax_name') ? 'tax_name' : null,
                Schema::hasColumn('orders', 'tax_rate') ? 'tax_rate' : null,
                Schema::hasColumn('orders', 'tax_inclusive') ? 'tax_inclusive' : null,
                Schema::hasColumn('orders', 'service_charge_enabled') ? 'service_charge_enabled' : null,
                Schema::hasColumn('orders', 'service_charge_rate') ? 'service_charge_rate' : null,
                Schema::hasColumn('orders', 'service_charge_amount') ? 'service_charge_amount' : null,
                Schema::hasColumn('orders', 'payment_reference') ? 'payment_reference' : null,
                Schema::hasColumn('orders', 'gateway_transaction_id') ? 'gateway_transaction_id' : null,
                Schema::hasColumn('orders', 'payment_verified_at') ? 'payment_verified_at' : null,
                Schema::hasColumn('orders', 'payment_collected_at') ? 'payment_collected_at' : null,
                Schema::hasColumn('orders', 'paid_at') ? 'paid_at' : null,
            ]));

            if (! empty($columns)) {
                $table->dropColumn($columns);
            }
        });

        Schema::dropIfExists('business_payment_configs');
        Schema::dropIfExists('platform_settings');

        Schema::table('businesses', function (Blueprint $table): void {
            $columns = array_values(array_filter([
                Schema::hasColumn('businesses', 'tax_name') ? 'tax_name' : null,
                Schema::hasColumn('businesses', 'tax_inclusive') ? 'tax_inclusive' : null,
                Schema::hasColumn('businesses', 'service_charge_enabled') ? 'service_charge_enabled' : null,
                Schema::hasColumn('businesses', 'service_charge_rate') ? 'service_charge_rate' : null,
            ]));

            if (! empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
