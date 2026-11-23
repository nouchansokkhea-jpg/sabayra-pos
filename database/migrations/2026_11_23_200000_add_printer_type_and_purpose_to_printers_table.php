<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('printers', function (Blueprint $table) {
            if (!Schema::hasColumn('printers', 'printer_type')) {
                $table->string('printer_type', 30)->default('receipt'); // receipt, kitchen, general
            }
            if (!Schema::hasColumn('printers', 'printing_purpose')) {
                $table->string('printing_purpose', 30)->default('both'); // customer_receipts, kitchen_tickets, both
            }
            if (!Schema::hasColumn('printers', 'last_used_at')) {
                $table->timestamp('last_used_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('printers', function (Blueprint $table) {
            $cols = ['printer_type', 'printing_purpose', 'last_used_at'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('printers', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
