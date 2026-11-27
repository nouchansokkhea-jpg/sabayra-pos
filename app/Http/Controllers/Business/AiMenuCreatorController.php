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
use App\Services\AI\MenuCreatorService;
use App\Services\Tenant\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AiMenuCreatorController extends Controller
{
    public function __construct(
        private readonly MenuCreatorService $menuCreatorService,
        private readonly AIManager $aiManager,
        private readonly AIUsageService $usageService,
        private readonly AiAccessService $accessService,
    ) {}

    /**
     * Render AI Menu Creator & Khmer Translator workspace.
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
            ->with('category:id,name_en,name_km')
            ->orderBy('name_en')
            ->get(['id', 'category_id', 'name_en', 'name_km', 'description_en', 'description_km', 'base_price', 'is_available']);

        $recentDrafts = AiGeneratedContent::where('business_id', $business->id)
            ->where('feature', 'menu_creator')
            ->with('product:id,name_en,name_km')
            ->latest()
            ->take(10)
            ->get();

        return view('business.ai.menu-creator', compact(
            'business',
            'platformConfig',
            'entitlements',
            'monthlySummary',
            'allowedAreas',
            'featureSettings',
            'products',
            'recentDrafts'
        ));
    }

    /**
     * Generate bilingual menu copy, translation, or combo recommendations cleanly.
     */
    public function generate(Request $request): JsonResponse
    {
        [$business, $user] = $this->resolveAndAuthorize();

        $validated = $request->validate([
            'action' => ['required', 'in:description,improve,translate,combos_and_pricing'],
            'product_id' => ['nullable', 'uuid'],
            'item_name' => ['nullable', 'string', 'max:160'],
            'item_name_km' => ['nullable', 'string', 'max:160'],
            'category_name' => ['nullable', 'string', 'max:120'],
            'price_usd' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'target_language' => ['nullable', 'in:auto,km,en,bilingual'],
            'tone' => ['nullable', 'in:friendly,professional,energetic,premium,concise'],
            'request_uuid' => ['nullable', 'string', 'max:64'],
        ]);

        try {
            $content = $this->menuCreatorService->generate(
                $business,
                $user,
                $validated,
                $validated['request_uuid'] ?? null
            );

            return response()->json([
                'ok' => true,
                'content' => [
                    'id' => $content->id,
                    'title' => $content->title,
                    'content_type' => $content->content_type,
                    'product_id' => $content->product_id,
                    'status' => $content->status,
                    'structured' => $content->structured_output,
                    'apply_url' => route('business.ai.menu-creator.apply', $content),
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
     * Apply explicitly reviewed AI copy to a store Product record.
     */
    public function apply(Request $request, AiGeneratedContent $content): RedirectResponse
    {
        [$business] = $this->resolveAndAuthorize();

        if ($content->business_id !== $business->id || $content->feature !== 'menu_creator') {
            abort(403, 'Unauthorized generated content access.');
        }

        $validated = $request->validate([
            'product_id' => ['required', 'uuid'],
            'apply_name_en' => ['nullable', 'boolean'],
            'apply_name_km' => ['nullable', 'boolean'],
            'apply_description_en' => ['nullable', 'boolean'],
            'apply_description_km' => ['nullable', 'boolean'],
        ]);

        $product = Product::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->whereKey($validated['product_id'])
            ->firstOrFail();

        $this->menuCreatorService->applyToProduct($business, $content, $product, [
            'name_en' => $request->boolean('apply_name_en'),
            'name_km' => $request->boolean('apply_name_km'),
            'description_en' => $request->boolean('apply_description_en'),
            'description_km' => $request->boolean('apply_description_km'),
        ]);

        return back()->with('success', "Applied AI Menu Copy to '{$product->name_en}' successfully.");
    }

    /**
     * Resolve active tenant and verify menu_creator role/permission authorization.
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

        $this->accessService->authorizeArea($user, $business, 'menu_creator');

        return [$business, $user];
    }
}
