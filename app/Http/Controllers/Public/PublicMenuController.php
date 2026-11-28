<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\BusinessMember;
use App\Models\BusinessPaymentConfig;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\QrCode;
use App\Models\QrScanEvent;
use App\Models\RestaurantTable;
use App\Services\Business\CategoryExperienceService;
use App\Services\Payment\BakongKhqrService;
use App\Services\Payment\PaymentGatewayVisibilityService;
use App\Services\Tenant\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class PublicMenuController extends Controller
{
    public function __construct(
        private PaymentGatewayVisibilityService $visibilityService,
        private BakongKhqrService $bakongKhqrService,
        private CategoryExperienceService $categoryExperience,
    ) {
    }

    /**
     * Display the category-adapted public digital menu, storefront catalog, or service booking hub.
     */
    public function show(Request $request, string $business_slug): View
    {
        $decodedSlug = urldecode($business_slug);
        $business = Business::where(function ($query) use ($business_slug, $decodedSlug) {
            $query->where('slug', $business_slug)
                ->orWhere('slug', $decodedSlug);
        })
            ->where('status', 'active')
            ->firstOrFail();

        TenantContext::set($business);

        try {
            $menuQr = QrCode::firstOrCreate(
                ['business_id' => $business->id, 'type' => 'menu'],
                [
                    'code' => "QR-MAIN-{$business->slug}",
                    'target_url' => url("/{$business->slug}"),
                    'scan_count' => 0,
                ]
            );
            $menuQr->increment('scan_count');
            $menuQr->update(['last_scanned_at' => now()]);
        } catch (\Throwable $e) {
            // Non-blocking
        }

        return $this->renderStorefront($request, $business, null);
    }

    /**
     * Handle table QR code scan and render menu with table context.
     */
    public function table(Request $request, string $business_slug, string $table_number): View
    {
        $decodedSlug = urldecode($business_slug);
        $business = Business::where(function ($query) use ($business_slug, $decodedSlug) {
            $query->where('slug', $business_slug)
                ->orWhere('slug', $decodedSlug);
        })
            ->where('status', 'active')
            ->firstOrFail();

        TenantContext::set($business);

        $table = RestaurantTable::where('business_id', $business->id)
            ->where('table_number', $table_number)
            ->first();

        if ($table && $table->qrCode) {
            $table->qrCode->increment('scan_count');
            $table->qrCode->update(['last_scanned_at' => now()]);

            QrScanEvent::create([
                'qr_code_id' => $table->qrCode->id,
                'business_id' => $business->id,
                'table_id' => $table->id,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'scanned_at' => now(),
            ]);
        }

        session(["table_{$business->id}" => $table_number]);

        return $this->renderStorefront($request, $business, $table_number);
    }

    /**
     * Assemble storefront data tailored to the active business category.
     */
    private function renderStorefront(Request $request, Business $business, ?string $forcedTableNumber): View
    {
        $categories = Category::where('business_id', $business->id)
            ->where('is_active', true)
            ->with(['products' => function ($q) use ($business): void {
                $q->where('business_id', $business->id)
                    ->where('is_available', true)
                    ->with(['variants', 'addons']);
            }])
            ->orderBy('sort_order')
            ->get();

        $featuredProducts = Product::where('business_id', $business->id)
            ->where('is_available', true)
            ->where('is_featured', true)
            ->with(['variants', 'addons', 'category'])
            ->take(8)
            ->get();

        $categoryMeta = $this->categoryExperience->meta($business);
        $storefrontConfig = $this->categoryExperience->storefrontConfig($business);
        $terminology = $this->categoryExperience->terminology($business);

        $tablesEnabled = $this->categoryExperience->isModuleEnabled($business, 'tables');
        $tables = $tablesEnabled
            ? RestaurantTable::where('business_id', $business->id)->where('is_active', true)->orderBy('table_number')->get()
            : collect();
        $tableNumber = $tablesEnabled
            ? ($forcedTableNumber ?? $request->query('table') ?? session("table_{$business->id}"))
            : null;

        $paymentMethods = $this->visibilityService->resolveCheckoutMethods($business);
        $pricingConfig = $this->buildPricingConfig($business);

        $brands = collect();
        if (! empty($storefrontConfig['show_retail_filters'])) {
            $brands = $categories->flatMap->products
                ->pluck('brand')
                ->filter(fn ($b): bool => filled($b))
                ->unique()
                ->sort()
                ->values();
        }

        $bookingServices = collect();
        $staffMembers = collect();
        if ($this->categoryExperience->isModuleEnabled($business, 'service_bookings')) {
            $bookingServices = Product::where('business_id', $business->id)
                ->where('is_available', true)
                ->orderBy('sort_order')
                ->get();
            $staffMembers = BusinessMember::with('user')
                ->where('business_id', $business->id)
                ->where('is_active', true)
                ->get();
        }

        return view('public.menu', compact(
            'business',
            'categories',
            'featuredProducts',
            'tables',
            'tableNumber',
            'paymentMethods',
            'pricingConfig',
            'categoryMeta',
            'storefrontConfig',
            'terminology',
            'brands',
            'bookingServices',
            'staffMembers'
        ));
    }

    /**
     * Display live order tracking status and payment settlement steps for customer.
     */
    public function orderStatus(Request $request, string $business_slug, string $order_number): View
    {
        $decodedSlug = urldecode($business_slug);
        $business = Business::where(function ($query) use ($business_slug, $decodedSlug) {
            $query->where('slug', $business_slug)
                ->orWhere('slug', $decodedSlug);
        })->firstOrFail();
        TenantContext::set($business);

        $order = Order::where('business_id', $business->id)
            ->where('order_number', $order_number)
            ->with(['items.addons', 'table', 'statusHistories', 'proofs', 'paymentTransactions'])
            ->firstOrFail();

        $paymentConfig = BusinessPaymentConfig::resolveForBusiness($business);
        $paymentMethods = $this->visibilityService->resolveCheckoutMethods($business, $order->order_type);
        $latestTransaction = $order->paymentTransactions()->latest('created_at')->first();

        $khqrSvg = null;
        if ($latestTransaction && filled($latestTransaction->khqr_payload)) {
            $khqrSvg = $this->bakongKhqrService->renderQrSvg($latestTransaction->khqr_payload);
        }

        $queryToken = $request->query('token');
        $proofToken = $this->visibilityService->verifyOrderProofToken($order, is_string($queryToken) ? $queryToken : null)
            ? $queryToken
            : null;

        return view('public.order-status', compact(
            'business',
            'order',
            'paymentConfig',
            'paymentMethods',
            'latestTransaction',
            'khqrSvg',
            'proofToken'
        ));
    }

    /**
     * Build safe public pricing rules for the live checkout drawer calculator.
     *
     * @return array<string, mixed>
     */
    private function buildPricingConfig(Business $business): array
    {
        return [
            'tax_name' => $business->tax_name ?: 'VAT',
            'tax_rate' => (float) ($business->tax_percent ?? 0),
            'tax_inclusive' => (bool) ($business->tax_inclusive ?? false),
            'service_charge_enabled' => (bool) ($business->service_charge_enabled ?? false),
            'service_charge_rate' => (float) ($business->service_charge_rate ?? 0),
            'exchange_rate_khr' => (float) ($business->exchange_rate_khr ?: 4100),
            'default_currency' => (string) ($business->default_currency ?: 'USD'),
        ];
    }
}
