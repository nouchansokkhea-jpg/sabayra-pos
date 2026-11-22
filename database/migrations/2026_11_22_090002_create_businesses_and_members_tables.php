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
        Schema::create('businesses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('owner_id')->constrained('users')->onDelete('restrict');
            $table->string('name');
            $table->string('slug')->unique()->index();
            $table->string('business_type', 50)->default('restaurant');
            $table->string('tagline')->nullable();
            $table->text('description')->nullable();
            $table->text('logo_url')->nullable();
            $table->text('cover_url')->nullable();
            $table->string('phone', 50)->nullable();
            $table->string('email')->nullable();
            $table->string('telegram_handle', 100)->nullable();
            $table->text('address')->nullable();
            $table->string('city', 100)->default('Phnom Penh');
            $table->string('country', 100)->default('Cambodia');
            $table->string('default_currency', 10)->default('USD');
            $table->decimal('exchange_rate_khr', 10, 2)->default(4100.00);
            $table->decimal('tax_percent', 5, 2)->default(0.00);
            $table->string('status', 50)->default('active'); // active, suspended, trial
            $table->json('theme_settings')->nullable(); // primary_color, font, layout, theme
            $table->json('opening_hours')->nullable();
            $table->timestamps();
        });

        Schema::create('business_branches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->onDelete('cascade');
            $table->string('name');
            $table->string('phone', 50)->nullable();
            $table->text('address')->nullable();
            $table->boolean('is_main')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('business_members', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('role', 50)->default('staff'); // owner, manager, cashier, waiter, kitchen, inventory
            $table->foreignUuid('branch_id')->nullable()->constrained('business_branches')->onDelete('set null');
            $table->boolean('is_active')->default(true);
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'user_id']);
        });

        Schema::create('business_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->onDelete('cascade');
            $table->string('key');
            $table->json('value')->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_settings');
        Schema::dropIfExists('business_members');
        Schema::dropIfExists('business_branches');
        Schema::dropIfExists('businesses');
    }
};
