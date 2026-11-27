<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\AiGeneratedContent;
use App\Models\Business;
use App\Models\Product;
use App\Models\User;
use App\Services\AI\AiAccessService;
use App\Services\AI\AIManager;
use App\Services\AI\AIProviderException;
use App\Services\AI\AIUsageService;
use App\Services\AI\MarketingStudioService;
use App\Services\Tenant\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AiMarketingController extends Controller
{
    public function __construct(
        private readonly MarketingStudioService $marketingService,
        private readonly AIManager $aiManager,
        private readonly AIUsageService $usageService,
        private readonly AiAccessService $accessService,
    ) {}

    /**
     * Render the SABAYRA AI Marketing Studio workspace.
     */
    public function index(): View
    {
        [$business, $user] = $this->resolveAndAuthorize();

        $platformConfig = $this->aiManager->getPlatformConfig();
        $entitlements = $this->usageService->resolveEntitlements($business);
        $monthlySummary = $this->usageService->getMonthlySummary($business);
        $allowedAreas = $this->accessService->allowedAreas($user, $business);
        $featureSettings = $this->usageService->getOrCreateFeatureSetting($business);

        $products = Product::where('business_id', $business->id)
            ->where('is_available', true)
            ->orderBy('name_en')
            ->get(['id', 'name_en', 'name_km', 'base_price', 'discount_price']);

        $recentCampaigns = AiGeneratedContent::where('business_id', $business->id)
            ->where('feature', 'marketing_studio')
            ->with('product:id,name_en')
            ->latest()
            ->take(10)
            ->get();

        return view('business.ai.marketing-studio', compact(
            'business',
            'platformConfig',
            'entitlements',
            'monthlySummary',
            'allowedAreas',
            'featureSettings',
            'products',
            'recentCampaigns'
        ));
    }

    /**
     * Generate localized social campaign drafts (Facebook, TikTok, Telegram, Khmer Holidays).
     */
    public function generate(Request $request): JsonResponse
    {
        [$business, $user] = $this->resolveAndAuthorize();

        $validated = $request->validate([
            'channel' => ['required', 'in:facebook,tiktok,telegram,general'],
            'campaign_type' => ['required', 'in:product_spotlight,combo_promo,holiday_khmer,flash_discount,retention'],
            'product_id' => ['nullable', 'uuid'],
            'occasion' => ['nullable', 'string', 'max:160'],
            'offer_details' => ['nullable', 'string', 'max:600'],
            'target_language' => ['required', 'in:km,en,bilingual'],
            'tone' => ['required', 'in:friendly,professional,energetic,premium,concise'],
            'request_uuid' => ['nullable', 'string', 'max:64'],
        ]);

        try {
            $content = $this->marketingService->generate(
                $business,
                $user,
                $validated,
                $validated['request_uuid'] ?? null
            );

            return response()->json([
                'ok' => true,
                'campaign' => [
                    'id' => $content->id,
                    'title' => $content->title,
                    'platform' => $content->platform,
                    'structured' => $content->structured_output,
                ],
                'monthly_summary' => $this->usageService->getMonthlySummary($business),
            ]);
        } catch (AIProviderException $exception) {
            return response()->json([
                'ok' => false,
                'error_code' => $exception->errorCode,
                'message' => $exception->getMessage(),
                'monthly_summary' => $this->usageService->getMonthlySummary($business),
            ], $exception->httpStatus);
        }
    }

    /**
     * Delete a generated marketing campaign owned by the active business.
     */
    public function destroy(AiGeneratedContent $content): RedirectResponse
    {
        [$business] = $this->resolveAndAuthorize();

        if ($content->business_id !== $business->id || $content->feature !== 'marketing_studio') {
            abort(403, 'Unauthorized marketing draft access.');
        }

        $content->delete();

        return back()->with('success', 'Marketing campaign draft removed.');
    }

    /**
     * Resolve active tenant business and verify marketing_studio authorization.
     *
     * @return array{0: Business, 1: User}
     */
    private function resolveAndAuthorize(): array
    {
        /** @var Business|null $business */
        $business = TenantContext::get();
        /** @var User|null $user */
        $user = Auth::user();

        if (! $business || ! $user) {
            abort(403, 'Active business workspace required.');
        }

        $this->accessService->authorizeArea($user, $business, 'marketing_studio');

        return [$business, $user];
    }
}
