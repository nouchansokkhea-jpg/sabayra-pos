<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\BusinessAiInsight;
use App\Models\User;
use App\Services\AI\AiAccessService;
use App\Services\AI\AiInsightsService;
use App\Services\AI\AIManager;
use App\Services\AI\AIProviderException;
use App\Services\AI\AIUsageService;
use App\Services\AI\MenuAuditService;
use App\Services\AI\SentimentRadarService;
use App\Services\AI\UpsellPairingService;
use App\Services\Tenant\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AiInsightController extends Controller
{
    public function __construct(
        protected AiInsightsService $aiInsightsService,
        protected MenuAuditService $menuAuditService,
        protected SentimentRadarService $sentimentRadarService,
        protected UpsellPairingService $upsellPairingService,
        protected AIManager $aiManager,
        protected AIUsageService $usageService,
        protected AiAccessService $accessService,
    ) {}

    /**
     * SABAYRA AI Weekly Performance Insights & Persistent Insight Queue.
     */
    public function weeklySummary(): View
    {
        /** @var Business|null $business */
        $business = TenantContext::get();
        /** @var User|null $user */
        $user = Auth::user();

        if (! $business || ! $user) {
            abort(403, 'Active business workspace required.');
        }

        ($this->accessService ?? app(AiAccessService::class))->authorizeArea($user, $business, 'insights');

        $insights = $this->aiInsightsService->generateWeeklySummary($business);
        $persistedInsights = BusinessAiInsight::where('business_id', $business->id)
            ->with('reviewedBy:id,name')
            ->latest('generated_at')
            ->take(12)
            ->get();

        $aiManager = $this->aiManager ?? app(AIManager::class);
        $usageService = $this->usageService ?? app(AIUsageService::class);
        $accessService = $this->accessService ?? app(AiAccessService::class);

        $platformConfig = $aiManager->getPlatformConfig();
        $entitlements = $usageService->resolveEntitlements($business);
        $monthlySummary = $usageService->getMonthlySummary($business);
        $allowedAreas = $accessService->allowedAreas($user, $business);

        return view('business.ai.insights', compact(
            'business',
            'insights',
            'persistedInsights',
            'platformConfig',
            'entitlements',
            'monthlySummary',
            'allowedAreas'
        ));
    }

    /**
     * Generate & persist actionable BusinessAiInsight entries backed by verified weekly data.
     */
    public function generate(Request $request): JsonResponse
    {
        /** @var Business|null $business */
        $business = TenantContext::get();
        /** @var User|null $user */
        $user = Auth::user();

        if (! $business || ! $user) {
            abort(403, 'Active business workspace required.');
        }

        ($this->accessService ?? app(AiAccessService::class))->authorizeArea($user, $business, 'insights');

        $validated = $request->validate([
            'with_ai' => ['nullable', 'boolean'],
            'request_uuid' => ['nullable', 'string', 'max:64'],
        ]);

        $usageService = $this->usageService ?? app(AIUsageService::class);

        try {
            $created = $this->aiInsightsService->generateAndPersistInsights(
                $business,
                $user,
                (bool) ($validated['with_ai'] ?? true),
                $validated['request_uuid'] ?? null
            );

            return response()->json([
                'ok' => true,
                'created_count' => $created->count(),
                'insights' => $created->values(),
                'monthly_summary' => $usageService->getMonthlySummary($business),
            ]);
        } catch (AIProviderException $exception) {
            return response()->json([
                'ok' => false,
                'error_code' => $exception->errorCode,
                'message' => $exception->getMessage(),
                'monthly_summary' => $usageService->getMonthlySummary($business),
            ], $exception->httpStatus);
        }
    }

    /**
     * Mark a persisted business AI insight as reviewed, applied, or dismissed.
     */
    public function updateStatus(Request $request, BusinessAiInsight $insight): RedirectResponse
    {
        /** @var Business|null $business */
        $business = TenantContext::get();
        /** @var User|null $user */
        $user = Auth::user();

        if (! $business || ! $user || $insight->business_id !== $business->id) {
            abort(403, 'Unauthorized insight access.');
        }

        ($this->accessService ?? app(AiAccessService::class))->authorizeArea($user, $business, 'insights');

        $validated = $request->validate([
            'status' => ['required', 'in:reviewed,applied,dismissed'],
        ]);

        $insight->update([
            'status' => (string) $validated['status'],
            'reviewed_by' => $user->id,
            'reviewed_at' => now(),
        ]);

        return back()->with('success', 'Business insight status updated.');
    }

    /**
     * Automated Menu Quality & Data Completeness Audit.
     */
    public function menuAudit(): View
    {
        $business = TenantContext::get();
        $audit = $this->menuAuditService->auditMenu($business);
        return view('business.ai.menu_audit', compact('business', 'audit'));
    }

    /**
     * Customer Sentiment Radar (Compliments, Complaints & Trends).
     */
    public function sentimentRadar(): View
    {
        $business = TenantContext::get();
        $sentiment = $this->sentimentRadarService->analyzeSentiment($business);
        return view('business.ai.sentiment', compact('business', 'sentiment'));
    }

    /**
     * AI Upsell Pairings (Frequently Bought Together Co-purchase Matrix).
     */
    public function upsellPairings(): View
    {
        $business = TenantContext::get();
        $pairings = $this->upsellPairingService->getPairings($business);
        return view('business.ai.upsell_pairings', compact('business', 'pairings'));
    }
}
