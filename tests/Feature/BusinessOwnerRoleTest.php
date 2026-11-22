<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\BusinessMember;
use App\Models\User;
use App\Services\Business\OwnershipTransferService;
use App\Services\Tenant\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;
use Tests\TestCase;

class BusinessOwnerRoleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        TenantContext::clear();
    }

    protected function tearDown(): void
    {
        TenantContext::clear();
        parent::tearDown();
    }

    private function createBusinessWithOwner(string $name = 'Bayon Bistro', string $slug = 'bayon-bistro'): array
    {
        $owner = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => $name,
            'slug' => $slug,
            'status' => 'active',
        ]);

        $branch = BusinessBranch::create([
            'business_id' => $business->id,
            'name' => 'Main Branch',
            'is_main' => true,
            'is_active' => true,
        ]);

        $ownerMember = BusinessMember::create([
            'business_id' => $business->id,
            'user_id' => $owner->id,
            'role' => 'owner',
            'branch_id' => $branch->id,
            'is_active' => true,
            'joined_at' => now(),
        ]);

        return [$business, $owner, $branch, $ownerMember];
    }

    public function test_each_business_has_authoritative_single_owner(): void
    {
        [$business, $owner] = $this->createBusinessWithOwner();

        $this->assertSame($owner->id, $business->owner_id);
        $this->assertSame($owner->id, $business->owner->id);
        $this->assertTrue($owner->isOwnerOf($business));
        $this->assertSame('owner', $owner->roleInBusiness($business));

        // Exactly one member has the owner role in this business
        $ownerMembers = BusinessMember::where('business_id', $business->id)->where('role', 'owner')->get();
        $this->assertCount(1, $ownerMembers);
        $this->assertSame($owner->id, $ownerMembers->first()->user_id);
    }

    public function test_ordinary_staff_cannot_receive_owner_role_via_staff_creation(): void
    {
        [$business, $owner] = $this->createBusinessWithOwner();

        TenantContext::set($business);

        $response = $this->actingAs($owner)
            ->withSession(['active_business_id' => $business->id])
            ->post(route('business.staff.store'), [
                'name' => 'Sneaky Staff',
                'email' => 'sneaky@example.com',
                'role' => 'owner', // Should fail validation
                'password' => 'secret12345',
            ]);

        $response->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('business_members', [
            'business_id' => $business->id,
            'role' => 'owner',
            'user_id' => User::where('email', 'sneaky@example.com')->value('id') ?? -1,
        ]);
    }

    public function test_ordinary_staff_cannot_be_updated_to_owner_role(): void
    {
        [$business, $owner, $branch] = $this->createBusinessWithOwner();

        $cashierUser = User::factory()->create();
        $staffMember = BusinessMember::create([
            'business_id' => $business->id,
            'user_id' => $cashierUser->id,
            'role' => 'cashier',
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);

        TenantContext::set($business);

        $response = $this->actingAs($owner)
            ->withSession(['active_business_id' => $business->id])
            ->put(route('business.staff.update', $staffMember), [
                'role' => 'owner', // Not allowed in validation
                'branch_id' => $branch->id,
                'is_active' => true,
            ]);

        $response->assertSessionHasErrors('role');
        $this->assertSame('cashier', $staffMember->fresh()->role);
    }

    public function test_model_validation_prevents_assigning_owner_role_to_non_owner(): void
    {
        [$business] = $this->createBusinessWithOwner();
        $stranger = User::factory()->create();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("The 'owner' role is reserved strictly for the designated business owner");

        BusinessMember::create([
            'business_id' => $business->id,
            'user_id' => $stranger->id,
            'role' => 'owner',
            'is_active' => true,
        ]);
    }

    public function test_database_partial_unique_index_prevents_multiple_owners(): void
    {
        [$business] = $this->createBusinessWithOwner();
        $anotherUser = User::factory()->create();

        // Attempting direct DB insert with role 'owner' for same business_id should violate unique index
        $this->expectException(\Illuminate\Database\QueryException::class);

        DB::table('business_members')->insert([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'business_id' => $business->id,
            'user_id' => $anotherUser->id,
            'role' => 'owner',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_different_businesses_have_independent_owners(): void
    {
        [$businessA, $ownerA] = $this->createBusinessWithOwner('Business A', 'biz-a');
        [$businessB, $ownerB] = $this->createBusinessWithOwner('Business B', 'biz-b');

        $this->assertTrue($ownerA->isOwnerOf($businessA));
        $this->assertFalse($ownerA->isOwnerOf($businessB));

        $this->assertTrue($ownerB->isOwnerOf($businessB));
        $this->assertFalse($ownerB->isOwnerOf($businessA));

        $this->assertSame('owner', $ownerA->roleInBusiness($businessA));
        $this->assertNull($ownerA->roleInBusiness($businessB));

        $this->assertSame('owner', $ownerB->roleInBusiness($businessB));
        $this->assertNull($ownerB->roleInBusiness($businessA));
    }

    public function test_one_user_can_own_multiple_businesses_independently(): void
    {
        $serialEntrepreneur = User::factory()->create();

        $biz1 = Business::create([
            'owner_id' => $serialEntrepreneur->id,
            'name' => 'Franchise 1',
            'slug' => 'franchise-1',
            'status' => 'active',
        ]);
        BusinessMember::create([
            'business_id' => $biz1->id,
            'user_id' => $serialEntrepreneur->id,
            'role' => 'owner',
            'is_active' => true,
        ]);

        $biz2 = Business::create([
            'owner_id' => $serialEntrepreneur->id,
            'name' => 'Franchise 2',
            'slug' => 'franchise-2',
            'status' => 'active',
        ]);
        BusinessMember::create([
            'business_id' => $biz2->id,
            'user_id' => $serialEntrepreneur->id,
            'role' => 'owner',
            'is_active' => true,
        ]);

        $this->assertTrue($serialEntrepreneur->isOwnerOf($biz1));
        $this->assertTrue($serialEntrepreneur->isOwnerOf($biz2));
        $this->assertSame('owner', $serialEntrepreneur->roleInBusiness($biz1));
        $this->assertSame('owner', $serialEntrepreneur->roleInBusiness($biz2));

        $this->assertCount(2, $serialEntrepreneur->ownedBusinesses);
    }

    public function test_staff_list_only_shows_members_of_current_business(): void
    {
        [$businessA, $ownerA, $branchA] = $this->createBusinessWithOwner('Business A', 'biz-a-test');
        [$businessB, $ownerB, $branchB] = $this->createBusinessWithOwner('Business B', 'biz-b-test');

        // Add staff to Business A
        $staffUserA = User::factory()->create(['name' => 'Alice Staff A', 'email' => 'alice@biz-a.com']);
        BusinessMember::create([
            'business_id' => $businessA->id,
            'user_id' => $staffUserA->id,
            'role' => 'cashier',
            'branch_id' => $branchA->id,
            'is_active' => true,
        ]);

        // Add staff to Business B
        $staffUserB = User::factory()->create(['name' => 'Bob Staff B', 'email' => 'bob@biz-b.com']);
        BusinessMember::create([
            'business_id' => $businessB->id,
            'user_id' => $staffUserB->id,
            'role' => 'waiter',
            'branch_id' => $branchB->id,
            'is_active' => true,
        ]);

        TenantContext::set($businessA);

        $response = $this->actingAs($ownerA)
            ->withSession(['active_business_id' => $businessA->id])
            ->get(route('business.staff.index'));

        $response->assertOk();
        // Should see Owner A and Staff A
        $response->assertSee($ownerA->name);
        $response->assertSee('Alice Staff A');

        // Should NOT see Owner B or Staff B
        $response->assertDontSee($ownerB->name);
        $response->assertDontSee('Bob Staff B');
    }

    public function test_ownership_transfer_works_securely_and_atomically(): void
    {
        [$business, $originalOwner, $branch] = $this->createBusinessWithOwner('Transfer Test Kitchen', 'transfer-kitchen');

        $managerUser = User::factory()->create(['password' => Hash::make('password123')]);
        $managerMember = BusinessMember::create([
            'business_id' => $business->id,
            'user_id' => $managerUser->id,
            'role' => 'manager',
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);

        TenantContext::set($business);

        $response = $this->actingAs($originalOwner)
            ->withSession(['active_business_id' => $business->id])
            ->post(route('business.staff.transfer-ownership'), [
                'new_owner_id' => $managerUser->id,
                'previous_owner_role' => 'manager',
                'password' => 'password123',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $business->refresh();

        // 1. New owner is updated on business
        $this->assertSame($managerUser->id, $business->owner_id);
        $this->assertTrue($managerUser->isOwnerOf($business));
        $this->assertSame('owner', $managerUser->roleInBusiness($business));

        // 2. Previous owner is now manager
        $this->assertFalse($originalOwner->isOwnerOf($business));
        $this->assertSame('manager', $originalOwner->roleInBusiness($business));

        // 3. Exactly one owner member exists
        $ownerMembers = BusinessMember::where('business_id', $business->id)->where('role', 'owner')->get();
        $this->assertCount(1, $ownerMembers);
        $this->assertSame($managerUser->id, $ownerMembers->first()->user_id);
    }

    public function test_ownership_transfer_rejects_incorrect_password(): void
    {
        [$business, $owner, $branch] = $this->createBusinessWithOwner('Password Protected', 'pwd-test');

        $newCandidate = User::factory()->create();
        BusinessMember::create([
            'business_id' => $business->id,
            'user_id' => $newCandidate->id,
            'role' => 'cashier',
            'branch_id' => $branch->id,
            'is_active' => true,
        ]);

        TenantContext::set($business);

        $response = $this->actingAs($owner)
            ->withSession(['active_business_id' => $business->id])
            ->post(route('business.staff.transfer-ownership'), [
                'new_owner_id' => $newCandidate->id,
                'previous_owner_role' => 'manager',
                'password' => 'wrong-password',
            ]);

        $response->assertSessionHasErrors('password');
        $business->refresh();
        $this->assertSame($owner->id, $business->owner_id);
    }

    public function test_staff_management_crud_operations(): void
    {
        [$business, $owner, $branch] = $this->createBusinessWithOwner('CRUD Diner', 'crud-diner');

        TenantContext::set($business);

        // 1. Add staff member
        $response = $this->actingAs($owner)
            ->withSession(['active_business_id' => $business->id])
            ->post(route('business.staff.store'), [
                'name' => 'Charlie Waiter',
                'email' => 'charlie@crud.com',
                'phone' => '+855 12 345 678',
                'role' => 'waiter',
                'branch_id' => $branch->id,
                'password' => 'waiterpass123',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $staffUser = User::where('email', 'charlie@crud.com')->first();
        $this->assertNotNull($staffUser);

        $member = BusinessMember::where('business_id', $business->id)->where('user_id', $staffUser->id)->first();
        $this->assertNotNull($member);
        $this->assertSame('waiter', $member->role);

        // 2. Update staff role to cashier and disable
        $updateResponse = $this->actingAs($owner)
            ->withSession(['active_business_id' => $business->id])
            ->put(route('business.staff.update', $member), [
                'role' => 'cashier',
                'branch_id' => $branch->id,
                'is_active' => false,
            ]);

        $updateResponse->assertRedirect();
        $member->refresh();
        $this->assertSame('cashier', $member->role);
        $this->assertFalse($member->is_active);

        // 3. Cannot remove the business owner
        $ownerMember = BusinessMember::where('business_id', $business->id)->where('user_id', $owner->id)->first();
        $deleteOwnerResponse = $this->actingAs($owner)
            ->withSession(['active_business_id' => $business->id])
            ->delete(route('business.staff.destroy', $ownerMember));

        $deleteOwnerResponse->assertSessionHas('error');
        $this->assertDatabaseHas('business_members', ['id' => $ownerMember->id]);

        // 4. Remove staff member
        $deleteResponse = $this->actingAs($owner)
            ->withSession(['active_business_id' => $business->id])
            ->delete(route('business.staff.destroy', $member));

        $deleteResponse->assertRedirect();
        $this->assertDatabaseMissing('business_members', ['id' => $member->id]);
    }
}
