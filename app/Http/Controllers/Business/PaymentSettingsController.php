<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\BusinessPaymentConfig;
use App\Models\BusinessSetting;
use App\Models\PlatformSetting;
use App\Models\SecurityLog;
use App\Models\User;
use App\Services\Payment\AbaPayWayService;
use App\Services\Payment\BakongKhqrService;
use App\Services\Storage\SupabaseStorageService;
use App\Services\Tenant\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PaymentSettingsController extends Controller
{
    /**
     * Allowed genuine MIME types mapped to safe canonical image extensions.
     *
     * @var array<string, string>
     */
    private const ALLOWED_QR_MIME_MAP = [
        'image/png' => 'png',
        'image/jpeg' => 'jpg',
        'image/webp' => 'webp',
    ];

    public function __construct(
        private AbaPayWayService $abaPayWayService,
        private BakongKhqrService $bakongKhqrService,
    ) {
    }

    /**
     * Persist Tax Settings, Service Charge, Currency, and all Payment Method configurations.
     */
    public function update(Request $request): RedirectResponse
    {
        $business = $this->authorizedBusiness();
        $config = BusinessPaymentConfig::firstOrCreateForBusiness($business);

        $validated = $request->validate([
            'default_currency' => ['required', 'string', 'in:USD,KHR'],
            'exchange_rate_khr' => ['required', 'numeric', 'min:1000', 'max:100000'],
            'tax_name' => ['nullable', 'string', 'max:40'],
            'tax_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'tax_inclusive' => ['nullable', 'boolean'],
            'service_charge_enabled' => ['nullable', 'boolean'],
            'service_charge_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'receipt_footer' => ['nullable', 'string', 'max:255'],

            'bank_transfer_enabled' => ['nullable', 'boolean'],
            'bank_name' => ['nullable', 'string', 'max:120'],
            'bank_account_holder' => ['nullable', 'string', 'max:120'],
            'bank_account_number' => ['nullable', 'string', 'max:60'],
            'bank_instructions' => ['nullable', 'string', 'max:1000'],
            'bank_reference_instructions' => ['nullable', 'string', 'max:500'],

            'static_qr_enabled' => ['nullable', 'boolean'],
            'static_qr_label' => ['nullable', 'string', 'max:80'],
            'static_qr_instructions' => ['nullable', 'string', 'max:1000'],
            'static_qr_require_proof' => ['nullable', 'boolean'],
            'static_qr_image' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp', 'max:5120'],
            'remove_static_qr_image' => ['nullable', 'boolean'],

            'cash_enabled' => ['nullable', 'boolean'],
            'cod_enabled' => ['nullable', 'boolean'],
            'cod_instructions' => ['nullable', 'string', 'max:500'],

            'aba_payway_enabled' => ['nullable', 'boolean'],
            'aba_payway_link' => ['nullable', 'string', 'max:255'],
            'aba_merchant_id' => ['nullable', 'string', 'max:80'],
            'aba_api_key' => ['nullable', 'string', 'max:255'],
            'clear_aba_api_key' => ['nullable', 'boolean'],
            'aba_environment' => ['nullable', 'string', 'in:sandbox,production'],

            'bakong_enabled' => ['nullable', 'boolean'],
            'bakong_account_id' => ['nullable', 'string', 'max:120'],
            'bakong_merchant_name' => ['nullable', 'string', 'max:100'],
        ]);

        // Support legacy callers/tests that toggle via khqr_enabled while keeping new forms clean.
        $taxName = trim((string) ($validated['tax_name'] ?? ($business->tax_name ?: 'VAT')));
        if ($taxName === '') {
            $taxName = 'VAT';
        }
        $taxPercent = $request->has('tax_percent')
            ? round((float) ($validated['tax_percent'] ?? 0), 2)
            : round((float) ($business->tax_percent ?? 0), 2);
        $taxInclusive = $request->has('tax_inclusive')
            ? $request->boolean('tax_inclusive')
            : (bool) $business->tax_inclusive;

        $serviceChargeEnabled = $request->has('service_charge_enabled')
            ? $request->boolean('service_charge_enabled')
            : (bool) $business->service_charge_enabled;
        $serviceChargeRate = round((float) ($validated['service_charge_rate'] ?? $business->service_charge_rate ?? 0), 2);

        $bankEnabled = $request->has('bank_transfer_enabled')
            ? $request->boolean('bank_transfer_enabled')
            : (bool) $config->bank_transfer_enabled;
        $bankName = $this->cleanNullableString($validated['bank_name'] ?? $config->bank_name);
        $bankAccountHolder = $this->cleanNullableString($validated['bank_account_holder'] ?? $config->bank_account_holder);
        $bankAccountNumber = $this->cleanNullableString($validated['bank_account_number'] ?? $config->bank_account_number);

        if ($bankEnabled && (! filled($bankName) || ! filled($bankAccountHolder) || ! filled($bankAccountNumber))) {
            throw ValidationException::withMessages([
                'bank_name' => 'Bank name, account holder name, and account number are required when Bank Transfer is enabled.',
            ]);
        }

        $staticQrEnabled = $request->has('static_qr_enabled')
            ? $request->boolean('static_qr_enabled')
            : (bool) $config->static_qr_enabled;
        $staticQrLabel = $this->cleanNullableString($validated['static_qr_label'] ?? $config->static_qr_label) ?: 'QR Payment';
        $staticQrRequireProof = $request->has('static_qr_require_proof')
            ? $request->boolean('static_qr_require_proof')
            : (bool) $config->static_qr_require_proof;

        $staticQrPath = $config->static_qr_image_path;
        if ($request->boolean('remove_static_qr_image')) {
            $this->deleteLocalStaticQrIfOwned($staticQrPath);
            $staticQrPath = null;
        }
        if ($request->hasFile('static_qr_image')) {
            $uploadedPath = $this->storeStaticQrImage($business, $request->file('static_qr_image'));
            if ($staticQrPath && $staticQrPath !== $uploadedPath) {
                $this->deleteLocalStaticQrIfOwned($staticQrPath);
            }
            $staticQrPath = $uploadedPath;
        }

        if ($staticQrEnabled && ! filled($staticQrPath)) {
            throw ValidationException::withMessages([
                'static_qr_image' => 'Upload a valid PNG, JPG, or WebP static QR image before enabling QR Payment.',
            ]);
        }

        $cashEnabled = $request->has('cash_enabled')
            ? $request->boolean('cash_enabled')
            : ($config->exists ? (bool) $config->cash_enabled : true);
        $codEnabled = $request->has('cod_enabled')
            ? $request->boolean('cod_enabled')
            : (bool) $config->cod_enabled;

        $abaEnabled = $request->has('aba_payway_enabled')
            ? $request->boolean('aba_payway_enabled')
            : (bool) $config->aba_payway_enabled;
        $abaLink = $this->cleanNullableString($request->has('aba_payway_link') ? ($validated['aba_payway_link'] ?? null) : $config->aba_payway_link);
        $abaMerchantId = $this->cleanNullableString($request->has('aba_merchant_id') ? ($validated['aba_merchant_id'] ?? null) : $config->aba_merchant_id);

        $effectiveAbaApiKey = $config->aba_api_key;
        $abaCredentialsChanged = ($abaLink !== $config->aba_payway_link) || ($abaMerchantId !== $config->aba_merchant_id);
        if ($request->boolean('clear_aba_api_key')) {
            $effectiveAbaApiKey = null;
            $abaCredentialsChanged = true;
        } elseif (filled($validated['aba_api_key'] ?? null)) {
            $effectiveAbaApiKey = trim((string) $validated['aba_api_key']);
            $abaCredentialsChanged = true;
        }

        $abaValidation = $this->abaPayWayService->validateConfigurationInput(
            $abaLink,
            $abaMerchantId,
            is_string($effectiveAbaApiKey) ? $effectiveAbaApiKey : null,
            $abaEnabled
        );
        if (! $abaValidation['ok']) {
            throw ValidationException::withMessages([
                'aba_payway_link' => $abaValidation['error'] ?? 'Invalid ABA PayWay configuration.',
            ]);
        }

        $bakongPreference = $request->has('bakong_enabled')
            ? $request->boolean('bakong_enabled')
            : ($request->has('khqr_enabled')
                ? $request->boolean('khqr_enabled')
                : (bool) $config->bakong_enabled);

        $bakongAccountId = $request->has('bakong_account_id')
            ? $this->cleanLowerNullableString($validated['bakong_account_id'] ?? null)
            : $this->cleanLowerNullableString($config->bakong_account_id);
        $bakongMerchantName = $request->has('bakong_merchant_name')
            ? $this->cleanNullableString($validated['bakong_merchant_name'] ?? null)
            : $config->bakong_merchant_name;

        $previousBakongId = $this->cleanLowerNullableString($config->bakong_account_id);
        $bakongVerified = (bool) $config->bakong_verified;
        $bakongVerifiedAt = $config->bakong_verified_at;
        $bakongStatus = $config->bakong_verification_status ?: 'not_verified';
        $bakongMessage = $config->bakong_verification_message;

        if ($bakongAccountId !== $previousBakongId) {
            $bakongVerified = false;
            $bakongVerifiedAt = null;
            $bakongStatus = $bakongAccountId === null ? 'not_verified' : 'pending_verification';
            $bakongMessage = $bakongAccountId === null
                ? null
                : 'Account ID changed. Click Verify to confirm this account via NBC Bakong Open API.';
        }

        DB::transaction(function () use (
            $business,
            $config,
            $validated,
            $taxName,
            $taxPercent,
            $taxInclusive,
            $serviceChargeEnabled,
            $serviceChargeRate,
            $bankEnabled,
            $bankName,
            $bankAccountHolder,
            $bankAccountNumber,
            $staticQrEnabled,
            $staticQrLabel,
            $staticQrPath,
            $staticQrRequireProof,
            $cashEnabled,
            $codEnabled,
            $abaEnabled,
            $abaLink,
            $abaMerchantId,
            $effectiveAbaApiKey,
            $abaCredentialsChanged,
            $bakongPreference,
            $bakongAccountId,
            $bakongMerchantName,
            $bakongVerified,
            $bakongVerifiedAt,
            $bakongStatus,
            $bakongMessage,
            $request
        ): void {
            $business->update([
                'default_currency' => $validated['default_currency'],
                'exchange_rate_khr' => $validated['exchange_rate_khr'],
                'tax_name' => $taxName,
                'tax_percent' => $taxPercent,
                'tax_inclusive' => $taxInclusive,
                'service_charge_enabled' => $serviceChargeEnabled,
                'service_charge_rate' => $serviceChargeRate,
            ]);

            $configPayload = [
                'bank_transfer_enabled' => $bankEnabled,
                'bank_name' => $bankName,
                'bank_account_holder' => $bankAccountHolder,
                'bank_account_number' => $bankAccountNumber,
                'bank_instructions' => $this->cleanNullableString($validated['bank_instructions'] ?? $config->bank_instructions),
                'bank_reference_instructions' => $this->cleanNullableString($validated['bank_reference_instructions'] ?? $config->bank_reference_instructions),

                'static_qr_enabled' => $staticQrEnabled,
                'static_qr_label' => $staticQrLabel,
                'static_qr_image_path' => $staticQrPath,
                'static_qr_instructions' => $this->cleanNullableString($validated['static_qr_instructions'] ?? $config->static_qr_instructions),
                'static_qr_require_proof' => $staticQrRequireProof,

                'cash_enabled' => $cashEnabled,
                'cod_enabled' => $codEnabled,
                'cod_instructions' => $this->cleanNullableString($validated['cod_instructions'] ?? $config->cod_instructions),

                'aba_payway_enabled' => $abaEnabled,
                'aba_payway_link' => $abaLink,
                'aba_merchant_id' => $abaMerchantId,
                'aba_api_key' => $effectiveAbaApiKey,
                'aba_environment' => $validated['aba_environment'] ?? ($config->aba_environment ?: 'sandbox'),

                'bakong_enabled' => $bakongPreference,
                'bakong_account_id' => $bakongAccountId,
                'bakong_merchant_name' => $bakongMerchantName,
                'bakong_verified' => $bakongVerified,
                'bakong_verified_at' => $bakongVerifiedAt,
                'bakong_verification_status' => $bakongStatus,
                'bakong_verification_message' => $bakongMessage,
            ];

            if ($abaCredentialsChanged) {
                $configPayload['aba_test_status'] = ($abaLink !== null && $abaMerchantId === null) ? 'link_only' : 'untested';
                $configPayload['aba_last_tested_at'] = null;
                $configPayload['aba_test_message'] = null;
            }

            $config->update($configPayload);

            // Keep legacy BusinessSetting keys synchronized without diverging state.
            BusinessSetting::setValue($business->id, 'payment.bakong_account_id', $bakongAccountId);
            BusinessSetting::setValue($business->id, 'payment.bakong_merchant_name', $bakongMerchantName);
            BusinessSetting::setValue($business->id, 'payment.khqr_enabled', $bakongPreference);
            BusinessSetting::setValue($business->id, 'payment.cash_enabled', $cashEnabled);
            BusinessSetting::setValue(
                $business->id,
                'payment.receipt_footer',
                $this->cleanNullableString($request->has('receipt_footer')
                    ? ($validated['receipt_footer'] ?? null)
                    : BusinessSetting::getValue($business->id, 'payment.receipt_footer'))
            );

            SecurityLog::create([
                'user_id' => Auth::id(),
                'business_id' => $business->id,
                'event_type' => 'payment_tax_settings_updated',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'metadata' => [
                    'tax_name' => $taxName,
                    'tax_percent' => $taxPercent,
                    'tax_inclusive' => $taxInclusive,
                    'service_charge_enabled' => $serviceChargeEnabled,
                    'service_charge_rate' => $serviceChargeRate,
                    'enabled_methods' => array_values(array_keys(array_filter([
                        'bank_transfer' => $bankEnabled,
                        'static_qr' => $staticQrEnabled,
                        'cash' => $cashEnabled,
                        'cod' => $codEnabled,
                        'aba_payway' => $abaEnabled,
                        'bakong' => $bakongPreference,
                    ]))),
                ],
            ]);
        });

        return redirect()
            ->route('business.settings.index', ['section' => 'payment'])
            ->with('success', 'Payment, tax, and service charge settings saved successfully.');
    }

    /**
     * Verify a business's Bakong Account ID against the official NBC Bakong Open API.
     */
    public function verifyBakong(Request $request): RedirectResponse|JsonResponse
    {
        $business = $this->authorizedBusiness();
        $config = BusinessPaymentConfig::firstOrCreateForBusiness($business);

        $validated = $request->validate([
            'bakong_account_id' => ['required', 'string', 'max:120'],
            'bakong_merchant_name' => ['nullable', 'string', 'max:100'],
        ]);

        if (array_key_exists('bakong_merchant_name', $validated)) {
            $config->update([
                'bakong_merchant_name' => $this->cleanNullableString($validated['bakong_merchant_name']),
            ]);
            BusinessSetting::setValue($business->id, 'payment.bakong_merchant_name', $config->bakong_merchant_name);
        }

        $result = $this->bakongKhqrService->verifyAccount($config, $validated['bakong_account_id']);
        BusinessSetting::setValue($business->id, 'payment.bakong_account_id', $config->fresh()?->bakong_account_id);

        SecurityLog::create([
            'user_id' => Auth::id(),
            'business_id' => $business->id,
            'event_type' => 'bakong_account_verification_attempt',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => [
                'status' => $result['status'],
                'verified' => $result['verified'],
                'bakong_account_id' => $config->fresh()?->bakong_account_id,
                'platform_enabled' => PlatformSetting::isBakongEnabled(),
            ],
        ]);

        if ($request->expectsJson()) {
            return response()->json($result, $result['ok'] ? 200 : 422);
        }

        $flashType = $result['ok'] ? 'success' : 'error';

        return redirect()
            ->route('business.settings.index', ['section' => 'payment'])
            ->with($flashType, $result['message']);
    }

    /**
     * Test the saved or submitted ABA PayWay Merchant API / PayWay Link configuration.
     */
    public function testAbaConnection(Request $request): RedirectResponse|JsonResponse
    {
        $business = $this->authorizedBusiness();
        $config = BusinessPaymentConfig::firstOrCreateForBusiness($business);

        $validated = $request->validate([
            'aba_payway_link' => ['nullable', 'string', 'max:255'],
            'aba_merchant_id' => ['nullable', 'string', 'max:80'],
            'aba_api_key' => ['nullable', 'string', 'max:255'],
            'aba_environment' => ['nullable', 'string', 'in:sandbox,production'],
        ]);

        $link = $request->has('aba_payway_link')
            ? $this->cleanNullableString($validated['aba_payway_link'] ?? null)
            : $config->aba_payway_link;
        $merchantId = $request->has('aba_merchant_id')
            ? $this->cleanNullableString($validated['aba_merchant_id'] ?? null)
            : $config->aba_merchant_id;
        $apiKey = filled($validated['aba_api_key'] ?? null)
            ? trim((string) $validated['aba_api_key'])
            : $config->aba_api_key;
        $environment = $validated['aba_environment'] ?? ($config->aba_environment ?: 'sandbox');

        $validation = $this->abaPayWayService->validateConfigurationInput($link, $merchantId, is_string($apiKey) ? $apiKey : null, false);
        if (! $validation['ok']) {
            if ($request->expectsJson()) {
                return response()->json(['ok' => false, 'message' => $validation['error']], 422);
            }

            return redirect()
                ->route('business.settings.index', ['section' => 'payment'])
                ->with('error', $validation['error'] ?? 'Invalid ABA PayWay configuration.');
        }

        $config->update([
            'aba_payway_link' => $link,
            'aba_merchant_id' => $merchantId,
            'aba_api_key' => $apiKey,
            'aba_environment' => $environment,
        ]);

        $result = $this->abaPayWayService->testConnection($config->fresh());

        SecurityLog::create([
            'user_id' => Auth::id(),
            'business_id' => $business->id,
            'event_type' => 'aba_payway_connection_test',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => [
                'status' => $result['status'],
                'ok' => $result['ok'],
                'environment' => $environment,
                'has_api_credentials' => $config->fresh()?->hasAbaMerchantApiCredentials(),
            ],
        ]);

        if ($request->expectsJson()) {
            return response()->json($result, $result['ok'] ? 200 : 422);
        }

        $flashType = $result['ok'] ? 'success' : 'error';

        return redirect()
            ->route('business.settings.index', ['section' => 'payment'])
            ->with($flashType, $result['message']);
    }

    /**
     * Validate genuine image bytes using finfo and store in persistent storage.
     */
    private function storeStaticQrImage(Business $business, UploadedFile $file): string
    {
        $bytes = @file_get_contents($file->getRealPath());
        if (! is_string($bytes) || $bytes === '') {
            throw ValidationException::withMessages([
                'static_qr_image' => 'Uploaded static QR file could not be read.',
            ]);
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $detectedMime = (string) $finfo->buffer($bytes);
        $imageInfo = @getimagesizefromstring($bytes);

        if (! array_key_exists($detectedMime, self::ALLOWED_QR_MIME_MAP) || $imageInfo === false) {
            throw ValidationException::withMessages([
                'static_qr_image' => 'Static QR image must be a genuine PNG, JPG, or WebP file.',
            ]);
        }

        $ext = self::ALLOWED_QR_MIME_MAP[$detectedMime];
        $folder = "businesses/{$business->id}/payment-qr";
        try {
            $remoteUrl = (new SupabaseStorageService())->upload($file, $folder);
            if (is_string($remoteUrl) && str_starts_with($remoteUrl, 'https://')) {
                return $remoteUrl;
            }
        } catch (\Throwable $e) {
            // Fall back to persistent inline data URI + local disk cache when remote object storage is unavailable.
        }

        $relativePath = "{$folder}/" . Str::uuid() . ".{$ext}";
        Storage::disk('public')->put($relativePath, $bytes);

        return 'data:' . $detectedMime . ';base64,' . base64_encode($bytes);
    }

    /**
     * Delete locally persisted static QR files when replaced or removed.
     */
    private function deleteLocalStaticQrIfOwned(?string $storedPath): void
    {
        if (! is_string($storedPath) || ! str_starts_with($storedPath, '/storage/')) {
            return;
        }

        $relative = ltrim(substr($storedPath, strlen('/storage/')), '/');
        if ($relative !== '') {
            Storage::disk('public')->delete($relative);
        }
    }

    private function cleanNullableString(mixed $value): ?string
    {
        $trimmed = trim((string) ($value ?? ''));

        return $trimmed === '' ? null : $trimmed;
    }

    private function cleanLowerNullableString(mixed $value): ?string
    {
        $trimmed = strtolower(trim((string) ($value ?? '')));

        return $trimmed === '' ? null : $trimmed;
    }

    private function authorizedBusiness(): Business
    {
        $business = TenantContext::get();
        /** @var User|null $user */
        $user = Auth::user();

        if (! $business || ! $user || ! $user->canManageBusinessSettings($business)) {
            abort(403, 'You do not have permission to manage business settings.');
        }

        return $business;
    }
}
