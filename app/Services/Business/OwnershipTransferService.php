<?php

namespace App\Services\Business;

use App\Models\Business;
use App\Models\BusinessMember;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class OwnershipTransferService
{
    /**
     * Transfer business ownership to a new owner atomically.
     *
     * @param Business $business The business whose ownership is being transferred
     * @param User $newOwner The user receiving ownership
     * @param User $authorizingUser The user authorizing the transfer (must be current owner or super admin)
     * @param string $previousOwnerRole The new role for the previous owner ('manager', 'cashier', etc.)
     * @return Business
     *
     * @throws InvalidArgumentException
     */
    public function transfer(
        Business $business,
        User $newOwner,
        User $authorizingUser,
        string $previousOwnerRole = 'manager'
    ): Business {
        // 1. Authorization guard
        $isCurrentOwner = (int) $business->owner_id === (int) $authorizingUser->id;
        $isSuperAdmin = $authorizingUser->isSuperAdmin();

        if (!$isCurrentOwner && !$isSuperAdmin) {
            throw new InvalidArgumentException("Unauthorized: Only the designated business owner or a platform super administrator can transfer ownership.");
        }

        // 2. Identity validation: Cannot transfer to oneself
        if ((int) $business->owner_id === (int) $newOwner->id) {
            throw new InvalidArgumentException("The selected user '{$newOwner->name}' is already the primary owner of this business.");
        }

        // 3. User status validation
        if ($newOwner->status && $newOwner->status !== 'active') {
            throw new InvalidArgumentException("Cannot transfer ownership to inactive user account '{$newOwner->email}'.");
        }

        // 4. Validate previous owner's demoted role
        $allowedStaffRoles = ['manager', 'cashier', 'waiter', 'kitchen', 'inventory'];
        if (!in_array($previousOwnerRole, $allowedStaffRoles, true)) {
            $previousOwnerRole = 'manager';
        }

        return DB::transaction(function () use ($business, $newOwner, $authorizingUser, $previousOwnerRole) {
            $oldOwnerId = $business->owner_id;

            // Step A: Reassign old owner's membership to non-owner role first
            // This ensures the partial unique index (unique_business_single_owner) is never violated
            BusinessMember::withoutGlobalScopes()
                ->where('business_id', $business->id)
                ->where('user_id', $oldOwnerId)
                ->update([
                    'role' => $previousOwnerRole,
                    'is_active' => true,
                ]);

            // Step B: Update business authoritative owner_id
            $business->update([
                'owner_id' => $newOwner->id,
            ]);

            // Step C: Upsert new owner's business membership with 'owner' role
            $newOwnerMember = BusinessMember::withoutGlobalScopes()
                ->where('business_id', $business->id)
                ->where('user_id', $newOwner->id)
                ->first();

            if ($newOwnerMember) {
                $newOwnerMember->update([
                    'role' => 'owner',
                    'is_active' => true,
                ]);
            } else {
                BusinessMember::withoutGlobalScopes()->create([
                    'business_id' => $business->id,
                    'user_id' => $newOwner->id,
                    'role' => 'owner',
                    'is_active' => true,
                    'joined_at' => now(),
                ]);
            }

            // Step D: Assign Spatie Role 'owner' to new owner if role exists in system
            $spatieOwnerRole = \Spatie\Permission\Models\Role::where('name', 'owner')->first();
            if ($spatieOwnerRole && !$newOwner->hasRole('owner')) {
                $newOwner->assignRole('owner');
            }

            Log::info("Ownership transfer completed: Business '{$business->name}' [{$business->id}] transferred from User #{$oldOwnerId} to User #{$newOwner->id} ({$newOwner->email}) by User #{$authorizingUser->id}. Previous owner demoted to '{$previousOwnerRole}'.");

            return $business->fresh(['owner', 'members']);
        });
    }
}
