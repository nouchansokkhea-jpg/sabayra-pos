<?php

use App\Models\Business;
use App\Models\BusinessMember;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Data reconciliation: Enforce 1 Business = 1 Owner for all existing records
        try {
            $businesses = Business::all();

            foreach ($businesses as $business) {
                // Ensure authoritative owner has an owner record in business_members
                $ownerMember = BusinessMember::withoutGlobalScopes()
                    ->where('business_id', $business->id)
                    ->where('user_id', $business->owner_id)
                    ->first();

                if (!$ownerMember) {
                    BusinessMember::withoutGlobalScopes()->create([
                        'business_id' => $business->id,
                        'user_id' => $business->owner_id,
                        'role' => 'owner',
                        'is_active' => true,
                        'joined_at' => $business->created_at ?? now(),
                    ]);
                } elseif ($ownerMember->role !== 'owner') {
                    $ownerMember->update(['role' => 'owner']);
                }

                // Reassign any unauthorized 'owner' records for other users in this business to 'manager'
                $otherOwners = BusinessMember::withoutGlobalScopes()
                    ->where('business_id', $business->id)
                    ->where('user_id', '!=', $business->owner_id)
                    ->where('role', 'owner')
                    ->get();

                foreach ($otherOwners as $invalidMember) {
                    Log::warning("Reconciling duplicate owner for business '{$business->id}': user '{$invalidMember->user_id}' reassigned from 'owner' to 'manager'.");
                    $invalidMember->update(['role' => 'manager']);
                }
            }
        } catch (\Throwable $e) {
            Log::info("Migration data reconciliation notice: " . $e->getMessage());
        }

        // 2. Add partial unique index: exactly one owner role per business
        $driver = DB::getDriverName();
        if ($driver === 'pgsql' || $driver === 'sqlite') {
            DB::statement("CREATE UNIQUE INDEX IF NOT EXISTS unique_business_single_owner ON business_members (business_id) WHERE role = 'owner'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();
        if ($driver === 'pgsql' || $driver === 'sqlite') {
            DB::statement("DROP INDEX IF EXISTS unique_business_single_owner");
        }
    }
};
