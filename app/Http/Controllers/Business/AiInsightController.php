<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Services\AI\AiInsightsService;
use App\Services\AI\MenuAuditService;
use App\Services\AI\SentimentRadarService;
use App\Services\AI\UpsellPairingService;
use App\Services\Tenant\TenantContext;
use Illuminate\Contracts\View\View;

class AiInsightController extends Controller
{
    public function __construct(
        protected AiInsightsService $aiInsightsService,
        protected MenuAuditService $menuAuditService,
        protected SentimentRadarService $sentimentRadarService,
        protected UpsellPairingService $upsellPairingService,
    ) {}

    /**
     * SABAYRA AI Weekly Performance Insights.
     */
    public function weeklySummary(): View
    {
        $business = TenantContext::get();
        $insights = $this->aiInsightsService->generateWeeklySummary($business);
        return view('business.ai.insights', compact('business', 'insights'));
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
