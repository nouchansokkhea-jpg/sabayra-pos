<?php

namespace App\Services\AI;

use App\Models\Business;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Carbon;

class AIContextService
{
    /**
     * Detect whether user input is Khmer, English, or mixed Khmer-English.
     */
    public function detectLanguage(string $text): string
    {
        preg_match_all('/[\x{1780}-\x{17FF}\x{19E0}-\x{19FF}]/u', $text, $khmerMatches);
        preg_match_all('/[A-Za-z]/', $text, $latinMatches);

        $khmerCount = count($khmerMatches[0]);
        $latinCount = count($latinMatches[0]);

        if ($khmerCount >= 3 && $latinCount >= 3) {
            return 'mixed';
        }

        if ($khmerCount > 0) {
            return 'km';
        }

        return 'en';
    }

    /**
     * Resolve target response language combining business preference with detected input language.
     */
    public function resolveResponseLanguage(string $preferredSetting, string $userInput): string
    {
        if (in_array($preferredSetting, ['km', 'en'], true)) {
            return $preferredSetting;
        }

        return $this->detectLanguage($userInput);
    }

    /**
     * Build a compact, PII-free tenant-scoped summary of the business for prompt grounding.
     *
     * @return array<string, mixed>
     */
    public function buildCompactBusinessContext(Business $business): array
    {
        $thirtyDaysAgo = Carbon::now()->subDays(30)->startOfDay();

        $activeCategoriesCount = Category::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->where('is_active', true)
            ->count();

        $activeProductsCount = Product::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->where('is_available', true)
            ->count();

        $lowStockCount = Product::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->where('track_inventory', true)
            ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
            ->count();

        $ordersQuery = Order::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->where('created_at', '>=', $thirtyDaysAgo)
            ->whereNotIn('status', ['cancelled', 'rejected']);

        $ordersLast30Days = (clone $ordersQuery)->count();
        $revenueLast30Days = (float) (clone $ordersQuery)->sum('total_amount');

        return [
            'business_name' => $business->name,
            'business_type' => $business->business_type ?? 'restaurant',
            'currency' => $business->currency ?? 'USD',
            'exchange_rate_khr' => (float) ($business->exchange_rate_khr ?: config('menuhub.default_exchange_rate_khr', 4100)),
            'timezone' => config('app.timezone', 'Asia/Phnom_Penh'),
            'active_categories_count' => $activeCategoriesCount,
            'active_products_count' => $activeProductsCount,
            'low_stock_products_count' => $lowStockCount,
            'orders_last_30_days' => $ordersLast30Days,
            'revenue_last_30_days_usd' => round($revenueLast30Days, 2),
        ];
    }

    /**
     * Produce clear language guidance so replies feel natural to Cambodian SMEs.
     */
    public function formatSystemLanguageInstruction(string $language): string
    {
        return match ($language) {
            'km' => 'Respond in clear, natural, polite Cambodian Khmer appropriate for SME hospitality and retail staff. Preserve official currency numbers ($ USD and ៛ KHR) and exact product names verbatim.',
            'mixed' => 'Respond in clear natural Khmer blended thoughtfully with familiar English retail/POS terms (e.g. Stock, Margin, Best Seller, KHQR) matching the user style.',
            default => 'Respond in clear, concise business English tailored for Cambodian hospitality and retail operators.',
        };
    }
}
