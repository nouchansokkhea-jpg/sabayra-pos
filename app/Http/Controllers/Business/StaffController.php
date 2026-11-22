<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\BusinessBranch;
use App\Models\BusinessMember;
use App\Models\User;
use App\Services\Business\OwnershipTransferService;
use App\Services\Tenant\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class StaffController extends Controller
{
    /**
     * List business staff members, authoritative owner, and their roles.
     */
    public function index(): View
    {
        $business = TenantContext::get();

        // Ensure authoritative owner has an explicit membership record
        $business->ensureOwnerMembership();

        // 1. Authoritative single business owner
        $owner = $business->owner;
        $ownerMember = $business->ownerMember()->with(['user', 'branch'])->first();

        // 2. Staff members strictly scoped to THIS business, excluding the primary owner
        $staffMembers = $business->staffMembers()
            ->with(['user', 'branch'])
            ->latest('created_at')
            ->get();

        // 3. Active branches belonging to this business
        $branches = BusinessBranch::where('business_id', $business->id)
            ->where('is_active', true)
            ->get();

        $currentUser = Auth::user();
        $isOwner = $currentUser && ((int) $currentUser->id === (int) $business->owner_id || $currentUser->isSuperAdmin());
        $currentUserRole = $currentUser ? $currentUser->roleInBusiness($business) : null;
        $canManageStaff = $isOwner || in_array($currentUserRole, ['owner', 'manager'], true);

        return view('business.staff.index', compact(
            'business',
            'owner',
            'ownerMember',
            'staffMembers',
            'branches',
            'isOwner',
            'canManageStaff'
        ));
    }

    /**
     * Invite / Add a staff member to this business.
     * Note: 'owner' role is strictly prohibited from staff creation.
     */
    public function store(Request $request): RedirectResponse
    {
        $business = TenantContext::get();
        $currentUser = Auth::user();
        $currentUserRole = $currentUser ? $currentUser->roleInBusiness($business) : null;

        if (!$currentUser || (!in_array($currentUserRole, ['owner', 'manager'], true) && !$currentUser->isSuperAdmin())) {
            abort(403, 'You do not have permission to add staff members.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'role' => ['required', 'string', 'in:manager,cashier,waiter,kitchen,inventory'],
            'branch_id' => ['nullable', 'exists:business_branches,id'],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        // Find or create the user account
        $user = User::where('email', $validated['email'])->first();

        if (!$user) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'password' => Hash::make($validated['password'] ?? Str::random(12)),
                'status' => 'active',
            ]);
        }

        // Prevent assigning staff roles to the business owner
        if ((int) $user->id === (int) $business->owner_id) {
            return back()->with('error', "User '{$user->email}' is already the designated primary owner of this business.");
        }

        // Check if already a member of this business
        if ($business->members()->where('user_id', $user->id)->exists()) {
            return back()->with('error', "User '{$user->email}' is already a team member in this business.");
        }

        // Attach staff membership
        BusinessMember::create([
            'business_id' => $business->id,
            'user_id' => $user->id,
            'role' => $validated['role'],
            'branch_id' => $validated['branch_id'] ?? null,
            'is_active' => true,
            'joined_at' => now(),
        ]);

        // Optional Spatie role assignment
        $spatieRole = \Spatie\Permission\Models\Role::where('name', $validated['role'])->first();
        if ($spatieRole && !$user->hasRole($validated['role'])) {
            $user->assignRole($validated['role']);
        }

        return back()->with('success', "Staff member '{$user->name}' successfully added as " . ucfirst($validated['role']) . ".");
    }

    /**
     * Update member role, branch, or active status.
     * Note: 'owner' role is not assignable through staff edit.
     */
    public function update(Request $request, BusinessMember $staff): RedirectResponse
    {
        $member = $staff;
        $business = TenantContext::get();
        $currentUser = Auth::user();

        // Enforce tenant boundary
        if ($member->business_id !== $business->id) {
            abort(403, 'Unauthorized access to member outside current workspace.');
        }

        // The primary owner cannot be modified via staff update
        if ((int) $member->user_id === (int) $business->owner_id) {
            return back()->with('error', 'The primary business owner role cannot be edited here. Use Ownership Transfer instead.');
        }

        $currentUserRole = $currentUser ? $currentUser->roleInBusiness($business) : null;
        if (!$currentUser || (!in_array($currentUserRole, ['owner', 'manager'], true) && !$currentUser->isSuperAdmin())) {
            abort(403, 'You do not have permission to edit staff members.');
        }

        $validated = $request->validate([
            'role' => ['required', 'string', 'in:manager,cashier,waiter,kitchen,inventory'],
            'branch_id' => ['nullable', 'exists:business_branches,id'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ($request->has('is_active')) {
            $validated['is_active'] = $request->boolean('is_active');
        }

        $member->update($validated);

        return back()->with('success', "Staff permissions updated for '{$member->user->name}'.");
    }

    /**
     * Remove member from this business.
     */
    public function destroy(BusinessMember $staff): RedirectResponse
    {
        $member = $staff;
        $business = TenantContext::get();
        $currentUser = Auth::user();

        // Enforce tenant boundary
        if ($member->business_id !== $business->id) {
            abort(403, 'Unauthorized access to member outside current workspace.');
        }

        if ((int) $member->user_id === (int) $business->owner_id) {
            return back()->with('error', 'Cannot remove the primary business owner from the business.');
        }

        $isOwner = $currentUser && ((int) $currentUser->id === (int) $business->owner_id || $currentUser->isSuperAdmin());
        if (!$isOwner) {
            abort(403, 'Only the primary business owner can remove team members.');
        }

        $name = $member->user->name ?? 'Staff member';
        $member->delete();

        return back()->with('success', "Staff member '{$name}' was removed from this business.");
    }

    /**
     * Transfer business ownership to another user.
     */
    public function transferOwnership(Request $request, OwnershipTransferService $transferService): RedirectResponse
    {
        $business = TenantContext::get();
        $currentUser = Auth::user();

        $isCurrentOwner = $currentUser && ((int) $currentUser->id === (int) $business->owner_id);
        $isSuperAdmin = $currentUser && $currentUser->isSuperAdmin();

        if (!$isCurrentOwner && !$isSuperAdmin) {
            abort(403, 'Only the current business owner can initiate an ownership transfer.');
        }

        $validated = $request->validate([
            'new_owner_id' => ['required', 'exists:users,id'],
            'previous_owner_role' => ['required', 'string', 'in:manager,cashier,waiter,kitchen,inventory'],
            'password' => [$isSuperAdmin ? 'nullable' : 'required', 'string'],
        ]);

        // Verify current password for security
        if (!$isSuperAdmin && !Hash::check($validated['password'], $currentUser->password)) {
            return back()->withErrors(['password' => 'The provided password was incorrect. Ownership transfer canceled.'])
                ->with('error', 'Incorrect password confirmation.');
        }

        $newOwner = User::findOrFail($validated['new_owner_id']);

        try {
            $transferService->transfer($business, $newOwner, $currentUser, $validated['previous_owner_role']);

            return back()->with('success', "Business ownership has been successfully transferred to {$newOwner->name} ({$newOwner->email}). You are now assigned as " . ucfirst($validated['previous_owner_role']) . ".");
        } catch (\Throwable $e) {
            return back()->with('error', 'Ownership transfer failed: ' . $e->getMessage());
        }
    }
}
