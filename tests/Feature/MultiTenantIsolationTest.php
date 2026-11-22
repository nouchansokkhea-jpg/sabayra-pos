<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\Tenant\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiTenantIsolationTest extends TestCase
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

    public function test_tenant_context_can_be_set_and_cleared(): void
    {
        $user = User::factory()->create();
        $business = Business::create([
            'owner_id' => $user->id,
            'name' => 'Angkor Café',
            'slug' => 'angkor-cafe',
            'business_type' => 'cafe',
            'status' => 'active',
        ]);

        $this->assertFalse(TenantContext::check());

        TenantContext::set($business);
        $this->assertTrue(TenantContext::check());
        $this->assertSame($business->id, TenantContext::id());

        TenantContext::clear();
        $this->assertFalse(TenantContext::check());
    }

    public function test_models_automatically_scoped_by_tenant(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $businessA = Business::create([
            'owner_id' => $user1->id,
            'name' => 'Business Alpha',
            'slug' => 'business-alpha',
            'status' => 'active',
        ]);

        $businessB = Business::create([
            'owner_id' => $user2->id,
            'name' => 'Business Beta',
            'slug' => 'business-beta',
            'status' => 'active',
        ]);

        // Create product in Business A
        TenantContext::set($businessA);
        $categoryA = Category::create([
            'name_en' => 'Drinks A',
            'slug' => 'drinks-a',
        ]);
        $productA = Product::create([
            'category_id' => $categoryA->id,
            'name_en' => 'Iced Tea Alpha',
            'slug' => 'iced-tea-alpha',
            'base_price' => 2.50,
        ]);
        $this->assertSame($businessA->id, $productA->business_id);

        // Create product in Business B
        TenantContext::set($businessB);
        $categoryB = Category::create([
            'name_en' => 'Drinks B',
            'slug' => 'drinks-b',
        ]);
        $productB = Product::create([
            'category_id' => $categoryB->id,
            'name_en' => 'Iced Tea Beta',
            'slug' => 'iced-tea-beta',
            'base_price' => 3.00,
        ]);
        $this->assertSame($businessB->id, $productB->business_id);

        // Verify Business B can only see Product B
        $bProducts = Product::all();
        $this->assertCount(1, $bProducts);
        $this->assertTrue($bProducts->contains('id', $productB->id));
        $this->assertFalse($bProducts->contains('id', $productA->id));

        // Switch to Business A: verify Business A can only see Product A
        TenantContext::set($businessA);
        $aProducts = Product::all();
        $this->assertCount(1, $aProducts);
        $this->assertTrue($aProducts->contains('id', $productA->id));
        $this->assertFalse($aProducts->contains('id', $productB->id));

        TenantContext::clear();
    }

    public function test_cambodian_riel_currency_conversion(): void
    {
        $user = User::factory()->create();
        $business = Business::create([
            'owner_id' => $user->id,
            'name' => 'Mekong Bistro',
            'slug' => 'mekong-bistro',
            'exchange_rate_khr' => 4100.00,
            'status' => 'active',
        ]);

        // $10.00 USD at 4,100 rate = 41,000 KHR
        $khr = $business->khrEquivalent(10.00);
        $this->assertEquals(41000.00, $khr);

        $this->assertSame('$10.00', $business->formatUsd(10.00));
        $this->assertSame('41,000 ៛', $business->formatKhr(41000.00));
    }

    public function test_user_ownership_and_membership(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();

        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => 'Bayon Restaurant',
            'slug' => 'bayon-restaurant',
            'status' => 'active',
        ]);

        $this->assertTrue($owner->isOwnerOf($business));
        $this->assertTrue($owner->belongsToBusiness($business));
        $this->assertSame('owner', $owner->roleInBusiness($business));

        $this->assertFalse($stranger->isOwnerOf($business));
        $this->assertFalse($stranger->belongsToBusiness($business));
        $this->assertNull($stranger->roleInBusiness($business));
    }
}
