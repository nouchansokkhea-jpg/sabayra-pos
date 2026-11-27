<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\AiForecast;
use App\Models\AiGeneratedContent;
use App\Models\AiUsageLog;
use App\Models\Business;
use App\Models\BusinessAiInsight;
use App\Models\Product;
use App\Models\User;
use App\Services\AI\AiAccessService;
use App\Services\AI\AIManager;
use App\Services\AI\AIUsageService;
use App\Services\Tenant\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AiCenterController extends Controller
{
    public function __construct(
        private readonly AIManager $aiManager,
        private readonly AIUsageService $usageService,
        private readonly AiAccessService $accessService,
    ) {}

    /**
     * Display unified SABAYRA AI Center overview grounded strictly in real business telemetry.
     */
    public function overview(): View
    {
        [$business, $user] = $this->resolveTenantAndAuthorize('overview');

        $platformConfig = $this->aiManager->getPlatformConfig();
        $entitlements = $this->usageService->resolveEntitlements($business);
        $featureSettings = $this->usageService->getOrCreateFeatureSetting($business);
        $monthlySummary = $this->usageService->getMonthlySummary($business);
        $allowedAreas = $this->accessService->allowedAreas($user, $business);

        $recentUsage = AiUsageLog::where('business_id', $business->id)
            ->with('user:id,name')
            ->latest()
            ->take(8)
            ->get();

        $latestInsights = BusinessAiInsight::where('business_id', $business->id)
            ->where(function ($query): void {
                $query->whereNull('status')->orWhere('status', '!=', 'dismissed');
            })
            ->latest('generated_at')
            ->take(5)
            ->get();

        $lowStockProducts = Product::where('business_id', $business->id)
            ->where('track_inventory', true)
            ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
            ->orderBy('stock_quantity')
            ->take(6)
            ->get(['id', 'name_en', 'name_km', 'stock_quantity', 'low_stock_threshold', 'unit']);

        $productsMissingKhmerCount = Product::where('business_id', $business->id)
            ->where('is_available', true)
            ->where(function ($query): void {
                $query->whereNull('name_km')
                    ->orWhere('name_km', '')
                    ->orWhereNull('description_km')
                    ->orWhere('description_km', '');
            })
            ->count();

        $recentContentCount = AiGeneratedContent::where('business_id', $business->id)->count();
        $latestForecast = AiForecast::where('business_id', $business->id)->latest('generated_at')->first();

        $suggestedActions = $this->buildRealSuggestedActions(
            $platformConfig,
            $lowStockProducts->count(),
            $productsMissingKhmerCount,
            $recentContentCount,
            $latestForecast !== null,
            $allowedAreas
        );

        return view('business.ai.overview', compact(
            'business',
            'platformConfig',
            'entitlements',
            'featureSettings',
            'monthlySummary',
            'allowedAreas',
            'recentUsage',
            'latestInsights',
            'lowStockProducts',
            'suggestedActions'
        ));
    }

    /**
     * Display monthly AI credit ledger and auditable request history.
     */
    public function usage(Request $request): View
    {
        [$business, $user] = $this->resolveTenantAndAuthorize('usage');

        $platformConfig = $this->aiManager->getPlatformConfig();
        $entitlements = $this->usageService->resolveEntitlements($business);
        $monthlySummary = $this->usageService->getMonthlySummary($business);
        $allowedAreas = $this->accessService->allowedAreas($user, $business);

        $featureFilter = (string) $request->query('feature', '');
        $statusFilter = (string) $request->query('status', '');

        $logsQuery = AiUsageLog::where('business_id', $business->id)
            ->with('user:id,name')
            ->latest();

        if ($featureFilter !== '') {
            $logsQuery->where('feature', $featureFilter);
        }

        if ($statusFilter !== '') {
            $logsQuery->where('status', $statusFilter);
        }

        $usageLogs = $logsQuery->paginate(20)->withQueryString();

        return view('business.ai.usage', compact(
            'business',
            'platformConfig',
            'entitlements',
            'monthlySummary',
            'allowedAreas',
            'usageLogs',
            'featureFilter',
            'statusFilter'
        ));
    }

    /**
     * Display business-level AI preferences, language/tone settings, and feature toggles.
     */
    public function settings(): View
    {
        [$business, $user] = $this->resolveTenantAndAuthorize('settings');

        $platformConfig = $this->aiManager->getPlatformConfig();
        $entitlements = $this->usageService->resolveEntitlements($business);
        $featureSettings = $this->usageService->getOrCreateFeatureSetting($business);
        $monthlySummary = $this->usageService->getMonthlySummary($business);
        $allowedAreas = $this->accessService->allowedAreas($user, $business);

        return view('business.ai.settings', compact(
            'business',
            'platformConfig',
            'entitlements',
            'featureSettings',
            'monthlySummary',
            'allowedAreas'
        ));
    }

    /**
     * Persist business AI preferences and feature toggles cleanly.
     */
    public function updateSettings(Request $request): RedirectResponse
    {
        [$business] = $this->resolveTenantAndAuthorize('settings');
        $platformConfig = $this->aiManager->getPlatformConfig();
        $featureSettings = $this->usageService->getOrCreateFeatureSetting($business);

        $validated = $request->validate([
            'preferred_language' => ['required', 'in:auto,km,en'],
            'preferred_tone' => ['required', 'in:friendly,professional,energetic,premium,concise'],
            'temperature' => ['required', 'numeric', 'min:0', 'max:1.5'],
            'provider_override' => ['nullable', 'in:gemini,openai'],
            'model_override' => ['nullable', 'string', 'max:80'],
        ]);

        $updateData = [
            'preferred_language' => (string) $validated['preferred_language'],
            'preferred_tone' => (string) $validated['preferred_tone'],
            'temperature' => round((float) $validated['temperature'], 2),
            'assistant_enabled' => $request->boolean('assistant_enabled'),
            'menu_creator_enabled' => $request->boolean('menu_creator_enabled'),
            'marketing_studio_enabled' => $request->boolean('marketing_studio_enabled'),
            'forecasting_enabled' => $request->boolean('forecasting_enabled'),
            'insights_enabled' => $request->boolean('insights_enabled'),
        ];

        if ($platformConfig['allow_business_override']) {
            $overrideProvider = $validated['provider_override'] ?: null;
            $overrideModel = $validated['model_override'] ?: null;
            if ($overrideProvider !== null && $overrideModel !== null) {
                $allowedModels = array_keys((array) config("ai.providers.{$overrideProvider}.models", []));
                if (! in_array($overrideModel, $allowedModels, true)) {
                    $overrideModel = (string) config("ai.providers.{$overrideProvider}.default_model");
                }
            }
            $updateData['provider_override'] = $overrideProvider;
            $updateData['model_override'] = $overrideModel;
        }

        $featureSettings->update($updateData);

        return back()->with('success', 'Business AI Center settings updated successfully.');
    }

    /**
     * Temporary workspace views until Phases 3–7 dedicated modules activate each tool.
     */
    public function modulePlaceholder(Request $request): View
    {
        $routeName = (string) ($request->route()?->getName() ?? 'business.ai.assistant');
        $meta = match ($routeName) {
            'business.ai.menu-creator' => ['area' => 'menu_creator', 'title' => 'AI Menu Creator', 'subtitle' => 'Generate bilingual Khmer & English item names, descriptions, pricing tips, and smart menu suggestions.'],
            'business.ai.marketing' => ['area' => 'marketing_studio', 'title' => 'AI Marketing Studio', 'subtitle' => 'Create Cambodian Facebook captions, TikTok video scripts, Telegram promos, and holiday campaigns.'],
            'business.ai.inventory-forecast' => ['area' => 'inventory_forecast', 'title' => 'AI Inventory Forecast', 'subtitle' => 'Moving-average stock runway estimates, reorder alerts, and explainable risk commentary.'],
            'business.ai.sales-prediction' => ['area' => 'sales_prediction', 'title' => 'AI Sales Prediction', 'subtitle' => '1-day, 7-day, and 30-day revenue outlook computed strictly from historical orders.'],
            default => ['area' => 'assistant', 'title' => 'AI Business Assistant', 'subtitle' => 'Khmer & English conversational copilot grounded in real store orders, menu, and stock data.'],
        };

        [$business, $user] = $this->resolveTenantAndAuthorize($meta['area']);
        $monthlySummary = $this->usageService->getMonthlySummary($business);
        $allowedAreas = $this->accessService->allowedAreas($user, $business);
        $entitlements = $this->usageService->resolveEntitlements($business);
        $platformConfig = $this->aiManager->getPlatformConfig();

        return view('business.ai.coming-soon', [
            'business' => $business,
            'monthlySummary' => $monthlySummary,
            'allowedAreas' => $allowedAreas,
            'entitlements' => $entitlements,
            'platformConfig' => $platformConfig,
            'moduleTitle' => $meta['title'],
            'moduleSubtitle' => $meta['subtitle'],
            'moduleArea' => $meta['area'],
        ]);
    }

    /**
     * Resolve active tenant business and verify role/permission authorization.
     *
     * @return array{0: Business, 1: User}
     */
    private function resolveTenantAndAuthorize(string $area): array
    {
        /** @var Business|null $business */
        $business = TenantContext::get();
        /** @var User|null $user */
        $user = Auth::user();

        if (! $business || ! $user) {
            abort(403, 'Active business workspace required.');
        }

        $this->accessService->authorizeArea($user, $business, $area);

        return [$business, $user];
    }

    /**
     * Derive truthful, non-fabricated next-step actions strictly from current database counts.
     *
     * @param  array<string, mixed>  $platformConfig
     * @param  array<string, bool>  $allowedAreas
     * @return array<int, array{title: string, description: string, route: string, cta: string, tone: string}>
     */
    private function buildRealSuggestedActions(
        array $platformConfig,
        int $lowStockCount,
        int $productsMissingKhmerCount,
        int $recentContentCount,
        bool $hasForecast,
        array $allowedAreas
    ): array {
        $actions = [];

        if (! $platformConfig['gemini_key_configured'] && ! $platformConfig['openai_key_configured']) {
            $actions[] = [
                'title' => 'AI Provider Key Needed (Super Admin)',
                'description' => 'Configure a Google Gemini or OpenAI API key in Platform Settings before generating AI replies.',
                'route' => route('business.ai.settings'),
                'cta' => 'Review AI Status',
                'tone' => 'amber',
            ];
        }

        if ($lowStockCount > 0 && ($allowedAreas['inventory_forecast'] ?? false)) {
            $actions[] = [
                'title' => "{$lowStockCount} Products Below Low-Stock Threshold",
                'description' => 'Review live stock alerts and run a data-backed reorder forecast.',
                'route' => route('business.ai.inventory-forecast'),
                'cta' => 'Open Inventory Forecast',
                'tone' => 'rose',
            ];
        }

        if ($productsMissingKhmerCount > 0 && ($allowedAreas['menu_creator'] ?? false)) {
            $actions[] = [
                'title' => "{$productsMissingKhmerCount} Menu Items Missing Khmer Copy",
                'description' => 'Use AI Menu Creator to translate and enrich bilingual Khmer & English product descriptions.',
                'route' => route('business.ai.menu-creator'),
                'cta' => 'Open Menu Creator',
                'tone' => 'orange',
            ];
        }

        if (! $hasForecast && ($allowedAreas['sales_prediction'] ?? false)) {
            $actions[] = [
                'title' => 'No Sales Forecast Generated Yet',
                'description' => 'Analyze historical orders and day-of-week demand trends across your store.',
                'route' => route('business.ai.sales-prediction'),
                'cta' => 'Run Sales Prediction',
                'tone' => 'navy',
            ];
        }

        if ($recentContentCount === 0 && ($allowedAreas['marketing_studio'] ?? false)) {
            $actions[] = [
                'title' => 'Create Your First Promotion Draft',
                'description' => 'Generate a Cambodian Facebook post, TikTok script, or Telegram promo for top items.',
                'route' => route('business.ai.marketing'),
                'cta' => 'Open Marketing Studio',
                'tone' => 'emerald',
            ];
        }

        return $actions;
    }
}
