<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE businesses DROP CONSTRAINT IF EXISTS businesses_owner_id_foreign;');
            DB::statement('ALTER TABLE businesses ADD CONSTRAINT businesses_owner_id_foreign FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE;');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE businesses DROP CONSTRAINT IF EXISTS businesses_owner_id_foreign;');
            DB::statement('ALTER TABLE businesses ADD CONSTRAINT businesses_owner_id_foreign FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE RESTRICT;');
        }
    }
};
