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
        Schema::create('telegram_connections', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->onDelete('cascade')->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('connection_mode', 30)->default('official_bot'); // official_bot, custom_bot
            $table->text('bot_token')->nullable(); // Encrypted at rest via Eloquent cast
            $table->string('bot_username', 100)->nullable();
            $table->text('chat_id')->nullable(); // Encrypted at rest via Eloquent cast
            $table->string('telegram_user_id', 64)->nullable();
            $table->string('telegram_username', 100)->nullable();
            $table->string('telegram_display_name', 150)->nullable();
            $table->boolean('is_active')->default(false);
            $table->boolean('notifications_enabled')->default(true);
            $table->timestamp('connected_at')->nullable();
            $table->timestamp('last_notification_at')->nullable();
            $table->timestamp('last_tested_at')->nullable();
            $table->string('last_error', 500)->nullable();
            $table->timestamps();

            $table->index(['business_id', 'is_active']);
        });

        Schema::create('telegram_verification_codes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('code_hash', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'expires_at']);
        });

        Schema::create('telegram_notification_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->onDelete('cascade');
            $table->foreignUuid('telegram_connection_id')->nullable()->constrained('telegram_connections')->onDelete('set null');
            $table->string('event_type', 80);
            $table->string('status', 20); // sent, failed, skipped
            $table->string('recipient_masked', 64)->nullable();
            $table->string('message_preview', 255)->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'created_at']);
            $table->index(['business_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('telegram_notification_logs');
        Schema::dropIfExists('telegram_verification_codes');
        Schema::dropIfExists('telegram_connections');
    }
};
