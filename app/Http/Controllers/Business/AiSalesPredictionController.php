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
use App\Services\AI\SalesPredictionService;
use App\Services\Tenant\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AiSalesPredictionController extends Controller
{
    public function __construct(
        private readonly SalesPredictionService $predictionService,
        private readonly AIManager $aiManager,
        private readonly AIUsageService $usageService,
        private readonly AiAccessService $accessService,
    ) {}

    /**
     * Render interactive Sales Prediction dashboard grounded strictly in historical order series.
     */
    public function index(Request $request): View
    {
        [$business, $user] = $this->resolveAndAuthorize();

        $horizon = (string) $request->query('horizon', '7d');
        if (! in_array($horizon, ['1d', '7d', '30d'], true)) {
            $horizon = '7d';
        }

        $platformConfig = $this->aiManager->getPlatformConfig();
        $entitlements = $this->usageService->resolveEntitlements($business);
        $monthlySummary = $this->usageService->getMonthlySummary($business);
        $allowedAreas = $this->accessService->allowedAreas($user, $business);

        $forecast = AiForecast::where('business_id', $business->id)
            ->where('forecast_type', 'sales')
            ->where('horizon', $horizon)
            ->latest('generated_at')
            ->first();

        if (! $forecast) {
            $forecast = $this->predictionService->predict($business, $user, $horizon, false);
        }

        return view('business.ai.sales-prediction', compact(
            'business',
            'platformConfig',
            'entitlements',
            'monthlySummary',
            'allowedAreas',
            'horizon',
            'forecast'
        ));
    }

    /**
     * Recompute statistical sales predictions or request AI trend explanation (2 credits).
     */
    public function run(Request $request): JsonResponse
    {
        [$business, $user] = $this->resolveAndAuthorize();

        $validated = $request->validate([
            'horizon' => ['required', 'in:1d,7d,30d'],
            'with_ai' => ['nullable', 'boolean'],
            'request_uuid' => ['nullable', 'string', 'max:64'],
        ]);

        try {
            $forecast = $this->predictionService->predict(
                $business,
                $user,
                (string) $validated['horizon'],
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
     * Resolve active tenant business and verify sales_prediction permission.
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

        $this->accessService->authorizeArea($user, $business, 'sales_prediction');

        return [$business, $user];
    }
}
