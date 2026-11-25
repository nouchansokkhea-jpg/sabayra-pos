<?php

namespace App\Services\Payment;

use App\Models\Business;
use App\Models\BusinessSetting;

class KHQRService
{
    /**
     * Generate an EMVCo-compliant Bakong KHQR dynamic payload string.
     */
    public static function generate(Business $business, float $amount, string $currency = 'USD', ?string $billNumber = null): string
    {
        $currencyCode = strtoupper($currency) === 'KHR' ? '116' : '840';
        $formattedAmount = strtoupper($currency) === 'KHR'
            ? (string) round($amount)
            : number_format($amount, 2, '.', '');

        // Merchant account identifier (Bakong ID)
        $storedBakongId = BusinessSetting::getValue($business->id, 'payment.bakong_account_id');
        $storedMerchantName = BusinessSetting::getValue($business->id, 'payment.bakong_merchant_name');
        $bakongId = $business->bakong_account_id ?? ($storedBakongId ?: 'menuhub_cambodia@nbc');
        $merchantName = $storedMerchantName ?: $business->name;
        $merchantAccountSub = self::tlv('00', $bakongId) . self::tlv('01', substr($business->slug, 0, 25));

        $payload = '';
        $payload .= self::tlv('00', '01'); // Format Indicator
        $payload .= self::tlv('01', '12'); // Dynamic QR (includes amount)
        $payload .= self::tlv('29', $merchantAccountSub); // Bakong Merchant Account
        $payload .= self::tlv('52', '5812'); // MCC: Eating Places & Restaurants
        $payload .= self::tlv('53', $currencyCode); // Transaction Currency
        $payload .= self::tlv('54', $formattedAmount); // Transaction Amount
        $payload .= self::tlv('58', 'KH'); // Country Code
        $payload .= self::tlv('59', substr($merchantName, 0, 25)); // Merchant Name
        $payload .= self::tlv('60', 'Phnom Penh'); // City

        if ($billNumber) {
            $additionalSub = self::tlv('01', substr($billNumber, 0, 25));
            $payload .= self::tlv('62', $additionalSub);
        }

        // Tag 63 (CRC16) header
        $payloadWithCrcTag = $payload . '6304';
        $crc = self::calculateCrc16($payloadWithCrcTag);

        return $payloadWithCrcTag . $crc;
    }

    /**
     * Format Tag-Length-Value string.
     */
    private static function tlv(string $tag, string $value): string
    {
        $length = str_pad((string) strlen($value), 2, '0', STR_PAD_LEFT);
        return $tag . $length . $value;
    }

    /**
     * Calculate 16-bit CRC checksum (CCITT-FALSE, polynomial 0x1021, init 0xFFFF).
     */
    private static function calculateCrc16(string $data): string
    {
        $crc = 0xFFFF;
        $length = strlen($data);

        for ($i = 0; $i < $length; $i++) {
            $crc ^= (ord($data[$i]) << 8);
            for ($j = 0; $j < 8; $j++) {
                if ($crc & 0x8000) {
                    $crc = (($crc << 1) ^ 0x1021) & 0xFFFF;
                } else {
                    $crc = ($crc << 1) & 0xFFFF;
                }
            }
        }

        return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
    }
}
