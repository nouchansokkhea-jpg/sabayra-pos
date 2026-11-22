<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\User;
use App\Services\Qr\QrCodeService;
use App\Services\Tenant\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QrCodeServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_qr_code_service_generates_correct_menu_and_table_urls(): void
    {
        $user = User::factory()->create();
        $business = Business::create([
            'owner_id' => $user->id,
            'name' => 'KTV Club',
            'slug' => 'ktv-club',
            'status' => 'active',
        ]);

        config(['menuhub.qr_base_url' => 'http://172.20.10.3:8000']);

        $menuUrl = QrCodeService::getMenuUrl($business);
        $this->assertEquals('http://172.20.10.3:8000/ktv-club', $menuUrl);

        $tableUrl = QrCodeService::getTableUrl($business, '01');
        $this->assertEquals('http://172.20.10.3:8000/ktv-club/table/01', $tableUrl);
    }

    public function test_merchant_can_download_master_qr_png_and_view_standee(): void
    {
        $user = User::factory()->create();
        $business = Business::create([
            'owner_id' => $user->id,
            'name' => 'KTV Club',
            'slug' => 'ktv-club',
            'status' => 'active',
        ]);

        TenantContext::set($business);

        $response = $this->actingAs($user)
            ->withSession(['active_business_id' => $business->id])
            ->get(route('business.tables.master.download-png'));
        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/png');

        $standeeResponse = $this->actingAs($user)
            ->withSession(['active_business_id' => $business->id])
            ->get(route('business.tables.master.standee'));
        $standeeResponse->assertOk();
        $standeeResponse->assertSee('Master Digital Menu');
        $standeeResponse->assertSee('KTV Club');
    }

    public function test_merchant_can_download_table_qr_png_and_view_standee(): void
    {
        $user = User::factory()->create();
        $business = Business::create([
            'owner_id' => $user->id,
            'name' => 'KTV Club',
            'slug' => 'ktv-club',
            'status' => 'active',
        ]);

        TenantContext::set($business);

        $table = \App\Models\RestaurantTable::create([
            'business_id' => $business->id,
            'table_number' => '07',
            'capacity' => 4,
            'status' => 'available',
        ]);

        $response = $this->actingAs($user)
            ->withSession(['active_business_id' => $business->id])
            ->get(route('business.tables.download-png', $table));
        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/png');

        $standeeResponse = $this->actingAs($user)
            ->withSession(['active_business_id' => $business->id])
            ->get(route('business.tables.standee', $table));
        $standeeResponse->assertOk();
        $standeeResponse->assertSee('Table #07');
        $standeeResponse->assertSee('KTV Club');
    }
}
