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
        if (! Schema::hasTable('payment_transactions')) {
            Schema::create('payment_transactions', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->foreignUuid('business_id')->constrained('businesses')->cascadeOnDelete();
                $table->foreignUuid('order_id')->constrained('orders')->cascadeOnDelete();
                $table->string('gateway', 40); // bank_transfer, static_qr, cod, cash, aba_payway, bakong_khqr, card
                $table->string('reference_number', 120)->unique();
                $table->string('external_transaction_id', 150)->nullable()->index();
                $table->string('khqr_md5', 64)->nullable()->index();
                $table->text('khqr_payload')->nullable();
                $table->text('qr_image_url')->nullable();
                $table->text('checkout_url')->nullable();
                $table->decimal('amount', 12, 2);
                $table->string('currency', 10)->default('USD');
                $table->string('status', 40)->default('pending'); // pending, pending_verification, pending_collection, paid, failed, rejected, expired, refunded
                $table->string('idempotency_key', 150)->nullable()->unique();
                $table->timestamp('expires_at')->nullable();
                $table->timestamp('verified_at')->nullable();
                $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
                $table->json('gateway_payload')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['business_id', 'status', 'created_at'], 'payment_tx_business_status_idx');
                $table->index(['order_id', 'status'], 'payment_tx_order_status_idx');
                $table->index(['gateway', 'external_transaction_id'], 'payment_tx_gateway_ext_idx');
            });
        }

        if (! Schema::hasTable('payment_proofs')) {
            Schema::create('payment_proofs', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->foreignUuid('business_id')->constrained('businesses')->cascadeOnDelete();
                $table->foreignUuid('order_id')->constrained('orders')->cascadeOnDelete();
                $table->foreignUuid('payment_transaction_id')->nullable()->constrained('payment_transactions')->nullOnDelete();
                $table->string('payment_method', 40);
                $table->text('file_path');
                $table->string('original_filename', 255);
                $table->string('mime_type', 100);
                $table->unsignedInteger('file_size');
                $table->string('customer_reference', 120)->nullable();
                $table->string('customer_note', 500)->nullable();
                $table->string('status', 30)->default('pending'); // pending, approved, rejected
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('reviewed_at')->nullable();
                $table->string('review_note', 500)->nullable();
                $table->string('uploaded_ip', 45)->nullable();
                $table->timestamps();

                $table->index(['business_id', 'status', 'created_at'], 'payment_proofs_business_status_idx');
                $table->index(['order_id', 'status'], 'payment_proofs_order_status_idx');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_proofs');
        Schema::dropIfExists('payment_transactions');
    }
};
