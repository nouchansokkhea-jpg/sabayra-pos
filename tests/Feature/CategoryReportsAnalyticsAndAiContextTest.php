<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessMember;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Reservation;
use App\Models\User;
use App\Services\AI\AIContextService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryReportsAnalyticsAndAiContextTest extends TestCase
{
    use RefreshDatabase;

    public function test_services_reports_analytics_and_ai_context_reflect_bookings_and_staff(): void
    {
        $owner = User::factory()->create(['name' => 'Sokha Stylist']);
        $salon = Business::create([
            'owner_id' => $owner->id,
            'name' => 'Lotus Beauty & Spa',
            'slug' => 'lotus-spa',
            'business_type' => 'services',
            'status' => 'active',
            'default_currency' => 'USD',
            'exchange_rate_khr' => 4100,
        ]);

        $staff = BusinessMember::create([
            'business_id' => $salon->id,
            'user_id' => $owner->id,
            'role' => 'owner',
            'is_active' => true,
        ]);

        $cat = Category::create([
            'business_id' => $salon->id,
            'name_en' => 'Facials',
            'slug' => 'facials',
            'is_active' => true,
        ]);

        $service = Product::create([
            'business_id' => $salon->id,
            'category_id' => $cat->id,
            'name_en' => 'Gold Glow Facial',
            'slug' => 'gold-glow-facial',
            'product_type' => 'service',
            'duration_minutes' => 60,
            'base_price' => 40.00,
            'is_available' => true,
        ]);

        Reservation::create([
            'business_id' => $salon->id,
            'booking_type' => 'service',
            'booking_code' => 'BK-RPT-1',
            'customer_name' => 'Bopha',
            'customer_phone' => '012445566',
            'reservation_date' => now()->toDateString(),
            'reservation_time' => '10:00',
            'duration_minutes' => 60,
            'price_usd' => 40.00,
            'service_product_id' => $service->id,
            'staff_member_id' => $staff->id,
            'status' => 'completed',
        ]);

        Reservation::create([
            'business_id' => $salon->id,
            'booking_type' => 'service',
            'booking_code' => 'BK-RPT-2',
            'customer_name' => 'Vanna',
            'customer_phone' => '012889900',
            'reservation_date' => now()->addDay()->toDateString(),
            'reservation_time' => '14:00',
            'duration_minutes' => 60,
            'price_usd' => 40.00,
            'service_product_id' => $service->id,
            'staff_member_id' => $staff->id,
            'status' => 'confirmed',
        ]);

        // Reports page
        $reportResponse = $this->actingAs($owner)
            ->withSession(['active_business_id' => $salon->id])
            ->get(route('business.reports.index', ['period' => 'month']));

        $reportResponse->assertOk();
        $reportResponse->assertSee('Service Bookings & Staff Performance');
        $reportResponse->assertSee('Sokha Stylist');
        $reportResponse->assertSee('$40.00');

        // Analytics page
        $analyticsResponse = $this->actingAs($owner)
            ->withSession(['active_business_id' => $salon->id])
            ->get(route('business.analytics.index'));

        $analyticsResponse->assertOk();
        $analyticsResponse->assertSee('Most Requested Services');
        $analyticsResponse->assertSee('Gold Glow Facial');

        // AI Context enrichment
        $aiContext = app(AIContextService::class)->buildCompactBusinessContext($salon);
        $this->assertSame('services', $aiContext['business_type']);
        $this->assertSame('booking', $aiContext['storefront_mode']);
        $this->assertSame('services', $aiContext['report_primary_focus']);
        $this->assertSame(1, $aiContext['upcoming_service_bookings_count']);
    }

    public function test_clothing_and_retail_reports_show_size_color_and_stock_valuation(): void
    {
        $owner = User::factory()->create();
        $boutique = Business::create([
            'owner_id' => $owner->id,
            'name' => 'Naga Apparel',
            'slug' => 'naga-apparel',
            'business_type' => 'clothing',
            'status' => 'active',
            'default_currency' => 'USD',
            'exchange_rate_khr' => 4100,
        ]);

        BusinessMember::create([
            'business_id' => $boutique->id,
            'user_id' => $owner->id,
            'role' => 'owner',
            'is_active' => true,
        ]);

        $cat = Category::create([
            'business_id' => $boutique->id,
            'name_en' => 'Jackets',
            'slug' => 'jackets',
            'is_active' => true,
        ]);

        $shirt = Product::create([
            'business_id' => $boutique->id,
            'category_id' => $cat->id,
            'name_en' => 'Linen Blazer',
            'slug' => 'linen-blazer',
            'brand' => 'Naga',
            'base_price' => 45.00,
            'is_available' => true,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $shirt->id,
            'name_en' => 'XL / Sand',
            'size' => 'XL',
            'color' => 'Sand',
            'stock_quantity' => 8,
        ]);

        $order = Order::create([
            'business_id' => $boutique->id,
            'order_number' => 'ORD-FASH-1',
            'order_type' => 'takeaway',
            'subtotal' => 90.00,
            'total_amount' => 90.00,
            'total_khr' => 369000,
            'exchange_rate_khr' => 4100,
            'payment_method' => 'cash',
            'payment_status' => 'paid',
            'status' => 'completed',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $shirt->id,
            'variant_id' => $variant->id,
            'product_name' => 'Linen Blazer',
            'variant_name' => 'XL / Sand',
            'unit_price' => 45.00,
            'quantity' => 2,
            'subtotal' => 90.00,
        ]);

        $report = $this->actingAs($owner)
            ->withSession(['active_business_id' => $boutique->id])
            ->get(route('business.reports.index'));

        $report->assertOk();
        $report->assertSee('Fashion Sales by Size & Color');
        $report->assertSee('XL / Sand');
        $report->assertSee('$90.00');
    }
}
