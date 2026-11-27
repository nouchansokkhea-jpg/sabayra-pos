<?php

namespace Tests\Feature;

use App\Models\AiGeneratedContent;
use App\Models\Business;
use App\Models\Category;
use App\Models\PlatformSetting;
use App\Models\Product;
use App\Models\User;
use App\Services\Tenant\TenantContext;
use Database\Seeders\PlansSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SabayraAiMenuCreatorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(PlansSeeder::class);
        TenantContext::clear();
    }

    public function test_menu_creator_generates_bilingual_khmer_english_draft_and_applies_only_on_explicit_confirmation(): void
    {
        $owner = User::factory()->create();
        $business = Business::create([
            'owner_id' => $owner->id,
            'name' => 'Angkor Milk Tea',
            'slug' => 'angkor-milk-tea',
            'business_type' => 'cafe',
            'status' => 'active',
        ]);

        $category = Category::create([
            'business_id' => $business->id,
            'name_en' => 'Milk Tea',
            'slug' => 'milk-tea',
            'is_active' => true,
        ]);

        $product = Product::create([
            'business_id' => $business->id,
            'category_id' => $category->id,
            'name_en' => 'Brown Sugar Boba',
            'slug' => 'brown-sugar-boba',
            'base_price' => 2.75,
            'is_available' => true,
        ]);

        PlatformSetting::setValue('ai.gemini_api_key', 'AIzaSyMenuCreatorTestKey1234567890', true);

        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [[
                        'text' => json_encode([
                            'name_en' => 'Brown Sugar Boba Milk Tea',
                            'name_km' => 'តែទឹកដោះគោគុជស្ករត្នោត',
                            'description_en' => 'Silky fresh milk layered with warm caramelized brown sugar tapioca pearls.',
                            'description_km' => 'តែទឹកដោះគោស្រស់ឈ្ងុយឆ្ងាញ់ លាយជាមួយគុជស្ករត្នោតទន់ល្មើយ។',
                            'marketing_hook' => 'Customer favorite afternoon sweet pick-me-up.',
                            'suggestions' => [],
                        ], JSON_UNESCAPED_UNICODE),
                    ]]],
                    'finishReason' => 'STOP',
                ]],
                'usageMetadata' => ['promptTokenCount' => 95, 'candidatesTokenCount' => 75, 'totalTokenCount' => 170],
            ], 200),
        ]);

        // Page renders cleanly
        $this->actingAs($owner)
            ->withSession(['active_business_id' => $business->id])
            ->get(route('business.ai.menu-creator'))
            ->assertOk()
            ->assertSee('Menu Copy &amp; Combo Studio', false);

        // Generate draft (must NOT overwrite Product automatically)
        $gen = $this->actingAs($owner)
            ->withSession(['active_business_id' => $business->id])
            ->postJson(route('business.ai.menu-creator.generate'), [
                'action' => 'description',
                'product_id' => $product->id,
                'target_language' => 'bilingual',
                'tone' => 'friendly',
            ]);

        $gen->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('content.status', 'draft')
            ->assertJsonPath('content.structured.name_km', 'តែទឹកដោះគោគុជស្ករត្នោត');

        $product->refresh();
        $this->assertNull($product->description_km);

        $draft = AiGeneratedContent::withoutGlobalScopes()->findOrFail($gen->json('content.id'));

        // Explicitly apply selected fields
        $apply = $this->actingAs($owner)
            ->withSession(['active_business_id' => $business->id])
            ->post(route('business.ai.menu-creator.apply', $draft), [
                'product_id' => $product->id,
                'apply_name_km' => '1',
                'apply_description_km' => '1',
                'apply_description_en' => '1',
            ]);

        $apply->assertRedirect();

        $product->refresh();
        $this->assertSame('តែទឹកដោះគោគុជស្ករត្នោត', $product->name_km);
        $this->assertStringContainsString('គុជស្ករត្នោត', (string) $product->description_km);
        $this->assertSame('applied', $draft->fresh()->status);
    }
}
