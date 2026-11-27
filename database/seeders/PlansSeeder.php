<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlansSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'name' => 'FREE TRIAL',
                'slug' => 'free',
                'description' => 'Ideal for new pop-up stalls, home businesses, and testing MenuHub.',
                'monthly_price' => 0.00,
                'yearly_price' => 0.00,
                'product_limit' => 20,
                'branch_limit' => 1,
                'staff_limit' => 1,
                'has_pos' => false,
                'has_inventory' => false,
                'has_online_ordering' => true,
                'has_analytics' => false,
                'has_custom_branding' => false,
                'has_ai_assistant' => true,
                'has_ai_menu_creator' => true,
                'has_ai_marketing' => false,
                'has_ai_forecasting' => false,
                'ai_monthly_credits' => 30,
                'ai_max_tokens_per_request' => 1536,
                'is_active' => true,
                'is_popular' => false,
                'sort_order' => 1,
            ],
            [
                'name' => 'BASIC',
                'slug' => 'basic',
                'description' => 'Essential Digital Menu and QR Ordering for Cafés, Milk Tea & Small Eateries.',
                'monthly_price' => 15.00,
                'yearly_price' => 150.00,
                'product_limit' => 100,
                'branch_limit' => 1,
                'staff_limit' => 3,
                'has_pos' => true,
                'has_inventory' => false,
                'has_online_ordering' => true,
                'has_analytics' => true,
                'has_custom_branding' => false,
                'has_ai_assistant' => true,
                'has_ai_menu_creator' => true,
                'has_ai_marketing' => true,
                'has_ai_forecasting' => false,
                'ai_monthly_credits' => 150,
                'ai_max_tokens_per_request' => 2048,
                'is_active' => true,
                'is_popular' => false,
                'sort_order' => 2,
            ],
            [
                'name' => 'PRO (Most Popular)',
                'slug' => 'pro',
                'description' => 'Full-featured package with high-speed POS, Inventory & Recipe control.',
                'monthly_price' => 29.00,
                'yearly_price' => 290.00,
                'product_limit' => 500,
                'branch_limit' => 3,
                'staff_limit' => 10,
                'has_pos' => true,
                'has_inventory' => true,
                'has_online_ordering' => true,
                'has_analytics' => true,
                'has_custom_branding' => true,
                'has_ai_assistant' => true,
                'has_ai_menu_creator' => true,
                'has_ai_marketing' => true,
                'has_ai_forecasting' => true,
                'ai_monthly_credits' => 500,
                'ai_max_tokens_per_request' => 4096,
                'is_active' => true,
                'is_popular' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'BUSINESS ENTERPRISE',
                'slug' => 'business',
                'description' => 'Unlimited scale for high-volume restaurant chains, supermarkets, and franchises.',
                'monthly_price' => 49.00,
                'yearly_price' => 490.00,
                'product_limit' => 2000,
                'branch_limit' => 10,
                'staff_limit' => 50,
                'has_pos' => true,
                'has_inventory' => true,
                'has_online_ordering' => true,
                'has_analytics' => true,
                'has_custom_branding' => true,
                'has_ai_assistant' => true,
                'has_ai_menu_creator' => true,
                'has_ai_marketing' => true,
                'has_ai_forecasting' => true,
                'ai_monthly_credits' => 2000,
                'ai_max_tokens_per_request' => 4096,
                'is_active' => true,
                'is_popular' => false,
                'sort_order' => 4,
            ],
        ];

        foreach ($plans as $planData) {
            Plan::firstOrCreate(['slug' => $planData['slug']], $planData);
        }
    }
}
