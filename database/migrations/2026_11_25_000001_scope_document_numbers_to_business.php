<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Table document-number constraints that must be scoped per business_id.
     *
     * @var array<int, array{table: string, column: string, old_index: string, scoped_index: string}>
     */
    private array $scopedConstraints = [
        [
            'table' => 'orders',
            'column' => 'order_number',
            'old_index' => 'orders_order_number_unique',
            'scoped_index' => 'orders_business_id_order_number_unique',
        ],
        [
            'table' => 'pos_transactions',
            'column' => 'receipt_number',
            'old_index' => 'pos_transactions_receipt_number_unique',
            'scoped_index' => 'pos_transactions_business_id_receipt_number_unique',
        ],
        [
            'table' => 'purchase_orders',
            'column' => 'po_number',
            'old_index' => 'purchase_orders_po_number_unique',
            'scoped_index' => 'purchase_orders_business_id_po_number_unique',
        ],
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach ($this->scopedConstraints as $definition) {
            $table = $definition['table'];
            $column = $definition['column'];
            $oldIndex = $definition['old_index'];

            if (DB::getDriverName() === 'pgsql') {
                DB::statement("ALTER TABLE \"{$table}\" DROP CONSTRAINT IF EXISTS \"{$oldIndex}\";");
                DB::statement("DROP INDEX IF EXISTS \"{$oldIndex}\";");
            } elseif (Schema::hasIndex($table, $oldIndex)) {
                Schema::table($table, function (Blueprint $blueprint) use ($oldIndex) {
                    $blueprint->dropUnique($oldIndex);
                });
            }

            if (! Schema::hasIndex($table, ['business_id', $column], 'unique')) {
                Schema::table($table, function (Blueprint $blueprint) use ($column) {
                    $blueprint->unique(['business_id', $column]);
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ($this->scopedConstraints as $definition) {
            $table = $definition['table'];
            $column = $definition['column'];
            $oldIndex = $definition['old_index'];
            $scopedIndex = $definition['scoped_index'];

            if (DB::getDriverName() === 'pgsql') {
                DB::statement("ALTER TABLE \"{$table}\" DROP CONSTRAINT IF EXISTS \"{$scopedIndex}\";");
                DB::statement("DROP INDEX IF EXISTS \"{$scopedIndex}\";");
            } elseif (Schema::hasIndex($table, $scopedIndex)) {
                Schema::table($table, function (Blueprint $blueprint) use ($scopedIndex) {
                    $blueprint->dropUnique($scopedIndex);
                });
            }

            if (! Schema::hasIndex($table, [$column], 'unique')) {
                Schema::table($table, function (Blueprint $blueprint) use ($column) {
                    $blueprint->unique($column);
                });
            }
        }
    }
};
