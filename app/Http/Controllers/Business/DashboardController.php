<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\BusinessSetting;
use App\Services\Business\DashboardExperienceService;
use App\Services\Tenant\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardExperienceService $dashboardExperience
    ) {
    }

    /**
     * Display the category-aware merchant business overview dashboard.
     */
    public function index(?Request $request = null): View
    {
        $business = TenantContext::get();
        $user = Auth::user();

        $viewData = $this->dashboardExperience->buildForBusiness($business, $user);

        return view('business.dashboard', $viewData);
    }

    /**
     * Save personalized dashboard panel visibility preferences for the workspace.
     */
    public function updateWidgets(Request $request): RedirectResponse
    {
        $business = TenantContext::get();
        if (! $business) {
            abort(403, 'Active business workspace required.');
        }

        $validated = $request->validate([
            'visible_panels' => ['nullable', 'array'],
            'visible_panels.*' => ['string', 'max:60'],
            'available_panels' => ['nullable', 'array'],
            'available_panels.*' => ['string', 'max:60'],
        ]);

        $available = (array) ($validated['available_panels'] ?? []);
        $visible = (array) ($validated['visible_panels'] ?? []);
        $hidden = array_values(array_diff($available, $visible));

        BusinessSetting::setValue($business->id, 'dashboard.hidden_widgets', $hidden);

        return redirect()
            ->route('business.dashboard')
            ->with('success', 'Dashboard layout preferences updated.');
    }

    /**
     * Refresh deterministic AI business insights for the active store without charging credits.
     */
    public function refreshAiInsights(): RedirectResponse
    {
        $business = TenantContext::get();
        $user = Auth::user();

        if (! $business || ! $user) {
            abort(403, 'Active business workspace required.');
        }

        app(\App\Services\AI\AiAccessService::class)->authorizeArea($user, $business, 'insights');
        app(\App\Services\AI\AiInsightsService::class)->generateAndPersistInsights($business, $user, false);

        return redirect()->route('business.ai.insights')->with('success', 'Weekly store insights refreshed from latest orders and stock.');
    }
}
