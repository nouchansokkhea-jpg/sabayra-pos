<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\BusinessSetting;
use App\Models\NotificationPreference;
use App\Models\SecurityLog;
use App\Models\TelegramConnection;
use App\Models\TelegramNotificationLog;
use App\Models\User;
use App\Services\Storage\SupabaseStorageService;
use App\Services\Security\TwoFactorAuthenticationService;
use App\Services\Telegram\TelegramConnectionService;
use App\Services\Tenant\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class SettingController extends Controller
{
    /**
     * Business-level sections requiring settings management authorization.
     *
     * @var array<int, string>
     */
    public const BUSINESS_SECTIONS = ['general', 'business', 'payment', 'notifications'];

    /**
     * All supported Settings sections.
     *
     * @var array<int, string>
     */
    public const ALL_SECTIONS = ['general', 'business', 'payment', 'notifications', 'security', 'account'];

    /**
     * Display the tabbed Business & Account Settings hub.
     */
    public function index(Request $request): View
    {
        $viewData = self::buildViewData($request);

        return view('business.settings.index', $viewData);
    }

    /**
     * Build shared view data for both Settings Hub and Security Center views.
     *
     * @return array<string, mixed>
     */
    public static function buildViewData(Request $request, ?string $forcedSection = null): array
    {
        $business = TenantContext::get();
        /** @var User $user */
        $user = Auth::user();

        $canManageBusinessSettings = $business ? $user->canManageBusinessSettings($business) : false;

        $requestedSection = $forcedSection ?? $request->query('section', session('active_section'));

        if ($requestedSection && !in_array($requestedSection, self::ALL_SECTIONS, true)) {
            $requestedSection = null;
        }

        if ($requestedSection && in_array($requestedSection, self::BUSINESS_SECTIONS, true) && !$canManageBusinessSettings) {
            abort(403, 'You do not have permission to manage business settings.');
        }

        $activeSection = $requestedSection ?? ($canManageBusinessSettings ? 'general' : 'security');

        $storedSettings = $business ? BusinessSetting::getAllForBusiness($business->id) : [];

        $securityLogs = SecurityLog::where('user_id', $user->id)
            ->latest('created_at')
            ->take(15)
            ->get();

        $activeSessionCount = 1;
        if (config('session.driver') === 'database' && Schema::hasTable(config('session.table', 'sessions'))) {
            $activeSessionCount = max(
                1,
                DB::table(config('session.table', 'sessions'))
                    ->where('user_id', $user->id)
                    ->count()
            );
        }

        $telegramConnection = null;
        $telegramLogs = collect();
        $telegramPendingLink = null;
        $notificationPreferences = [];
        $notificationBranchId = null;
        $businessBranches = collect();

        if ($business && $canManageBusinessSettings) {
            if (Schema::hasTable('telegram_connections')) {
                $telegramConnection = TelegramConnection::withoutGlobalScopes()
                    ->where('business_id', $business->id)
                    ->first();

                if (! $telegramConnection?->is_active && app(TelegramConnectionService::class)->syncPendingVerificationFromUpdates($business)) {
                    $telegramConnection = TelegramConnection::withoutGlobalScopes()
                        ->where('business_id', $business->id)
                        ->first();
                }
            }

            if (Schema::hasTable('telegram_notification_logs')) {
                $telegramLogs = TelegramNotificationLog::withoutGlobalScopes()
                    ->where('business_id', $business->id)
                    ->latest('created_at')
                    ->take(8)
                    ->get();
            }

            if (Schema::hasTable('notification_preferences')) {
                $notificationPreferences = NotificationPreference::resolveForBusiness($business->id);
                $notificationBranchId = collect($notificationPreferences)
                    ->pluck('branch_id')
                    ->filter()
                    ->first();
            }

            if (Schema::hasTable('business_branches')) {
                $businessBranches = $business->branches()
                    ->where('is_active', true)
                    ->orderBy('name')
                    ->get(['id', 'name']);
            }

            $sessionLink = session("telegram_link_{$business->id}");
            if (is_array($sessionLink) && !empty($sessionLink['expires_at'])) {
                if (now()->lessThan($sessionLink['expires_at']) && !$telegramConnection?->is_active) {
                    $telegramPendingLink = $sessionLink;
                } else {
                    session()->forget("telegram_link_{$business->id}");
                }
            }
        }

        $mailMailer = (string) config('mail.default', 'log');

        return [
            'business' => $business,
            'user' => $user,
            'canManageBusinessSettings' => $canManageBusinessSettings,
            'activeSection' => $activeSection,
            'businessTypes' => config('menuhub.business_types'),
            'currencies' => config('menuhub.currencies'),
            'storedSettings' => $storedSettings,
            'securityLogs' => $securityLogs,
            'logs' => $securityLogs,
            'activeSessionCount' => $activeSessionCount,
            'telegramConnection' => $telegramConnection,
            'telegramLogs' => $telegramLogs,
            'telegramPendingLink' => $telegramPendingLink,
            'officialBotConfigured' => filled(config('services.telegram.bot_token')),
            'officialBotUsername' => ltrim((string) config('services.telegram.bot_username', 'SabayraBot'), '@'),
            'notificationCatalog' => NotificationPreference::catalogByGroup(),
            'notificationPreferences' => $notificationPreferences,
            'notificationBranchId' => $notificationBranchId,
            'businessBranches' => $businessBranches,
            'emailConfigured' => ! in_array($mailMailer, ['log', 'array', 'null', ''], true),
            'mailMailer' => $mailMailer,
            'pendingQueueJobsCount' => Schema::hasTable('jobs') ? (int) DB::table('jobs')->count() : 0,
            'twoFactorEnabled' => $user->hasEnabledTwoFactorAuthentication(),
            'twoFactorPendingSetup' => $user->hasPendingTwoFactorSetup(),
            'twoFactorSetupPayload' => app(TwoFactorAuthenticationService::class)->getPendingSetupPayload($user),
            'twoFactorRecoveryCodesPlain' => session('two_factor_recovery_codes_plain'),
            'remainingRecoveryCodesCount' => is_array($user->two_factor_recovery_codes) ? count($user->two_factor_recovery_codes) : 0,
        ];
    }

    /**
     * Full backward-compatible settings update endpoint (PUT /app/settings).
     */
    public function update(Request $request): RedirectResponse
    {
        $business = $this->authorizedBusiness();

        $this->prepareSlugInput($request, $business);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('businesses', 'slug')->ignore($business->id), 'regex:/^[\p{L}\p{M}\p{N}\-]+$/u'],
            'tagline' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'business_type' => ['required', 'string', 'in:' . implode(',', array_keys(config('menuhub.business_types')))],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'telegram_handle' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', 'in:active,inactive'],
            'logo_url' => ['nullable', 'url', 'max:1000'],
            'logo_file' => ['nullable', 'image', 'max:5120'],
            'cover_url' => ['nullable', 'url', 'max:1000'],
            'cover_file' => ['nullable', 'image', 'max:10240'],
            'default_currency' => ['required', 'string', 'in:USD,KHR'],
            'exchange_rate_khr' => ['required', 'numeric', 'min:1000'],
            'tax_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'theme_preset' => ['nullable', 'string', 'max:50'],
            'public_url' => ['nullable', 'string', 'max:255'],
            'primary_color' => ['nullable', 'string', 'max:30'],
            'secondary_color' => ['nullable', 'string', 'max:30'],
            'font' => ['nullable', 'string', 'max:100'],
            'layout' => ['nullable', 'string', 'in:modern,minimal,grid,list'],
            'opening_hours' => ['nullable', 'array'],
            'social_links' => ['nullable', 'array'],
            'social_links.telegram' => ['nullable', 'string', 'max:255'],
            'social_links.facebook' => ['nullable', 'string', 'max:255'],
            'social_links.instagram' => ['nullable', 'string', 'max:255'],
            'social_links.tiktok' => ['nullable', 'string', 'max:255'],
        ]);

        $validated = $this->handleBrandMediaUploads($request, $validated);
        $validated['theme_settings'] = $this->buildThemeSettings($business, $validated);

        if (!empty($validated['social_links']['telegram'])) {
            $validated['telegram_handle'] = $validated['social_links']['telegram'];
        }

        unset(
            $validated['theme_preset'],
            $validated['primary_color'],
            $validated['secondary_color'],
            $validated['font'],
            $validated['layout'],
            $validated['public_url'],
            $validated['social_links']
        );

        $validated['tax_percent'] = $validated['tax_percent'] ?? 0.00;
        $business->update($validated);

        return redirect()
            ->route('business.settings.index', ['section' => 'general'])
            ->with('success', 'Business profile & settings updated successfully.');
    }

    /**
     * Update General Storefront & Appearance settings.
     */
    public function updateGeneral(Request $request): RedirectResponse
    {
        $business = $this->authorizedBusiness();

        $this->prepareSlugInput($request, $business);

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:active,inactive'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('businesses', 'slug')->ignore($business->id), 'regex:/^[\p{L}\p{M}\p{N}\-]+$/u'],
            'public_url' => ['nullable', 'string', 'max:255'],
            'theme_preset' => ['nullable', 'string', 'max:50'],
            'primary_color' => ['nullable', 'string', 'max:30'],
            'secondary_color' => ['nullable', 'string', 'max:30'],
            'font' => ['nullable', 'string', 'max:100'],
            'layout' => ['nullable', 'string', 'in:modern,minimal,grid,list'],
            'auto_accept_orders' => ['nullable', 'boolean'],
            'show_khr_prices' => ['nullable', 'boolean'],
        ]);

        $themeSettings = $this->buildThemeSettings($business, $validated);

        $business->update([
            'status' => $validated['status'],
            'slug' => $validated['slug'],
            'theme_settings' => $themeSettings,
        ]);

        BusinessSetting::setValue($business->id, 'general.auto_accept_orders', $request->boolean('auto_accept_orders'));
        BusinessSetting::setValue($business->id, 'general.show_khr_prices', $request->boolean('show_khr_prices', true));

        return redirect()
            ->route('business.settings.index', ['section' => 'general'])
            ->with('success', 'General & storefront appearance settings saved.');
    }

    /**
     * Update Business Information, Branding Assets, Hours, and Social Links.
     */
    public function updateBusinessInfo(Request $request): RedirectResponse
    {
        $business = $this->authorizedBusiness();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'business_type' => ['required', 'string', 'in:' . implode(',', array_keys(config('menuhub.business_types')))],
            'tagline' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string'],
            'logo_url' => ['nullable', 'url', 'max:1000'],
            'logo_file' => ['nullable', 'image', 'max:5120'],
            'cover_url' => ['nullable', 'url', 'max:1000'],
            'cover_file' => ['nullable', 'image', 'max:10240'],
            'opening_hours' => ['nullable', 'array'],
            'social_links' => ['nullable', 'array'],
            'social_links.telegram' => ['nullable', 'string', 'max:255'],
            'social_links.facebook' => ['nullable', 'string', 'max:255'],
            'social_links.instagram' => ['nullable', 'string', 'max:255'],
            'social_links.tiktok' => ['nullable', 'string', 'max:255'],
        ]);

        $validated = $this->handleBrandMediaUploads($request, $validated);

        $existingTheme = $business->theme_settings ?? [];
        $existingTheme['social_links'] = $validated['social_links'] ?? ($existingTheme['social_links'] ?? []);

        if (!empty($validated['social_links']['telegram'])) {
            $validated['telegram_handle'] = $validated['social_links']['telegram'];
        }

        unset($validated['social_links']);
        $validated['theme_settings'] = $existingTheme;

        $business->update($validated);

        return redirect()
            ->route('business.settings.index', ['section' => 'business'])
            ->with('success', 'Business information & branding updated successfully.');
    }

    /**
     * Update Payment, Currency, Exchange Rate, Tax & Bakong KHQR settings.
     */
    public function updatePaymentTax(Request $request): RedirectResponse
    {
        $business = $this->authorizedBusiness();

        $validated = $request->validate([
            'default_currency' => ['required', 'string', 'in:USD,KHR'],
            'exchange_rate_khr' => ['required', 'numeric', 'min:1000'],
            'tax_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'bakong_account_id' => ['nullable', 'string', 'max:120'],
            'bakong_merchant_name' => ['nullable', 'string', 'max:100'],
            'receipt_footer' => ['nullable', 'string', 'max:255'],
        ]);

        $business->update([
            'default_currency' => $validated['default_currency'],
            'exchange_rate_khr' => $validated['exchange_rate_khr'],
            'tax_percent' => $validated['tax_percent'] ?? 0.00,
        ]);

        BusinessSetting::setValue($business->id, 'payment.bakong_account_id', $validated['bakong_account_id'] ?? null);
        BusinessSetting::setValue($business->id, 'payment.bakong_merchant_name', $validated['bakong_merchant_name'] ?? null);
        BusinessSetting::setValue($business->id, 'payment.khqr_enabled', $request->boolean('khqr_enabled', true));
        BusinessSetting::setValue($business->id, 'payment.cash_enabled', $request->boolean('cash_enabled', true));
        BusinessSetting::setValue($business->id, 'payment.receipt_footer', $validated['receipt_footer'] ?? null);

        return redirect()
            ->route('business.settings.index', ['section' => 'payment'])
            ->with('success', 'Payment, currency, and tax configuration updated.');
    }

    /**
     * Update the authenticated user's personal account profile & locale.
     */
    public function updateAccount(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:50'],
            'preferred_locale' => ['required', 'string', 'in:en,km'],
        ]);

        $emailChanged = $user->email !== $validated['email'];
        $user->fill($validated);

        if ($emailChanged) {
            $user->email_verified_at = null;
        }

        $user->save();

        session(['locale' => $validated['preferred_locale']]);
        App::setLocale($validated['preferred_locale']);

        SecurityLog::create([
            'user_id' => $user->id,
            'business_id' => TenantContext::get()?->id,
            'event_type' => 'account_updated',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => ['email_changed' => $emailChanged],
        ]);

        return redirect()
            ->route('business.settings.index', ['section' => 'account'])
            ->with('success', 'Personal account settings updated successfully.');
    }

    /**
     * Update the authenticated user's account password after current password verification.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        SecurityLog::create([
            'user_id' => $user->id,
            'business_id' => TenantContext::get()?->id,
            'event_type' => 'password_changed',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()
            ->route('business.settings.index', ['section' => 'account'])
            ->with('success', 'Your account password has been changed securely.');
    }

    /**
     * Resolve active business and enforce business settings authorization.
     */
    private function authorizedBusiness(): Business
    {
        $business = TenantContext::get();
        /** @var User|null $user */
        $user = Auth::user();

        if (!$business || !$user || !$user->canManageBusinessSettings($business)) {
            abort(403, 'You do not have permission to manage business settings.');
        }

        return $business;
    }

    /**
     * Normalize Unicode/Khmer public menu slug on the incoming request.
     */
    private function prepareSlugInput(Request $request, Business $business): void
    {
        if ($request->filled('slug')) {
            $raw = trim((string) $request->input('slug'));
            $cleanedSlug = preg_replace('/[\s_]+/u', '-', $raw);
            $cleanedSlug = preg_replace('/[^\p{L}\p{M}\p{N}\-]+/u', '', $cleanedSlug);
            $cleanedSlug = trim((string) $cleanedSlug, '-');
            $request->merge([
                'slug' => !empty($cleanedSlug) ? mb_strtolower($cleanedSlug, 'UTF-8') : $business->slug,
            ]);
        } else {
            $request->merge(['slug' => $business->slug]);
        }
    }

    /**
     * Upload logo and cover images via SupabaseStorageService when files are present.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function handleBrandMediaUploads(Request $request, array $validated): array
    {
        if ($request->hasFile('logo_file')) {
            $logoUrl = (new SupabaseStorageService())->upload($request->file('logo_file'), 'logos');
            if ($logoUrl) {
                $validated['logo_url'] = $logoUrl;
            }
        }
        unset($validated['logo_file']);

        if ($request->hasFile('cover_file')) {
            $coverUrl = (new SupabaseStorageService())->upload($request->file('cover_file'), 'covers');
            if ($coverUrl) {
                $validated['cover_url'] = $coverUrl;
            }
        }
        unset($validated['cover_file']);

        return $validated;
    }

    /**
     * Merge storefront theme customization fields safely with existing theme settings.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function buildThemeSettings(Business $business, array $validated): array
    {
        $existingTheme = $business->theme_settings ?? [];

        $publicUrl = $existingTheme['public_url'] ?? null;
        if (array_key_exists('public_url', $validated)) {
            $rawPublicUrl = trim((string) ($validated['public_url'] ?? ''));
            $publicUrl = ($rawPublicUrl !== '' && !Str::contains($rawPublicUrl, 'kheatopup.store'))
                ? rtrim($rawPublicUrl, '/')
                : null;
        }

        return array_merge($existingTheme, [
            'theme_preset' => $validated['theme_preset'] ?? ($existingTheme['theme_preset'] ?? 'orange'),
            'primary_color' => $validated['primary_color'] ?? ($existingTheme['primary_color'] ?? '#f97316'),
            'secondary_color' => $validated['secondary_color'] ?? ($existingTheme['secondary_color'] ?? '#1f2937'),
            'font' => $validated['font'] ?? ($existingTheme['font'] ?? 'Inter, Kantumruy Pro, Battambang, sans-serif'),
            'layout' => $validated['layout'] ?? ($existingTheme['layout'] ?? 'modern'),
            'public_url' => $publicUrl,
            'social_links' => $validated['social_links'] ?? ($existingTheme['social_links'] ?? []),
        ]);
    }
}
