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
        if (! Schema::hasTable('notification_preferences')) {
            Schema::create('notification_preferences', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->foreignUuid('business_id')->constrained('businesses')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
                $table->foreignUuid('branch_id')->nullable()->constrained('business_branches')->nullOnDelete();
                $table->string('event_key', 80);
                $table->boolean('telegram_enabled')->default(false);
                $table->boolean('email_enabled')->default(false);
                $table->boolean('in_app_enabled')->default(true);
                $table->boolean('browser_enabled')->default(false);
                $table->timestamps();

                $table->unique(['business_id', 'user_id', 'event_key'], 'notification_preferences_scope_unique');
                $table->index(['business_id', 'event_key']);
            });
        }

        if (! Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->string('type');
                $table->morphs('notifiable');
                $table->text('data');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('notification_preferences');
    }
};
