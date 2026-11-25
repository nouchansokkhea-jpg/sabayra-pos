<?php

namespace App\Services\Security;

use App\Models\SecurityLog;
use App\Models\User;
use App\Services\Tenant\TenantContext;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class TwoFactorAuthenticationService
{
    private const BASE32_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    private const PERIOD_SECONDS = 30;

    private const DIGITS = 6;

    /**
     * Generate a cryptographically random RFC 4648 Base32 secret key.
     */
    public function generateSecretKey(int $length = 32): string
    {
        $secret = '';
        $alphabetLength = strlen(self::BASE32_ALPHABET);
        $bytes = random_bytes($length);

        for ($i = 0; $i < $length; $i++) {
            $secret .= self::BASE32_ALPHABET[ord($bytes[$i]) % $alphabetLength];
        }

        return $secret;
    }

    /**
     * Build a standard otpauth:// URI compatible with Google Authenticator, Authy, 1Password, and Apple Passwords.
     */
    public function getQrCodeUri(User $user, string $secret): string
    {
        $issuer = 'SABAYRA';
        $label = rawurlencode("{$issuer}:{$user->email}");

        return sprintf(
            'otpauth://totp/%s?secret=%s&issuer=%s&algorithm=SHA1&digits=%d&period=%d',
            $label,
            $secret,
            rawurlencode($issuer),
            self::DIGITS,
            self::PERIOD_SECONDS
        );
    }

    /**
     * Render a crisp inline SVG QR code for the provided otpauth:// URI.
     */
    public function renderQrCodeSvg(string $otpAuthUri): string
    {
        return (string) QrCode::format('svg')
            ->size(192)
            ->margin(1)
            ->generate($otpAuthUri);
    }

    /**
     * Compute the 6-digit RFC 6238 TOTP code for a Base32 secret at the given Unix timestamp.
     */
    public function getCurrentCode(string $secret, ?int $timestamp = null): string
    {
        $timeSlice = (int) floor(($timestamp ?? time()) / self::PERIOD_SECONDS);
        $binaryKey = $this->decodeBase32($secret);

        if ($binaryKey === '') {
            return '';
        }

        // Pack 64-bit unsigned big-endian time counter
        $high = ($timeSlice >> 32) & 0xFFFFFFFF;
        $low = $timeSlice & 0xFFFFFFFF;
        $packedCounter = pack('N2', $high, $low);

        $hash = hash_hmac('sha1', $packedCounter, $binaryKey, true);
        $offset = ord($hash[strlen($hash) - 1]) & 0x0F;

        $binaryCode = ((ord($hash[$offset]) & 0x7F) << 24)
            | ((ord($hash[$offset + 1]) & 0xFF) << 16)
            | ((ord($hash[$offset + 2]) & 0xFF) << 8)
            | (ord($hash[$offset + 3]) & 0xFF);

        $otp = $binaryCode % (10 ** self::DIGITS);

        return str_pad((string) $otp, self::DIGITS, '0', STR_PAD_LEFT);
    }

    /**
     * Verify a 6-digit TOTP code against a Base32 secret with optional clock-drift window and replay prevention.
     */
    public function verifyCode(string $secret, string $code, int $window = 1, ?User $replayProtectUser = null): bool
    {
        $normalized = preg_replace('/\s+/', '', trim($code));

        if (! is_string($normalized) || preg_match('/^\d{6}$/', $normalized) !== 1) {
            return false;
        }

        if ($replayProtectUser instanceof User) {
            $replayKey = "sabayra_totp_used:{$replayProtectUser->id}:" . sha1($normalized);
            if (Cache::has($replayKey)) {
                return false;
            }
        }

        $now = time();

        for ($offset = -$window; $offset <= $window; $offset++) {
            $expected = $this->getCurrentCode($secret, $now + ($offset * self::PERIOD_SECONDS));

            if ($expected !== '' && hash_equals($expected, $normalized)) {
                if ($replayProtectUser instanceof User) {
                    $replayKey = "sabayra_totp_used:{$replayProtectUser->id}:" . sha1($normalized);
                    Cache::put($replayKey, true, now()->addSeconds(self::PERIOD_SECONDS * ($window + 1)));
                }

                return true;
            }
        }

        return false;
    }

    /**
     * Generate and store an unconfirmed encrypted TOTP secret for authenticator onboarding.
     *
     * @return array{secret: string, qr_uri: string, qr_svg: string}
     */
    public function beginSetup(User $user): array
    {
        $secret = $this->generateSecretKey();

        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_enabled' => false,
            'two_factor_confirmed_at' => null,
            'two_factor_recovery_codes' => null,
        ])->save();

        SecurityLog::create([
            'user_id' => $user->id,
            'business_id' => TenantContext::get()?->id,
            'event_type' => '2fa_setup_initiated',
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);

        $qrUri = $this->getQrCodeUri($user, $secret);

        return [
            'secret' => $secret,
            'qr_uri' => $qrUri,
            'qr_svg' => $this->renderQrCodeSvg($qrUri),
        ];
    }

    /**
     * Return setup QR & secret payload ONLY while setup is pending confirmation.
     *
     * @return array{secret: string, qr_uri: string, qr_svg: string}|null
     */
    public function getPendingSetupPayload(User $user): ?array
    {
        if (! $user->hasPendingTwoFactorSetup()) {
            return null;
        }

        $secret = (string) $user->two_factor_secret;
        $qrUri = $this->getQrCodeUri($user, $secret);

        return [
            'secret' => $secret,
            'qr_uri' => $qrUri,
            'qr_svg' => $this->renderQrCodeSvg($qrUri),
        ];
    }

    /**
     * Cancel an unconfirmed pending 2FA setup.
     */
    public function cancelSetup(User $user): void
    {
        if ($user->hasPendingTwoFactorSetup()) {
            $user->forceFill([
                'two_factor_secret' => null,
                'two_factor_enabled' => false,
                'two_factor_confirmed_at' => null,
                'two_factor_recovery_codes' => null,
            ])->save();
        }
    }

    /**
     * Verify initial TOTP code, activate 2FA, and return plaintext single-use recovery codes once.
     *
     * @return array{ok: bool, error?: string, recovery_codes?: array<int, string>}
     */
    public function confirmSetup(User $user, string $code): array
    {
        $secret = (string) ($user->two_factor_secret ?? '');

        if ($secret === '') {
            return [
                'ok' => false,
                'error' => 'Please start 2FA setup to generate an authenticator key first.',
            ];
        }

        if (! $this->verifyCode($secret, $code, 1, $user)) {
            return [
                'ok' => false,
                'error' => 'Invalid 6-digit verification code. Confirm your device clock is synchronized and try again.',
            ];
        }

        [$plainCodes, $hashedCodes] = $this->generateRecoveryCodePairs();

        $user->forceFill([
            'two_factor_enabled' => true,
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => $hashedCodes,
        ])->save();

        SecurityLog::create([
            'user_id' => $user->id,
            'business_id' => TenantContext::get()?->id,
            'event_type' => '2fa_enabled',
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);

        return [
            'ok' => true,
            'recovery_codes' => $plainCodes,
        ];
    }

    /**
     * Verify a login 2FA challenge input (either 6-digit TOTP code or single-use recovery code).
     *
     * @return array{ok: bool, method?: string, remaining_recovery_codes?: int}
     */
    public function verifyChallenge(User $user, string $input): array
    {
        if (! $user->hasEnabledTwoFactorAuthentication()) {
            return ['ok' => false];
        }

        $secret = (string) $user->two_factor_secret;
        $trimmed = trim($input);

        if ($this->verifyCode($secret, $trimmed, 1, $user)) {
            return [
                'ok' => true,
                'method' => 'totp',
            ];
        }

        // Fallback: single-use recovery code check (row locked atomically)
        $normalizedRecovery = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $trimmed) ?? '');

        if ($normalizedRecovery !== '') {
            $matched = DB::transaction(function () use ($user, $normalizedRecovery): ?int {
                /** @var User|null $lockedUser */
                $lockedUser = User::where('id', $user->id)->lockForUpdate()->first();

                if (! $lockedUser) {
                    return null;
                }

                $storedHashes = is_array($lockedUser->two_factor_recovery_codes)
                    ? $lockedUser->two_factor_recovery_codes
                    : [];

                $candidateHash = hash('sha256', $normalizedRecovery);
                $matchedIndex = null;

                foreach ($storedHashes as $idx => $storedHash) {
                    if (is_string($storedHash) && hash_equals($storedHash, $candidateHash)) {
                        $matchedIndex = $idx;
                        break;
                    }
                }

                if ($matchedIndex === null) {
                    return null;
                }

                unset($storedHashes[$matchedIndex]);
                $remaining = array_values($storedHashes);

                $lockedUser->forceFill([
                    'two_factor_recovery_codes' => $remaining,
                ])->save();

                return count($remaining);
            });

            if ($matched !== null) {
                $user->refresh();

                SecurityLog::create([
                    'user_id' => $user->id,
                    'business_id' => TenantContext::get()?->id,
                    'event_type' => '2fa_recovery_used',
                    'ip_address' => request()?->ip(),
                    'user_agent' => request()?->userAgent(),
                    'metadata' => ['remaining_recovery_codes' => $matched],
                ]);

                return [
                    'ok' => true,
                    'method' => 'recovery_code',
                    'remaining_recovery_codes' => $matched,
                ];
            }
        }

        SecurityLog::create([
            'user_id' => $user->id,
            'business_id' => TenantContext::get()?->id,
            'event_type' => '2fa_failed',
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);

        return ['ok' => false];
    }

    /**
     * Regenerate 8 single-use recovery codes for an already-enabled 2FA account.
     *
     * @return array<int, string>
     */
    public function regenerateRecoveryCodes(User $user): array
    {
        [$plainCodes, $hashedCodes] = $this->generateRecoveryCodePairs();

        $user->forceFill([
            'two_factor_recovery_codes' => $hashedCodes,
        ])->save();

        SecurityLog::create([
            'user_id' => $user->id,
            'business_id' => TenantContext::get()?->id,
            'event_type' => '2fa_recovery_codes_regenerated',
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);

        return $plainCodes;
    }

    /**
     * Completely disable 2FA and purge stored secret and recovery codes.
     */
    public function disable(User $user): void
    {
        $user->forceFill([
            'two_factor_enabled' => false,
            'two_factor_secret' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_recovery_codes' => null,
        ])->save();

        SecurityLog::create([
            'user_id' => $user->id,
            'business_id' => TenantContext::get()?->id,
            'event_type' => '2fa_disabled',
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }

    /**
     * Generate 8 cryptographically random recovery codes alongside their SHA-256 digests.
     *
     * @return array{0: array<int, string>, 1: array<int, string>}
     */
    private function generateRecoveryCodePairs(int $count = 8): array
    {
        $plainCodes = [];
        $hashedCodes = [];

        for ($i = 0; $i < $count; $i++) {
            $raw = Str::upper(bin2hex(random_bytes(5))); // 10 hex chars -> formatted as XXXXX-XXXXX
            $formatted = substr($raw, 0, 5) . '-' . substr($raw, 5, 5);
            $plainCodes[] = $formatted;
            $hashedCodes[] = hash('sha256', $raw);
        }

        return [$plainCodes, $hashedCodes];
    }

    /**
     * Decode an RFC 4648 Base32 secret string into raw binary bytes.
     */
    private function decodeBase32(string $secret): string
    {
        $cleaned = strtoupper(preg_replace('/[^A-Za-z2-7]/', '', $secret) ?? '');

        if ($cleaned === '') {
            return '';
        }

        $buffer = 0;
        $bitsLeft = 0;
        $output = '';

        $length = strlen($cleaned);
        for ($i = 0; $i < $length; $i++) {
            $val = strpos(self::BASE32_ALPHABET, $cleaned[$i]);
            if ($val === false) {
                continue;
            }

            $buffer = ($buffer << 5) | $val;
            $bitsLeft += 5;

            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $output .= chr(($buffer >> $bitsLeft) & 0xFF);
            }
        }

        return $output;
    }
}
