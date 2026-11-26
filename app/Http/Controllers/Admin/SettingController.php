<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use App\Models\SecurityLog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SettingController extends Controller
{
    /**
     * Display platform system settings including global payment gateway controls.
     */
    public function index(): View
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

        return view('admin.settings.index', compact('platform', 'gatewaySettings'));
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
}
