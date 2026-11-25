<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (Schema::hasColumn('users', 'two_factor_secret')) {
                $table->text('two_factor_secret')->nullable()->change();
            } else {
                $table->text('two_factor_secret')->nullable();
            }

            if (! Schema::hasColumn('users', 'two_factor_recovery_codes')) {
                $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');
            }

            if (! Schema::hasColumn('users', 'two_factor_confirmed_at')) {
                $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_recovery_codes');
            }
        });

        // Clear any pre-Phase-4 placeholder toggle flags that lacked verified TOTP confirmation
        DB::table('users')
            ->whereNull('two_factor_confirmed_at')
            ->where('two_factor_enabled', true)
            ->update([
                'two_factor_enabled' => false,
                'two_factor_secret' => null,
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $columns = array_values(array_filter([
                Schema::hasColumn('users', 'two_factor_recovery_codes') ? 'two_factor_recovery_codes' : null,
                Schema::hasColumn('users', 'two_factor_confirmed_at') ? 'two_factor_confirmed_at' : null,
            ]));

            if (! empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
