<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->onDelete('cascade');
            $table->foreignUuid('branch_id')->nullable()->constrained('business_branches')->onDelete('set null');
            $table->string('customer_name');
            $table->string('customer_phone', 50);
            $table->string('customer_email')->nullable();
            $table->integer('party_size')->default(2);
            $table->date('reservation_date');
            $table->time('reservation_time');
            $table->foreignUuid('table_id')->nullable()->constrained('restaurant_tables')->onDelete('set null');
            $table->text('special_requests')->nullable();
            $table->string('status', 50)->default('pending'); // pending, confirmed, seated, completed, cancelled
            $table->timestamps();

            $table->index(['business_id', 'reservation_date', 'status']);
        });

        Schema::create('customer_waitlists', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->onDelete('cascade');
            $table->foreignUuid('branch_id')->nullable()->constrained('business_branches')->onDelete('set null');
            $table->string('customer_name');
            $table->string('customer_phone', 50);
            $table->integer('party_size')->default(2);
            $table->string('queue_number', 20);
            $table->integer('estimated_wait_minutes')->default(15);
            $table->string('status', 50)->default('waiting'); // waiting, notified, seated, cancelled
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_waitlists');
        Schema::dropIfExists('reservations');
    }
};
