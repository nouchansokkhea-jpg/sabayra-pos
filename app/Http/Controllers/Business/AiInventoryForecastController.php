<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\AiForecast;
use App\Models\Business;
use App\Models\User;
use App\Services\AI\AiAccessService;
use App\Services\AI\AIManager;
use App\Services\AI\AIProviderException;
use App\Services\AI\AIUsageService;
use App\Services\AI\InventoryForecastService;
use App\Services\Tenant\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AiInventoryForecastController extends Controller
{
    public function __construct(
        private readonly InventoryForecastService $forecastService,
        private readonly AIManager $aiManager,
        private readonly AIUsageService $usageService,
        private readonly AiAccessService $accessService,
    ) {}

    /**
     * Display live statistical inventory runway forecasts and optional AI reorder commentary.
     */
    public function index(): View
    {
        [$business, $user] = $this->resolveAndAuthorize();

        $platformConfig = $this->aiManager->getPlatformConfig();
        $entitlements = $this->usageService->resolveEntitlements($business);
        $monthlySummary = $this->usageService->getMonthlySummary($business);
        $allowedAreas = $this->accessService->allowedAreas($user, $business);

        $forecast = AiForecast::where('business_id', $business->id)
            ->where('forecast_type', 'inventory')
            ->latest('generated_at')
            ->first();

        if (! $forecast) {
            $forecast = $this->forecastService->analyze($business, $user, false);
        }

        return view('business.ai.inventory-forecast', compact(
            'business',
            'platformConfig',
            'entitlements',
            'monthlySummary',
            'allowedAreas',
            'forecast'
        ));
    }

    /**
     * Recompute inventory statistics (free) or generate AI procurement analysis (2 credits).
     */
    public function run(Request $request): JsonResponse
    {
        [$business, $user] = $this->resolveAndAuthorize();

        $validated = $request->validate([
            'with_ai' => ['nullable', 'boolean'],
            'request_uuid' => ['nullable', 'string', 'max:64'],
        ]);

        try {
            $forecast = $this->forecastService->analyze(
                $business,
                $user,
                (bool) ($validated['with_ai'] ?? false),
                $validated['request_uuid'] ?? null
            );

            return response()->json([
                'ok' => true,
                'forecast' => $forecast,
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
     * Resolve active tenant business and verify inventory_forecast permission.
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

        $this->accessService->authorizeArea($user, $business, 'inventory_forecast');

        return [$business, $user];
    }
}
