<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\PaymentProof;
use App\Models\PaymentTransaction;
use App\Models\SecurityLog;
use App\Models\User;
use App\Services\Notifications\BusinessNotificationService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

class PaymentProofService
{
    /**
     * Allowed real image MIME types mapped to safe canonical extensions.
     *
     * @var array<string, string>
     */
    private const ALLOWED_MIME_MAP = [
        'image/png' => 'png',
        'image/jpeg' => 'jpg',
        'image/webp' => 'webp',
    ];

    /**
     * Store a validated customer payment proof securely on private disk and set order status to pending_verification.
     * Never marks an order as paid automatically on upload.
     */
    public function storeCustomerProof(
        Order $order,
        UploadedFile $file,
        ?string $customerReference = null,
        ?string $customerNote = null,
        ?string $ipAddress = null
    ): PaymentProof {
        $bytes = @file_get_contents($file->getRealPath());
        if (! is_string($bytes) || $bytes === '') {
            throw new InvalidArgumentException('Uploaded payment proof file could not be read.');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $detectedMime = (string) $finfo->buffer($bytes);
        $imageInfo = @getimagesizefromstring($bytes);

        if (! array_key_exists($detectedMime, self::ALLOWED_MIME_MAP) || $imageInfo === false) {
            throw new InvalidArgumentException('Invalid proof file. Only genuine PNG, JPG, or WebP images up to 5MB are allowed.');
        }

        $safeExt = self::ALLOWED_MIME_MAP[$detectedMime];
        $relativePath = "payment-proofs/{$order->business_id}/{$order->id}/" . Str::uuid() . ".{$safeExt}";
        $encodedContent = base64_encode($bytes);

        Storage::disk('local')->put($relativePath, $bytes);

        return DB::transaction(function () use ($order, $file, $detectedMime, $relativePath, $encodedContent, $customerReference, $customerNote, $ipAddress): PaymentProof {
            $tx = PaymentTransaction::withoutGlobalScopes()->create([
                'business_id' => $order->business_id,
                'order_id' => $order->id,
                'gateway' => $order->payment_method ?: 'bank_transfer',
                'reference_number' => $customerReference ?: ('PROOF-' . gmdate('YmdHis') . '-' . strtoupper(Str::random(5))),
                'amount' => $order->currency === 'KHR' ? $order->total_khr : $order->total_amount,
                'currency' => $order->currency ?: 'USD',
                'status' => 'pending_verification',
                'idempotency_key' => 'proof:' . Str::uuid(),
                'notes' => $customerNote,
            ]);

            $proof = PaymentProof::withoutGlobalScopes()->create([
                'business_id' => $order->business_id,
                'order_id' => $order->id,
                'payment_transaction_id' => $tx->id,
                'payment_method' => $order->payment_method ?: 'bank_transfer',
                'file_path' => $relativePath,
                'original_filename' => Str::limit(basename((string) $file->getClientOriginalName()), 240),
                'mime_type' => $detectedMime,
                'file_size' => (int) $file->getSize(),
                'encoded_content' => $encodedContent,
                'customer_reference' => $customerReference ? trim($customerReference) : null,
                'customer_note' => $customerNote ? trim($customerNote) : null,
                'status' => 'pending',
                'uploaded_ip' => $ipAddress,
            ]);

            if ($order->payment_status !== 'paid') {
                $order->update([
                    'payment_status' => 'pending_verification',
                    'payment_reference' => $proof->customer_reference ?: $tx->reference_number,
                ]);
            }

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'from_status' => $order->status,
                'to_status' => $order->status,
                'note' => 'Customer uploaded payment proof; awaiting staff verification.',
                'created_at' => now(),
            ]);

            return $proof;
        });
    }

    /**
     * Approve manual bank transfer / QR payment after staff verification.
     */
    public function approveOrderPayment(Order $order, User $staff, ?string $reviewNote = null, ?string $ipAddress = null, ?string $userAgent = null): void
    {
        DB::transaction(function () use ($order, $staff, $reviewNote, $ipAddress, $userAgent): void {
            $lockedOrder = Order::withoutGlobalScopes()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            $now = now();

            $pendingProofs = PaymentProof::withoutGlobalScopes()
                ->where('order_id', $lockedOrder->id)
                ->where('status', 'pending');

            if ($pendingProofs->exists()) {
                $pendingProofs->update([
                    'status' => 'approved',
                    'reviewed_by' => $staff->id,
                    'reviewed_at' => $now,
                    'review_note' => $reviewNote,
                ]);
            } else {
                $latestProof = PaymentProof::withoutGlobalScopes()
                    ->where('order_id', $lockedOrder->id)
                    ->latest('created_at')
                    ->first();

                if ($latestProof && $latestProof->status !== 'approved') {
                    $latestProof->update([
                        'status' => 'approved',
                        'reviewed_by' => $staff->id,
                        'reviewed_at' => $now,
                        'review_note' => $reviewNote,
                    ]);
                }
            }

            PaymentTransaction::withoutGlobalScopes()
                ->where('order_id', $lockedOrder->id)
                ->whereIn('status', ['pending', 'pending_verification', 'rejected'])
                ->update([
                    'status' => 'paid',
                    'verified_at' => $now,
                    'verified_by' => $staff->id,
                ]);

            $lockedOrder->update([
                'payment_status' => 'paid',
                'payment_verified_at' => $now,
                'payment_verified_by' => $staff->id,
                'paid_at' => $now,
            ]);

            OrderStatusHistory::create([
                'order_id' => $lockedOrder->id,
                'user_id' => $staff->id,
                'from_status' => $lockedOrder->status,
                'to_status' => $lockedOrder->status,
                'note' => 'Payment proof approved by ' . $staff->name . ($reviewNote ? ": {$reviewNote}" : '.'),
                'created_at' => $now,
            ]);

            SecurityLog::create([
                'user_id' => $staff->id,
                'business_id' => $lockedOrder->business_id,
                'event_type' => 'payment_proof_approved',
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'metadata' => [
                    'order_id' => $lockedOrder->id,
                    'order_number' => $lockedOrder->order_number,
                    'amount' => (float) $lockedOrder->total_amount,
                ],
            ]);
        });

        app(BusinessNotificationService::class)->notifyOrderEvent($order->fresh(), 'order.payment_received');
    }

    /**
     * Reject a manual payment proof and keep the order unpaid/rejected.
     */
    public function rejectOrderPayment(Order $order, User $staff, string $reason, ?string $ipAddress = null, ?string $userAgent = null): void
    {
        DB::transaction(function () use ($order, $staff, $reason, $ipAddress, $userAgent): void {
            $lockedOrder = Order::withoutGlobalScopes()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            $now = now();

            PaymentProof::withoutGlobalScopes()
                ->where('order_id', $lockedOrder->id)
                ->where('status', 'pending')
                ->update([
                    'status' => 'rejected',
                    'reviewed_by' => $staff->id,
                    'reviewed_at' => $now,
                    'review_note' => $reason,
                ]);

            PaymentTransaction::withoutGlobalScopes()
                ->where('order_id', $lockedOrder->id)
                ->whereIn('status', ['pending', 'pending_verification'])
                ->update([
                    'status' => 'rejected',
                    'verified_at' => $now,
                    'verified_by' => $staff->id,
                    'notes' => $reason,
                ]);

            $lockedOrder->update([
                'payment_status' => 'rejected',
                'payment_verified_at' => $now,
                'payment_verified_by' => $staff->id,
            ]);

            OrderStatusHistory::create([
                'order_id' => $lockedOrder->id,
                'user_id' => $staff->id,
                'from_status' => $lockedOrder->status,
                'to_status' => $lockedOrder->status,
                'note' => 'Payment rejected by ' . $staff->name . ": {$reason}",
                'created_at' => $now,
            ]);

            SecurityLog::create([
                'user_id' => $staff->id,
                'business_id' => $lockedOrder->business_id,
                'event_type' => 'payment_proof_rejected',
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'metadata' => [
                    'order_id' => $lockedOrder->id,
                    'order_number' => $lockedOrder->order_number,
                    'reason' => $reason,
                ],
            ]);
        });
    }

    /**
     * Mark a Cash on Delivery order as collected with collector audit trail.
     */
    public function collectCodPayment(Order $order, User $staff, ?string $note = null, ?string $ipAddress = null, ?string $userAgent = null): void
    {
        DB::transaction(function () use ($order, $staff, $note, $ipAddress, $userAgent): void {
            $lockedOrder = Order::withoutGlobalScopes()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            $now = now();

            PaymentTransaction::withoutGlobalScopes()
                ->where('order_id', $lockedOrder->id)
                ->whereIn('status', ['pending', 'pending_collection'])
                ->update([
                    'status' => 'paid',
                    'verified_at' => $now,
                    'verified_by' => $staff->id,
                ]);

            $lockedOrder->update([
                'payment_status' => 'paid',
                'payment_collected_at' => $now,
                'payment_collected_by' => $staff->id,
                'paid_at' => $now,
            ]);

            OrderStatusHistory::create([
                'order_id' => $lockedOrder->id,
                'user_id' => $staff->id,
                'from_status' => $lockedOrder->status,
                'to_status' => $lockedOrder->status,
                'note' => 'COD cash collected by ' . $staff->name . ($note ? ": {$note}" : '.'),
                'created_at' => $now,
            ]);

            SecurityLog::create([
                'user_id' => $staff->id,
                'business_id' => $lockedOrder->business_id,
                'event_type' => 'cod_payment_collected',
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'metadata' => [
                    'order_id' => $lockedOrder->id,
                    'order_number' => $lockedOrder->order_number,
                    'amount' => (float) $lockedOrder->total_amount,
                ],
            ]);
        });

        app(BusinessNotificationService::class)->notifyOrderEvent($order->fresh(), 'order.payment_received');
    }
}
