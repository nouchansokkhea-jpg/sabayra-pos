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
        Schema::create('restaurant_tables', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->onDelete('cascade');
            $table->foreignUuid('branch_id')->nullable()->constrained('business_branches')->onDelete('set null');
            $table->string('table_number', 50);
            $table->string('name', 100)->nullable();
            $table->integer('capacity')->default(4);
            $table->string('status', 30)->default('available'); // available, occupied, reserved
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['business_id', 'table_number']);
        });

        Schema::create('qr_codes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->onDelete('cascade');
            $table->foreignUuid('table_id')->nullable()->constrained('restaurant_tables')->onDelete('set null');
            $table->string('type', 30)->default('menu'); // menu, table, business
            $table->string('code', 100)->unique();
            $table->text('target_url');
            $table->text('svg_path')->nullable();
            $table->integer('scan_count')->default(0);
            $table->timestamp('last_scanned_at')->nullable();
            $table->timestamps();
        });

        Schema::create('qr_scan_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('qr_code_id')->constrained('qr_codes')->onDelete('cascade');
            $table->foreignUuid('business_id')->constrained('businesses')->onDelete('cascade');
            $table->uuid('table_id')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('scanned_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qr_scan_events');
        Schema::dropIfExists('qr_codes');
        Schema::dropIfExists('restaurant_tables');
    }
};
