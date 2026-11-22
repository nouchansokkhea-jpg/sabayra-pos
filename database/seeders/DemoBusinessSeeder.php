<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\BusinessMember;
use App\Models\Category;
use App\Models\Customer;
use App\Models\InventoryItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Plan;
use App\Models\Product;
use App\Models\ProductAddon;
use App\Models\ProductVariant;
use App\Models\QrCode;
use App\Models\RestaurantTable;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Tenant\TenantContext;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoBusinessSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create Super Admin User
        $superAdmin = User::firstOrCreate(
            ['email' => 'admin@menuhub.asia'],
            [
                'name' => 'MenuHub Super Admin',
                'password' => Hash::make('Password@123'),
                'phone' => '+855 12 888 999',
                'is_super_admin' => true,
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );
        $superAdmin->assignRole('super_admin');

        // 2. Create Business Owner User
        $owner = User::firstOrCreate(
            ['email' => 'owner@romdoul.com'],
            [
                'name' => 'Sokha Chan',
                'password' => Hash::make('Password@123'),
                'phone' => '+855 98 765 432',
                'is_super_admin' => false,
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );
        $owner->assignRole('owner');

        // 3. Create Demo Business
        $business = Business::firstOrCreate(
            ['slug' => 'romdoul-kitchen'],
            [
                'owner_id' => $owner->id,
                'name' => 'Romdoul Khmer Kitchen',
                'business_type' => 'restaurant',
                'tagline' => 'Authentic Khmer Cuisine & Special Coffee',
                'description' => 'Experience genuine Cambodian flavors made with fresh local ingredients in the heart of Phnom Penh.',
                'phone' => '+855 23 999 111',
                'email' => 'info@romdoul.com',
                'telegram_handle' => '@romdoulkitchen',
                'address' => '#45, Street 240, Daun Penh, Phnom Penh',
                'city' => 'Phnom Penh',
                'country' => 'Cambodia',
                'default_currency' => 'USD',
                'exchange_rate_khr' => 4100.00,
                'tax_percent' => 10.00,
                'status' => 'active',
                'theme_settings' => [
                    'primary_color' => '#f97316', // Vibrant Orange
                    'secondary_color' => '#1f2937',
                    'font' => 'Inter, Siemreap, sans-serif',
                    'layout' => 'modern',
                ],
                'opening_hours' => [
                    'monday' => '07:00 AM - 10:00 PM',
                    'tuesday' => '07:00 AM - 10:00 PM',
                    'wednesday' => '07:00 AM - 10:00 PM',
                    'thursday' => '07:00 AM - 10:00 PM',
                    'friday' => '07:00 AM - 10:30 PM',
                    'saturday' => '07:00 AM - 10:30 PM',
                    'sunday' => '07:00 AM - 10:00 PM',
                ],
            ]
        );

        // Bind Tenant Context for Seeder
        TenantContext::set($business);

        // 4. Create Main Branch
        $mainBranch = BusinessBranch::firstOrCreate(
            ['business_id' => $business->id, 'is_main' => true],
            [
                'name' => 'Daun Penh Flagship Branch',
                'phone' => '+855 23 999 111',
                'address' => '#45, Street 240, Daun Penh, Phnom Penh',
                'is_active' => true,
            ]
        );

        // 5. Link Owner Membership
        BusinessMember::firstOrCreate(
            ['business_id' => $business->id, 'user_id' => $owner->id],
            [
                'role' => 'owner',
                'branch_id' => $mainBranch->id,
                'is_active' => true,
                'joined_at' => now(),
            ]
        );

        // 6. Assign Pro Subscription
        $proPlan = Plan::where('slug', 'pro')->first();
        if ($proPlan) {
            Subscription::firstOrCreate(
                ['business_id' => $business->id, 'plan_id' => $proPlan->id],
                [
                    'billing_cycle' => 'monthly',
                    'price' => $proPlan->monthly_price,
                    'status' => 'active',
                    'starts_at' => now(),
                    'expires_at' => now()->addYear(),
                    'payment_method' => 'manual',
                ]
            );
        }

        // 7. Categories
        $categoriesData = [
            ['name_en' => 'Signature Khmer Dishes', 'name_km' => 'មុខម្ហូបខ្មែរប្រចាំហាង', 'slug' => 'signature-dishes', 'sort_order' => 1],
            ['name_en' => 'Soups & Curries', 'name_km' => 'ស៊ុប និងការី', 'slug' => 'soups-curries', 'sort_order' => 2],
            ['name_en' => 'Coffee & Beverages', 'name_km' => 'កាហ្វេ និងភេសជ្ជៈ', 'slug' => 'beverages', 'sort_order' => 3],
            ['name_en' => 'Traditional Desserts', 'name_km' => 'បង្អែមខ្មែរ', 'slug' => 'desserts', 'sort_order' => 4],
        ];

        $categories = [];
        foreach ($categoriesData as $c) {
            $categories[$c['slug']] = Category::firstOrCreate(
                ['business_id' => $business->id, 'slug' => $c['slug']],
                array_merge($c, ['business_id' => $business->id, 'is_active' => true])
            );
        }

        // 8. Add-ons
        $addonRice = ProductAddon::firstOrCreate(
            ['business_id' => $business->id, 'name_en' => 'Extra Jasmine Rice'],
            ['name_km' => 'បាយផ្កាម្លិះបន្ថែម', 'price' => 0.75, 'is_available' => true]
        );
        $addonEgg = ProductAddon::firstOrCreate(
            ['business_id' => $business->id, 'name_en' => 'Fried Egg (Sunny Side Up)'],
            ['name_km' => 'ពងទាចៀន', 'price' => 0.50, 'is_available' => true]
        );
        $addonShot = ProductAddon::firstOrCreate(
            ['business_id' => $business->id, 'name_en' => 'Extra Espresso Shot'],
            ['name_km' => 'កាហ្វេបន្ថែមមួយតង់', 'price' => 0.60, 'is_available' => true]
        );

        // 9. Products
        // Product 1: Fish Amok
        $amok = Product::firstOrCreate(
            ['business_id' => $business->id, 'slug' => 'traditional-fish-amok'],
            [
                'category_id' => $categories['signature-dishes']->id,
                'name_en' => 'Traditional Fish Amok',
                'name_km' => 'អាម៉ុកត្រីបុរាណ',
                'sku' => 'AMOK-001',
                'description_en' => 'Fresh Mekong river fish steamed with fragrant coconut milk, kroeung lemongrass paste, and noni leaves served in a banana leaf basket.',
                'description_km' => 'ត្រីទន្លេស្រស់ចំហុយជាមួយគ្រឿងការីខ្មែរ ខ្ទិះដូងឈ្ងុយឆ្ងាញ់ និងស្លឹកញរៀបចំយ៉ាងប្រណិតក្នុងកន្ទោងស្លឹកចេក។',
                'product_type' => 'food',
                'base_price' => 6.50,
                'cost_price' => 2.50,
                'is_available' => true,
                'is_featured' => true,
                'track_inventory' => false,
                'sort_order' => 1,
            ]
        );
        $amok->addons()->syncWithoutDetaching([$addonRice->id, $addonEgg->id]);

        // Product 2: Beef Lok Lak
        $loklak = Product::firstOrCreate(
            ['business_id' => $business->id, 'slug' => 'kampot-pepper-beef-lok-lak'],
            [
                'category_id' => $categories['signature-dishes']->id,
                'name_en' => 'Kampot Pepper Beef Lok Lak',
                'name_km' => 'ឡុកឡាក់សាច់គោម្រេចកំពត',
                'sku' => 'LOKLAK-001',
                'description_en' => 'Tender diced beef tenderloin wok-tossed in savory garlic-soy glaze, served on crisp lettuce, tomatoes, and Kampot lime pepper sauce.',
                'description_km' => 'សាច់គោផុយឆាជាមួយទឹកស៊ីអ៊ីវខ្ទឹមសឈ្ងុយឆ្ងាញ់ អមដោយសាឡាត់ ត្រសក់ ប៉េងប៉ោះ និងទឹកត្រីកោះកុង/ទឹកម្រេចក្រូចឆ្មាកំពត។',
                'product_type' => 'food',
                'base_price' => 7.00,
                'cost_price' => 3.20,
                'discount_price' => 6.30, // On discount
                'is_available' => true,
                'is_featured' => true,
                'track_inventory' => false,
                'sort_order' => 2,
            ]
        );
        $loklak->addons()->syncWithoutDetaching([$addonRice->id, $addonEgg->id]);

        // Product 3: Iced Khmer Latte / Milk Coffee
        $coffee = Product::firstOrCreate(
            ['business_id' => $business->id, 'slug' => 'iced-khmer-milk-coffee'],
            [
                'category_id' => $categories['beverages']->id,
                'name_en' => 'Iced Khmer Milk Coffee',
                'name_km' => 'កាហ្វេទឹកដោះគោទឹកកក',
                'sku' => 'CAFE-001',
                'description_en' => 'Rich Robusta dark roast filtered traditionally and swirled with creamy sweet condensed milk over crushed ice.',
                'description_km' => 'កាហ្វេរ៉ូប៊ូស្តាស្រស់បុកឈ្ងុយឆ្ងាញ់ លាយជាមួយទឹកដោះគោខាប់ផ្អែមមុតលើទឹកកកម៉ត់។',
                'product_type' => 'beverage',
                'base_price' => 2.00,
                'cost_price' => 0.60,
                'is_available' => true,
                'is_featured' => true,
                'track_inventory' => false,
                'sort_order' => 3,
            ]
        );
        $coffee->addons()->syncWithoutDetaching([$addonShot->id]);

        // Product Variants for Coffee
        ProductVariant::firstOrCreate(
            ['product_id' => $coffee->id, 'name_en' => 'Regular (16oz)'],
            ['name_km' => 'កែវធម្មតា (16oz)', 'price_adjustment' => 0.00, 'is_default' => true]
        );
        ProductVariant::firstOrCreate(
            ['product_id' => $coffee->id, 'name_en' => 'Large (22oz)'],
            ['name_km' => 'កែវធំ (22oz)', 'price_adjustment' => 0.50, 'is_default' => false]
        );

        // 10. Tables & QR Codes
        for ($i = 1; $i <= 6; $i++) {
            $table = RestaurantTable::firstOrCreate(
                ['business_id' => $business->id, 'table_number' => (string) $i],
                [
                    'branch_id' => $mainBranch->id,
                    'name' => "Dining Table {$i}",
                    'capacity' => 4,
                    'status' => 'available',
                    'is_active' => true,
                ]
            );

            QrCode::firstOrCreate(
                ['business_id' => $business->id, 'table_id' => $table->id],
                [
                    'type' => 'table',
                    'code' => "QR-TBL-{$business->slug}-{$i}",
                    'target_url' => url("/{$business->slug}/table/{$i}"),
                ]
            );
        }

        // Business Main QR
        QrCode::firstOrCreate(
            ['business_id' => $business->id, 'type' => 'menu'],
            [
                'code' => "QR-MAIN-{$business->slug}",
                'target_url' => url("/{$business->slug}"),
            ]
        );

        // 11. Inventory Raw Materials
        InventoryItem::firstOrCreate(
            ['business_id' => $business->id, 'sku' => 'ING-COFFEE'],
            [
                'name_en' => 'Mondulkiri Roasted Coffee Beans',
                'name_km' => 'គ្រាប់កាហ្វេលីងមណ្ឌលគិរី',
                'unit' => 'g',
                'current_stock' => 5000.00,
                'low_stock_threshold' => 1000.00,
                'unit_cost' => 0.0120, // $12 per kg
                'is_active' => true,
            ]
        );
        InventoryItem::firstOrCreate(
            ['business_id' => $business->id, 'sku' => 'ING-MILK'],
            [
                'name_en' => 'Sweetened Condensed Milk',
                'name_km' => 'ទឹកដោះគោខាប់',
                'unit' => 'can',
                'current_stock' => 48.00,
                'low_stock_threshold' => 12.00,
                'unit_cost' => 0.8500,
                'is_active' => true,
            ]
        );
        InventoryItem::firstOrCreate(
            ['business_id' => $business->id, 'sku' => 'ING-KAMPOT-PEPPER'],
            [
                'name_en' => 'GI Certified Black Kampot Pepper',
                'name_km' => 'ម្រេចខ្មៅកំពតពិតៗ',
                'unit' => 'g',
                'current_stock' => 2000.00,
                'low_stock_threshold' => 500.00,
                'unit_cost' => 0.0250,
                'is_active' => true,
            ]
        );

        // 12. Sample Initial Completed Order & Customer
        $customer = Customer::firstOrCreate(
            ['business_id' => $business->id, 'phone' => '+855 12 345 678'],
            [
                'name' => 'Vannak Dara',
                'email' => 'dara.vannak@gmail.com',
                'total_spent' => 13.50,
                'total_orders' => 1,
            ]
        );

        $order = Order::firstOrCreate(
            ['business_id' => $business->id, 'order_number' => 'ORD-20261122-0001'],
            [
                'branch_id' => $mainBranch->id,
                'order_type' => 'dine_in',
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'customer_phone' => $customer->phone,
                'subtotal' => 13.50,
                'total_amount' => 13.50,
                'total_khr' => $business->khrEquivalent(13.50),
                'currency' => 'USD',
                'exchange_rate_khr' => 4100.00,
                'status' => 'completed',
                'payment_status' => 'paid',
                'payment_method' => 'khqr',
                'notes' => 'Customer paid via KHQR Bakong.',
            ]
        );

        OrderItem::firstOrCreate(
            ['order_id' => $order->id, 'product_id' => $amok->id],
            [
                'product_name' => $amok->name_en,
                'unit_price' => 6.50,
                'quantity' => 1.00,
                'subtotal' => 6.50,
            ]
        );
        OrderItem::firstOrCreate(
            ['order_id' => $order->id, 'product_id' => $loklak->id],
            [
                'product_name' => $loklak->name_en,
                'unit_price' => 7.00,
                'quantity' => 1.00,
                'subtotal' => 7.00,
            ]
        );

        TenantContext::clear();
    }
}
