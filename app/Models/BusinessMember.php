<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

class BusinessMember extends Model
{
    use HasFactory, HasUuids, BelongsToTenant;

    protected $fillable = [
        'business_id',
        'user_id',
        'role',
        'branch_id',
        'is_active',
        'joined_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'joined_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (BusinessMember $member) {
            $validRoles = ['owner', 'manager', 'cashier', 'waiter', 'kitchen', 'inventory'];

            if ($member->role && !in_array($member->role, $validRoles, true)) {
                throw new InvalidArgumentException("Invalid role '{$member->role}'. Allowed roles: " . implode(', ', $validRoles));
            }

            // Strictly enforce ONE BUSINESS = ONE OWNER rule
            if ($member->role === 'owner') {
                $business = $member->business ?: Business::withoutGlobalScopes()->find($member->business_id);

                if ($business && (int) $member->user_id !== (int) $business->owner_id) {
                    throw new InvalidArgumentException("The 'owner' role is reserved strictly for the designated business owner (User ID: {$business->owner_id}). Staff members cannot hold the owner role.");
                }
            }
        });
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(BusinessBranch::class, 'branch_id');
    }

    /**
     * Scope query to non-owner staff members.
     */
    public function scopeStaff(Builder $query): Builder
    {
        return $query->where('role', '!=', 'owner');
    }

    /**
     * Scope query to active members.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
