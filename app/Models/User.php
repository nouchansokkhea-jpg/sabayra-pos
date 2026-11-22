<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'avatar_url',
        'is_super_admin',
        'status',
        'preferred_locale',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected static function booted(): void
    {
        static::deleting(function (User $user) {
            foreach ($user->ownedBusinesses as $business) {
                $business->delete();
            }
            $user->businessMemberships()->delete();
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_super_admin' => 'boolean',
        ];
    }

    /**
     * Businesses directly owned by this user.
     */
    public function ownedBusinesses(): HasMany
    {
        return $this->hasMany(Business::class, 'owner_id');
    }

    /**
     * All business membership links for this user.
     */
    public function businessMemberships(): HasMany
    {
        return $this->hasMany(BusinessMember::class, 'user_id');
    }

    /**
     * All businesses where this user is an active member or owner.
     */
    public function businesses(): BelongsToMany
    {
        return $this->belongsToMany(Business::class, 'business_members', 'user_id', 'business_id')
            ->withPivot(['role', 'branch_id', 'is_active', 'joined_at'])
            ->withTimestamps();
    }

    /**
     * Check if user is platform super administrator.
     */
    public function isSuperAdmin(): bool
    {
        return (bool) $this->is_super_admin;
    }

    /**
     * Check if user is the direct owner of a specific business.
     */
    public function isOwnerOf(Business|string $business): bool
    {
        $businessId = $business instanceof Business ? $business->id : $business;
        return $this->ownedBusinesses()->where('id', $businessId)->exists();
    }

    /**
     * Check if user belongs to a business (as owner, manager, or staff).
     */
    public function belongsToBusiness(Business|string $business): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $businessId = $business instanceof Business ? $business->id : $business;

        if ($this->isOwnerOf($businessId)) {
            return true;
        }

        return $this->businessMemberships()
            ->where('business_id', $businessId)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Get the user's role in a specific business.
     */
    public function roleInBusiness(Business|string $business): ?string
    {
        $businessId = $business instanceof Business ? $business->id : $business;

        if ($this->isOwnerOf($businessId)) {
            return 'owner';
        }

        $role = $this->businessMemberships()
            ->where('business_id', $businessId)
            ->where('is_active', true)
            ->value('role');

        return $role === 'owner' ? 'manager' : $role;
    }
}
