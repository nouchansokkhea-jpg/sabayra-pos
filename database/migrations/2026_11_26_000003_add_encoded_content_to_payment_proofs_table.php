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
        Schema::table('payment_proofs', function (Blueprint $table): void {
            if (! Schema::hasColumn('payment_proofs', 'encoded_content')) {
                $table->longText('encoded_content')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_proofs', function (Blueprint $table): void {
            if (Schema::hasColumn('payment_proofs', 'encoded_content')) {
                $table->dropColumn('encoded_content');
            }
        });
    }
};
