<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Services\Business\BusinessFeatureService;
use App\Services\Tenant\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StorefrontTemplateController extends Controller
{
    /**
     * Display available storefront templates and design customizer.
     */
    public function index(): View
    {
        $business = TenantContext::get();
        $currentTheme = $business->theme_settings ?? [];
        $activeTemplate = $currentTheme['template'] ?? BusinessFeatureService::getDefaultTemplate($business);

        $templates = [
            'restaurant' => [
                'name' => 'Royal Khmer Fine Dining',
                'category' => 'Restaurant & Grill',
                'description' => 'Rich warm amber and deep slate tones crafted for sit-down dining, table QRs, and appetizers-to-desserts flow.',
                'primary_color' => '#f97316',
                'secondary_color' => '#0f172a',
                'font' => 'Instrument Sans, Kantumruy Pro',
            ],
            'cafe' => [
                'name' => 'Artisan Coffee Roasters',
                'category' => 'Café & Roastery',
                'description' => 'Earthy espresso brown and warm cream palettes highlighting signature drinks, beans origin, and pastry showcases.',
                'primary_color' => '#d97706',
                'secondary_color' => '#1c1917',
                'font' => 'Instrument Sans, Kantumruy Pro',
            ],
            'milk_tea' => [
                'name' => 'Boba & Sweet Blossom',
                'category' => 'Milk Tea & Dessert',
                'description' => 'Vibrant bubblegum and matcha tones emphasizing sweetness levels, ice adjustments, and chewy toppings.',
                'primary_color' => '#ec4899',
                'secondary_color' => '#18181b',
                'font' => 'Instrument Sans, Kantumruy Pro',
            ],
            'bakery' => [
                'name' => 'Golden Crust Bakery',
                'category' => 'Bakery & Pastry',
                'description' => 'Golden wheat and buttery accents perfect for fresh loaves, viennoiseries, custom birthday cakes, and daily batches.',
                'primary_color' => '#eab308',
                'secondary_color' => '#292524',
                'font' => 'Instrument Sans, Kantumruy Pro',
            ],
            'supermarket' => [
                'name' => 'Phnom Penh Fresh Market',
                'category' => 'Supermarket & Grocery',
                'description' => 'High-density grid layout with emerald freshness cues, barcode visibility, multi-buy savings, and fast search.',
                'primary_color' => '#10b981',
                'secondary_color' => '#09090b',
                'font' => 'Instrument Sans, Kantumruy Pro',
            ],
            'mini_mart' => [
                'name' => 'QuickStop Express Mart',
                'category' => 'Mini Mart & Convenience',
                'description' => 'Speed-focused product listing for quick essentials, snacks, beverages, and household goods.',
                'primary_color' => '#06b6d4',
                'secondary_color' => '#0f172a',
                'font' => 'Instrument Sans, Kantumruy Pro',
            ],
            'retail' => [
                'name' => 'Modern Lifestyle Store',
                'category' => 'General Retail',
                'description' => 'Clean product grid showcasing item specifications, manufacturer warranty, and stock availability.',
                'primary_color' => '#6366f1',
                'secondary_color' => '#18181b',
                'font' => 'Instrument Sans, Kantumruy Pro',
            ],
            'fashion' => [
                'name' => 'Haute Couture Boutique',
                'category' => 'Fashion & Apparel',
                'description' => 'Minimalist luxury aesthetic with large vertical hero lookbooks, size charts, color swatches, and seasonal drops.',
                'primary_color' => '#a855f7',
                'secondary_color' => '#000000',
                'font' => 'Instrument Sans, Kantumruy Pro',
            ],
            'beauty' => [
                'name' => 'Glow & Glamour Beauty Studio',
                'category' => 'Cosmetics & Salon',
                'description' => 'Soft rose and serene ivory aesthetics designed for cosmetic products, skincare routines, and service packages.',
                'primary_color' => '#f43f5e',
                'secondary_color' => '#1c1917',
                'font' => 'Instrument Sans, Kantumruy Pro',
            ],
            'services' => [
                'name' => 'ProService Specialist Hub',
                'category' => 'Services & Consulting',
                'description' => 'Structured package tiers, consultation slots, transparent dual-currency quotes, and direct messaging links.',
                'primary_color' => '#3b82f6',
                'secondary_color' => '#0f172a',
                'font' => 'Instrument Sans, Kantumruy Pro',
            ],
        ];

        return view('business.templates.index', compact('business', 'templates', 'activeTemplate', 'currentTheme'));
    }

    /**
     * Update active storefront template and styling palette.
     */
    public function update(Request $request): RedirectResponse
    {
        $business = TenantContext::get();

        $validated = $request->validate([
            'template' => 'required|string|max:50',
            'primary_color' => 'required|string|max:20',
            'secondary_color' => 'required|string|max:20',
            'layout' => 'nullable|string|in:grid,list,compact',
            'hero_title' => 'nullable|string|max:255',
        ]);

        $settings = array_merge($business->theme_settings ?? [], $validated);
        $business->update(['theme_settings' => $settings]);

        return back()->with('status', 'Storefront template and design preferences updated successfully!');
    }
}
