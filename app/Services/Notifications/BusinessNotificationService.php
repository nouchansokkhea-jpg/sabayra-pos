<?php

namespace App\Services\Notifications;

use App\Jobs\SendTelegramBusinessNotificationJob;
use App\Models\Business;
use App\Models\BusinessBranch;
use App\Models\BusinessMember;
use App\Models\CustomerReview;
use App\Models\InventoryItem;
use App\Models\NotificationPreference;
use App\Models\Order;
use App\Models\Reservation;
use App\Models\RestaurantTable;
use App\Models\TelegramConnection;
use App\Models\User;
use App\Notifications\BusinessEventNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class BusinessNotificationService
{
    /**
     * Dispatch a business notification across configured and enabled channels.
     */
    public function dispatch(
        Business $business,
        string $eventKey,
        string $title,
        string $summary,
        ?string $telegramBody = null,
        ?string $branchId = null,
        ?string $actionUrl = null,
        ?string $dedupeKey = null,
        int $dedupeTtlSeconds = 300,
    ): void {
        if (! array_key_exists($eventKey, NotificationPreference::EVENT_CATALOG)) {
            return;
        }

        if ($business->status !== 'active' && $eventKey !== 'business.account_alert') {
            return;
        }

        if ($dedupeKey !== null && $dedupeKey !== '') {
            $cacheKey = "sabayra_notif_dedupe:{$business->id}:{$eventKey}:" . sha1($dedupeKey);

            if (! Cache::add($cacheKey, true, now()->addSeconds($dedupeTtlSeconds))) {
                return;
            }
        }

        $branchName = $this->resolveBranchName($business->id, $branchId);
        $resolvedBusinessPrefs = NotificationPreference::resolveForBusiness($business->id);
        $businessPref = $resolvedBusinessPrefs[$eventKey];

        // Respect business-level branch filter if configured
        if ($businessPref['branch_id'] !== null && $branchId !== null && $businessPref['branch_id'] !== $branchId) {
            return;
        }

        // 1. Dispatch queued Telegram notification if connected and enabled
        if ($businessPref['telegram_enabled'] && Schema::hasTable('telegram_connections')) {
            $connection = TelegramConnection::withoutGlobalScopes()
                ->where('business_id', $business->id)
                ->first();

            if ($connection && $connection->is_active && $connection->notifications_enabled) {
                $body = $telegramBody ?? $this->formatTelegramMessage($business, $title, $summary, $branchName);

                if ($this->shouldRunImmediatelyInLocalWeb()) {
                    SendTelegramBusinessNotificationJob::dispatchSync(
                        businessId: $business->id,
                        eventType: $eventKey,
                        message: $body,
                        preview: Str::limit($summary, 180),
                        branchId: $branchId,
                    );
                } else {
                    SendTelegramBusinessNotificationJob::dispatch(
                        businessId: $business->id,
                        eventType: $eventKey,
                        message: $body,
                        preview: Str::limit($summary, 180),
                        branchId: $branchId,
                    );
                }
            }
        }

        // 2. Dispatch queued In-App (database) and Email notifications to eligible business recipients
        if (! Schema::hasTable('notifications')) {
            return;
        }

        $recipients = $this->resolveRecipients($business, $eventKey, $branchId);
        $dispatchNow = $this->shouldRunImmediatelyInLocalWeb();

        foreach ($recipients as $recipient) {
            $userPrefs = NotificationPreference::resolveForBusiness($business->id, $recipient->id);
            $pref = $userPrefs[$eventKey] ?? $businessPref;

            if ($pref['branch_id'] !== null && $branchId !== null && $pref['branch_id'] !== $branchId) {
                continue;
            }

            $sendInApp = (bool) $pref['in_app_enabled'];
            $sendEmail = (bool) $pref['email_enabled'] && ! empty($recipient->email);

            if (! $sendInApp && ! $sendEmail) {
                continue;
            }

            $notification = new BusinessEventNotification(
                businessId: $business->id,
                businessName: $business->name,
                eventKey: $eventKey,
                title: $title,
                message: $summary,
                actionUrl: $actionUrl,
                branchId: $branchId,
                branchName: $branchName,
                sendEmail: $sendEmail,
                sendDatabase: $sendInApp,
            );

            if ($dispatchNow) {
                Notification::sendNow($recipient, $notification);
            } else {
                Notification::send($recipient, $notification);
            }
        }
    }

    /**
     * When running local PHP serve after changing .env to QUEUE_CONNECTION=sync, honor sync execution immediately.
     */
    private function shouldRunImmediatelyInLocalWeb(): bool
    {
        if (! app()->environment('local') || app()->runningUnitTests() || app()->runningInConsole()) {
            return false;
        }

        if (config('queue.default') === 'sync') {
            return true;
        }

        $envPath = base_path('.env');
        if (is_file($envPath)) {
            $envContents = @file_get_contents($envPath);
            if (is_string($envContents) && preg_match('/^QUEUE_CONNECTION\s*=\s*sync\b/m', $envContents) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * Dispatch an order lifecycle or payment event without exposing sensitive customer PII.
     */
    public function notifyOrderEvent(Order $order, string $eventKey, ?string $extraNote = null): void
    {
        try {
            $order->loadMissing(['business', 'table', 'branch']);
            $business = $order->business;

            if (! $business) {
                return;
            }

            $orderTypeLabel = ucwords(str_replace('_', ' ', (string) ($order->order_type ?? 'order')));
            $totalFormatted = $business->formatUsd((float) $order->total_amount);
            $tablePart = $this->formatTableLabel($order);

            [$title, $summary] = match ($eventKey) {
                'order.created' => [
                    "New Order #{$order->order_number}",
                    "New {$orderTypeLabel} order #{$order->order_number} ({$totalFormatted}){$tablePart}.",
                ],
                'order.confirmed' => [
                    "Order Confirmed #{$order->order_number}",
                    "Order #{$order->order_number} ({$orderTypeLabel}, {$totalFormatted}) has been confirmed.",
                ],
                'order.cancelled' => [
                    "Order Cancelled #{$order->order_number}",
                    "Order #{$order->order_number} ({$orderTypeLabel}, {$totalFormatted}) was cancelled.",
                ],
                'order.completed' => [
                    "Order Completed #{$order->order_number}",
                    "Order #{$order->order_number} ({$orderTypeLabel}, {$totalFormatted}) is now completed.",
                ],
                'order.payment_received' => [
                    "Payment Received #{$order->order_number}",
                    "Payment confirmed for order #{$order->order_number} ({$totalFormatted} via " . strtoupper((string) ($order->payment_method ?? 'CASH')) . ').',
                ],
                default => [
                    "Order Update #{$order->order_number}",
                    "Order #{$order->order_number} updated to {$order->status}.",
                ],
            };

            if (! empty($extraNote)) {
                $summary .= ' ' . Str::limit(strip_tags($extraNote), 100);
            }

            $this->dispatch(
                business: $business,
                eventKey: $eventKey,
                title: $title,
                summary: $summary,
                branchId: $order->branch_id,
                actionUrl: url('/app/orders'),
                dedupeKey: "order:{$order->id}:{$eventKey}:{$order->status}:{$order->payment_status}",
            );
        } catch (\Throwable $e) {
            Log::warning("Order notification dispatch skipped: {$e->getMessage()}");
        }
    }

    /**
     * Dispatch a reservation lifecycle event without exposing customer contact PII.
     */
    public function notifyReservationEvent(Reservation $reservation, string $eventKey): void
    {
        try {
            $reservation->loadMissing(['business', 'table', 'branch']);
            $business = $reservation->business;

            if (! $business) {
                return;
            }

            $dateLabel = $reservation->reservation_date
                ? $reservation->reservation_date->format('Y-m-d')
                : 'TBD';
            $timeLabel = (string) ($reservation->reservation_time ?? 'TBD');
            $partySize = (int) ($reservation->party_size ?? 1);
            $tablePart = $this->formatTableLabel($reservation);

            [$title, $summary] = match ($eventKey) {
                'reservation.created' => [
                    'New Table Reservation',
                    "New reservation for party of {$partySize} on {$dateLabel} at {$timeLabel}{$tablePart}.",
                ],
                'reservation.confirmed' => [
                    'Reservation Confirmed',
                    "Reservation for party of {$partySize} on {$dateLabel} at {$timeLabel} is confirmed{$tablePart}.",
                ],
                'reservation.cancelled' => [
                    'Reservation Cancelled',
                    "Reservation for party of {$partySize} on {$dateLabel} at {$timeLabel} was cancelled.",
                ],
                default => [
                    'Reservation Update',
                    "Reservation for party of {$partySize} on {$dateLabel} updated to {$reservation->status}.",
                ],
            };

            $this->dispatch(
                business: $business,
                eventKey: $eventKey,
                title: $title,
                summary: $summary,
                branchId: $reservation->branch_id,
                actionUrl: url('/app/reservations'),
                dedupeKey: "reservation:{$reservation->id}:{$eventKey}:{$reservation->status}",
            );
        } catch (\Throwable $e) {
            Log::warning("Reservation notification dispatch skipped: {$e->getMessage()}");
        }
    }

    /**
     * Dispatch review notifications (and low-rating alert when <= 3 stars).
     */
    public function notifyReviewEvent(CustomerReview $review): void
    {
        try {
            $review->loadMissing(['business', 'product', 'order']);
            $business = $review->business;

            if (! $business) {
                return;
            }

            $rating = (int) $review->rating;
            $itemPart = $review->product ? " on {$review->product->name_en}" : '';
            $excerpt = ! empty($review->comment)
                ? ' "' . Str::limit(strip_tags((string) $review->comment), 80) . '"'
                : '';

            $branchId = $review->order?->branch_id;

            $this->dispatch(
                business: $business,
                eventKey: 'review.created',
                title: "New {$rating}/5 Customer Review",
                summary: "Received a {$rating}/5 rating{$itemPart}.{$excerpt}",
                branchId: $branchId,
                actionUrl: url('/app/reviews'),
                dedupeKey: "review:{$review->id}:created",
            );

            if ($rating <= 3) {
                $this->dispatch(
                    business: $business,
                    eventKey: 'review.low_rating',
                    title: "Low Rating Alert ({$rating}/5 Stars)",
                    summary: "A customer left a {$rating}/5 rating{$itemPart}.{$excerpt}",
                    branchId: $branchId,
                    actionUrl: url('/app/reviews'),
                    dedupeKey: "review:{$review->id}:low_rating",
                );
            }
        } catch (\Throwable $e) {
            Log::warning("Review notification dispatch skipped: {$e->getMessage()}");
        }
    }

    /**
     * Dispatch inventory low-stock or out-of-stock notifications only on threshold crossings.
     */
    public function notifyInventoryStockTransition(InventoryItem $item, float $previousStock, float $newStock, ?string $branchId = null): void
    {
        try {
            $business = $item->business ?? Business::find($item->business_id);

            if (! $business) {
                return;
            }

            $threshold = (float) $item->low_stock_threshold;
            $unit = $item->unit ?: 'units';
            $formattedStock = rtrim(rtrim(number_format($newStock, 2, '.', ''), '0'), '.');
            $formattedThreshold = rtrim(rtrim(number_format($threshold, 2, '.', ''), '0'), '.');

            if ($previousStock > 0 && $newStock <= 0) {
                $this->dispatch(
                    business: $business,
                    eventKey: 'inventory.out_of_stock',
                    title: "Out of Stock: {$item->name_en}",
                    summary: "{$item->name_en} is now out of stock ({$formattedStock} {$unit}). Restock immediately.",
                    branchId: $branchId,
                    actionUrl: url('/app/inventory'),
                    dedupeKey: "inventory:{$item->id}:out_of_stock",
                    dedupeTtlSeconds: 900,
                );
            } elseif ($threshold > 0 && $previousStock > $threshold && $newStock <= $threshold && $newStock > 0) {
                $this->dispatch(
                    business: $business,
                    eventKey: 'inventory.low_stock',
                    title: "Low Stock: {$item->name_en}",
                    summary: "{$item->name_en} fell to {$formattedStock} {$unit} (threshold: {$formattedThreshold} {$unit}).",
                    branchId: $branchId,
                    actionUrl: url('/app/inventory'),
                    dedupeKey: "inventory:{$item->id}:low_stock",
                    dedupeTtlSeconds: 900,
                );
            }
        } catch (\Throwable $e) {
            Log::warning("Inventory notification dispatch skipped: {$e->getMessage()}");
        }
    }

    /**
     * Dispatch automated daily sales summary notification.
     *
     * @param  array{date: string, orders_count: int, completed_orders_count: int, cancelled_orders_count: int, gross_revenue_usd: float}  $stats
     */
    public function notifyDailySalesSummary(Business $business, array $stats): void
    {
        $date = $stats['date'];
        $revenue = $business->formatUsd((float) $stats['gross_revenue_usd']);
        $orders = (int) $stats['orders_count'];
        $completed = (int) $stats['completed_orders_count'];
        $cancelled = (int) $stats['cancelled_orders_count'];

        $summary = "Daily summary for {$date}: {$orders} total orders ({$completed} completed, {$cancelled} cancelled), revenue {$revenue}.";

        $telegram = "<b>{$business->name} — Daily Sales Summary ({$date})</b>\n"
            . "Orders: {$orders} ({$completed} completed, {$cancelled} cancelled)\n"
            . "Revenue: {$revenue}";

        $this->dispatch(
            business: $business,
            eventKey: 'business.daily_summary',
            title: "Daily Sales Summary ({$date})",
            summary: $summary,
            telegramBody: $telegram,
            actionUrl: url('/app'),
            dedupeKey: "daily_summary:{$business->id}:{$date}",
            dedupeTtlSeconds: 43200,
        );
    }

    /**
     * Dispatch critical account, status, or subscription alert.
     */
    public function notifyAccountAlert(Business $business, string $title, string $message, ?string $dedupeKey = null): void
    {
        try {
            $this->dispatch(
                business: $business,
                eventKey: 'business.account_alert',
                title: $title,
                summary: $message,
                actionUrl: url('/app/settings?section=account'),
                dedupeKey: $dedupeKey,
                dedupeTtlSeconds: 600,
            );
        } catch (\Throwable $e) {
            Log::warning("Account notification dispatch skipped: {$e->getMessage()}");
        }
    }

    /**
     * Resolve authorized in-app/email user recipients for the given business, event, and branch.
     *
     * @return Collection<int, User>
     */
    private function resolveRecipients(Business $business, string $eventKey, ?string $branchId): Collection
    {
        $recipients = collect();

        $owner = $business->owner;
        if ($owner instanceof User) {
            $recipients->push($owner);
        }

        $members = BusinessMember::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->where('is_active', true)
            ->with('user')
            ->get();

        $group = NotificationPreference::EVENT_CATALOG[$eventKey]['group'] ?? 'order';

        $allowedRoles = match ($group) {
            'business', 'review' => ['owner', 'manager'],
            'inventory' => ['owner', 'manager', 'inventory'],
            'order', 'reservation' => ['owner', 'manager', 'cashier', 'waiter', 'kitchen', 'staff'],
            default => ['owner', 'manager'],
        };

        foreach ($members as $member) {
            $memberUser = $member->user;
            if (! $memberUser instanceof User) {
                continue;
            }

            $memberRole = (string) ($member->role ?? 'staff');
            $memberBranchId = $member->branch_id;

            if (! in_array($memberRole, $allowedRoles, true)) {
                continue;
            }

            if ($branchId !== null && $memberBranchId !== null && $memberBranchId !== $branchId) {
                continue;
            }

            $recipients->push($memberUser);
        }

        return $recipients->unique('id')->values();
    }

    private function resolveBranchName(string $businessId, ?string $branchId): ?string
    {
        if (empty($branchId)) {
            return null;
        }

        $branch = BusinessBranch::where('business_id', $businessId)
            ->where('id', $branchId)
            ->first(['id', 'name']);

        return $branch?->name;
    }

    private function formatTableLabel(Order|Reservation $record): string
    {
        $table = $record->getRelationValue('table');

        if ($table instanceof RestaurantTable) {
            $label = $table->name ?: $table->table_number;

            return ! empty($label) ? " | Table: {$label}" : '';
        }

        return '';
    }

    private function formatTelegramMessage(Business $business, string $title, string $summary, ?string $branchName = null): string
    {
        $safeBusiness = e($business->name);
        $safeTitle = e($title);
        $safeSummary = e($summary);

        $lines = [
            "<b>{$safeTitle}</b>",
            "Business: {$safeBusiness}",
        ];

        if (! empty($branchName)) {
            $lines[] = 'Branch: ' . e($branchName);
        }

        $lines[] = '';
        $lines[] = $safeSummary;

        return implode("\n", $lines);
    }
}
