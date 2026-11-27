<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use App\Models\SecurityLog;
use App\Services\AI\AIManager;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SettingController extends Controller
{
    /**
     * Display platform system settings including global payment gateway and AI controls.
     */
    public function index(AIManager $aiManager): View
    {
        $platform = [
            'name' => config('menuhub.name'),
            'tagline' => config('menuhub.tagline'),
            'default_currency' => config('menuhub.default_currency'),
            'exchange_rate_khr' => config('menuhub.default_exchange_rate_khr'),
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'database_driver' => config('database.default'),
            'database_host' => config('database.connections.pgsql.host'),
        ];

        $gatewaySettings = [
            'bakong_enabled' => PlatformSetting::isBakongEnabled(),
            'aba_payway_enabled' => PlatformSetting::isAbaPayWayEnabled(),
            'bakong_api_base_url' => PlatformSetting::resolveBakongApiBaseUrl(),
            'bakong_token_configured' => filled(PlatformSetting::resolveBakongApiToken()),
            'aba_sandbox_url' => config('services.aba_payway.sandbox_url'),
            'aba_production_url' => config('services.aba_payway.production_url'),
        ];

        $aiConfig = $aiManager->getPlatformConfig();

        return view('admin.settings.index', compact('platform', 'gatewaySettings', 'aiConfig'));
    }

    /**
     * Update platform-wide payment gateway availability and encrypted NBC Bakong credentials.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'bakong_enabled' => ['nullable', 'boolean'],
            'aba_payway_enabled' => ['nullable', 'boolean'],
            'bakong_api_base_url' => ['nullable', 'url:https', 'max:255'],
            'bakong_api_token' => ['nullable', 'string', 'max:1000'],
            'clear_bakong_api_token' => ['nullable', 'boolean'],
        ]);

        $bakongEnabled = $request->has('bakong_enabled')
            ? $request->boolean('bakong_enabled')
            : PlatformSetting::isBakongEnabled();

        $abaPayWayEnabled = $request->has('aba_payway_enabled')
            ? $request->boolean('aba_payway_enabled')
            : PlatformSetting::isAbaPayWayEnabled();

        $adminId = Auth::id();

        PlatformSetting::setValue(
            'payments.bakong_enabled',
            $bakongEnabled,
            false,
            'Global switch controlling whether Bakong KHQR is available across SABAYRA.',
            $adminId
        );

        PlatformSetting::setValue(
            'payments.aba_payway_enabled',
            $abaPayWayEnabled,
            false,
            'Global switch controlling whether ABA PayWay is available across SABAYRA.',
            $adminId
        );

        if (filled($validated['bakong_api_base_url'] ?? null)) {
            PlatformSetting::setValue(
                'payments.bakong_api_base_url',
                rtrim(trim((string) $validated['bakong_api_base_url']), '/'),
                false,
                'Official NBC Bakong Open API base URL.',
                $adminId
            );
        }

        if ($request->boolean('clear_bakong_api_token')) {
            PlatformSetting::setValue(
                'payments.bakong_api_token',
                null,
                true,
                'Encrypted NBC Bakong Open API access token.',
                $adminId
            );
        } elseif (filled($validated['bakong_api_token'] ?? null)) {
            PlatformSetting::setValue(
                'payments.bakong_api_token',
                trim((string) $validated['bakong_api_token']),
                true,
                'Encrypted NBC Bakong Open API access token.',
                $adminId
            );
        }

        SecurityLog::create([
            'user_id' => $adminId,
            'event_type' => 'platform_payment_settings_updated',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => [
                'bakong_enabled' => $bakongEnabled,
                'aba_payway_enabled' => $abaPayWayEnabled,
                'bakong_api_token_updated' => filled($validated['bakong_api_token'] ?? null) || $request->boolean('clear_bakong_api_token'),
            ],
        ]);

        return back()->with('success', 'Platform payment gateway controls updated successfully.');
    }

    /**
     * Update platform-wide SABAYRA AI provider configuration and encrypted API credentials.
     */
    public function updateAi(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ai_enabled' => ['nullable', 'boolean'],
            'ai_default_provider' => ['required', 'in:gemini,openai'],
            'ai_default_model' => ['required', 'string', 'max:80'],
            'ai_temperature' => ['required', 'numeric', 'min:0', 'max:1.5'],
            'ai_max_output_tokens' => ['required', 'integer', 'min:128', 'max:8192'],
            'ai_timeout_seconds' => ['required', 'integer', 'min:5', 'max:120'],
            'ai_retry_attempts' => ['required', 'integer', 'min:0', 'max:3'],
            'ai_default_monthly_credits' => ['required', 'integer', 'min:0', 'max:100000'],
            'ai_allow_business_override' => ['nullable', 'boolean'],
            'gemini_api_key' => ['nullable', 'string', 'max:500'],
            'openai_api_key' => ['nullable', 'string', 'max:500'],
            'clear_gemini_api_key' => ['nullable', 'boolean'],
            'clear_openai_api_key' => ['nullable', 'boolean'],
        ]);

        $provider = (string) $validated['ai_default_provider'];
        $allowedModels = array_keys((array) config("ai.providers.{$provider}.models", []));
        $model = in_array($validated['ai_default_model'], $allowedModels, true)
            ? (string) $validated['ai_default_model']
            : (string) config("ai.providers.{$provider}.default_model");

        $adminId = Auth::id();
        $aiEnabled = $request->boolean('ai_enabled');
        $allowBusinessOverride = $request->boolean('ai_allow_business_override');

        PlatformSetting::setValue('ai.enabled', $aiEnabled, false, 'Master switch enabling SABAYRA AI Center.', $adminId);
        PlatformSetting::setValue('ai.default_provider', $provider, false, 'Default AI provider driver (gemini or openai).', $adminId);
        PlatformSetting::setValue('ai.default_model', $model, false, 'Default AI model identifier.', $adminId);
        PlatformSetting::setValue('ai.temperature', (float) $validated['ai_temperature'], false, 'Default AI generation temperature.', $adminId);
        PlatformSetting::setValue('ai.max_output_tokens', (int) $validated['ai_max_output_tokens'], false, 'Platform upper bound on output tokens.', $adminId);
        PlatformSetting::setValue('ai.timeout_seconds', (int) $validated['ai_timeout_seconds'], false, 'HTTP timeout seconds for AI provider calls.', $adminId);
        PlatformSetting::setValue('ai.retry_attempts', (int) $validated['ai_retry_attempts'], false, 'Retry attempts on transient AI provider errors.', $adminId);
        PlatformSetting::setValue('ai.default_monthly_credits', (int) $validated['ai_default_monthly_credits'], false, 'Fallback monthly AI credits when subscription plans are disabled.', $adminId);
        PlatformSetting::setValue('ai.allow_business_override', $allowBusinessOverride, false, 'Whether individual businesses may select an alternate configured AI provider/model.', $adminId);

        $geminiKeyUpdated = false;
        if ($request->boolean('clear_gemini_api_key')) {
            PlatformSetting::setValue('ai.gemini_api_key', null, true, 'Encrypted Google Gemini API key.', $adminId);
            $geminiKeyUpdated = true;
        } elseif (filled($validated['gemini_api_key'] ?? null)) {
            PlatformSetting::setValue('ai.gemini_api_key', trim((string) $validated['gemini_api_key']), true, 'Encrypted Google Gemini API key.', $adminId);
            $geminiKeyUpdated = true;
        }

        $openaiKeyUpdated = false;
        if ($request->boolean('clear_openai_api_key')) {
            PlatformSetting::setValue('ai.openai_api_key', null, true, 'Encrypted OpenAI API key.', $adminId);
            $openaiKeyUpdated = true;
        } elseif (filled($validated['openai_api_key'] ?? null)) {
            PlatformSetting::setValue('ai.openai_api_key', trim((string) $validated['openai_api_key']), true, 'Encrypted OpenAI API key.', $adminId);
            $openaiKeyUpdated = true;
        }

        SecurityLog::create([
            'user_id' => $adminId,
            'event_type' => 'platform_ai_settings_updated',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => [
                'ai_enabled' => $aiEnabled,
                'default_provider' => $provider,
                'default_model' => $model,
                'gemini_key_updated' => $geminiKeyUpdated,
                'openai_key_updated' => $openaiKeyUpdated,
            ],
        ]);

        return back()->with('success', 'SABAYRA AI Center configuration updated successfully.');
    }

    /**
     * Test connectivity to the configured or overridden AI provider without charging tenant credits.
     */
    public function testAi(Request $request, AIManager $aiManager): JsonResponse
    {
        $validated = $request->validate([
            'provider' => ['required', 'in:gemini,openai'],
            'model' => ['nullable', 'string', 'max:80'],
            'api_key' => ['nullable', 'string', 'max:500'],
        ]);

        $result = $aiManager->testProviderConnection(
            (string) $validated['provider'],
            $validated['model'] ?? null,
            $validated['api_key'] ?? null
        );

        return response()->json($result, $result['ok'] ? 200 : 422);
    }
}
