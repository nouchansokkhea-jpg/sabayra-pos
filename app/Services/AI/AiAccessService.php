<?php

namespace App\Services\AI;

use App\Models\Business;
use App\Models\User;

class AiAccessService
{
    public const AREAS = [
        'overview',
        'assistant',
        'menu_creator',
        'marketing_studio',
        'inventory_forecast',
        'sales_prediction',
        'usage',
        'settings',
        'insights',
    ];

    /**
     * Determine whether the user is permitted to open a given AI Center area for the business.
     */
    public function canAccess(User $user, Business $business, string $area): bool
    {
        if (! $user->belongsToBusiness($business)) {
            return false;
        }

        if ($user->isSuperAdmin() || $user->isOwnerOf($business)) {
            return true;
        }

        $role = $user->roleInBusiness($business);
        if (in_array($role, ['owner', 'manager'], true)) {
            return true;
        }

        return match ($area) {
            'settings' => $user->canManageBusinessSettings($business),
            'assistant', 'sales_prediction', 'usage', 'insights' => $user->checkPermissionTo('view_business_reports'),
            'menu_creator', 'marketing_studio' => $user->checkPermissionTo('manage_products'),
            'inventory_forecast' => $role === 'inventory' || $user->checkPermissionTo('manage_inventory'),
            'overview' => $this->canAccess($user, $business, 'assistant')
                || $this->canAccess($user, $business, 'menu_creator')
                || $this->canAccess($user, $business, 'inventory_forecast')
                || $this->canAccess($user, $business, 'sales_prediction'),
            default => false,
        };
    }

    /**
     * Return a map of AI Center area keys => boolean permission for sidebar & views.
     *
     * @return array<string, bool>
     */
    public function allowedAreas(User $user, Business $business): array
    {
        $map = [];
        foreach (self::AREAS as $area) {
            $map[$area] = $this->canAccess($user, $business, $area);
        }

        return $map;
    }

    /**
     * Abort with 403 if the user is not authorized for the requested AI Center area.
     */
    public function authorizeArea(User $user, Business $business, string $area): void
    {
        if (! $this->canAccess($user, $business, $area)) {
            abort(403, 'You do not have permission to access this AI Center section.');
        }
    }
}
