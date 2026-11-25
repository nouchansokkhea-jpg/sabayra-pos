<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model
{
    use BelongsToTenant, HasUuids;

    /**
     * Catalog of all supported business notification events and their defaults.
     *
     * @var array<string, array{
     *     group: string,
     *     group_label: string,
     *     label: string,
     *     description: string,
     *     defaults: array{telegram: bool, email: bool, in_app: bool, browser: bool}
     * }>
     */
    public const EVENT_CATALOG = [
        'order.created' => [
            'group' => 'order',
            'group_label' => 'Order Notifications',
            'label' => 'New order received',
            'description' => 'Alert when a customer or cashier submits a new order.',
            'defaults' => ['telegram' => true, 'email' => false, 'in_app' => true, 'browser' => true],
        ],
        'order.confirmed' => [
            'group' => 'order',
            'group_label' => 'Order Notifications',
            'label' => 'Order confirmed',
            'description' => 'Alert when a pending order is confirmed for preparation.',
            'defaults' => ['telegram' => true, 'email' => false, 'in_app' => true, 'browser' => false],
        ],
        'order.cancelled' => [
            'group' => 'order',
            'group_label' => 'Order Notifications',
            'label' => 'Order cancelled',
            'description' => 'Alert when an order is cancelled or voided.',
            'defaults' => ['telegram' => true, 'email' => false, 'in_app' => true, 'browser' => false],
        ],
        'order.completed' => [
            'group' => 'order',
            'group_label' => 'Order Notifications',
            'label' => 'Order completed',
            'description' => 'Alert when an order is marked completed or served.',
            'defaults' => ['telegram' => false, 'email' => false, 'in_app' => true, 'browser' => false],
        ],
        'order.payment_received' => [
            'group' => 'order',
            'group_label' => 'Order Notifications',
            'label' => 'Payment received',
            'description' => 'Alert when an order payment is confirmed as paid.',
            'defaults' => ['telegram' => true, 'email' => false, 'in_app' => true, 'browser' => false],
        ],
        'reservation.created' => [
            'group' => 'reservation',
            'group_label' => 'Reservation Notifications',
            'label' => 'New reservation',
            'description' => 'Alert when a new table booking is created.',
            'defaults' => ['telegram' => true, 'email' => false, 'in_app' => true, 'browser' => false],
        ],
        'reservation.confirmed' => [
            'group' => 'reservation',
            'group_label' => 'Reservation Notifications',
            'label' => 'Reservation confirmed',
            'description' => 'Alert when a reservation status is set to confirmed.',
            'defaults' => ['telegram' => true, 'email' => false, 'in_app' => true, 'browser' => false],
        ],
        'reservation.cancelled' => [
            'group' => 'reservation',
            'group_label' => 'Reservation Notifications',
            'label' => 'Reservation cancelled',
            'description' => 'Alert when a reservation is cancelled.',
            'defaults' => ['telegram' => true, 'email' => false, 'in_app' => true, 'browser' => false],
        ],
        'review.created' => [
            'group' => 'review',
            'group_label' => 'Review Notifications',
            'label' => 'New customer review',
            'description' => 'Alert whenever a customer submits a new rating or review.',
            'defaults' => ['telegram' => true, 'email' => false, 'in_app' => true, 'browser' => false],
        ],
        'review.low_rating' => [
            'group' => 'review',
            'group_label' => 'Review Notifications',
            'label' => 'New low-rating review',
            'description' => 'Urgent alert when a review is rated 3 stars or lower.',
            'defaults' => ['telegram' => true, 'email' => true, 'in_app' => true, 'browser' => true],
        ],
        'inventory.low_stock' => [
            'group' => 'inventory',
            'group_label' => 'Inventory Notifications',
            'label' => 'Low stock',
            'description' => 'Alert when an inventory ingredient drops to or below its threshold.',
            'defaults' => ['telegram' => true, 'email' => false, 'in_app' => true, 'browser' => false],
        ],
        'inventory.out_of_stock' => [
            'group' => 'inventory',
            'group_label' => 'Inventory Notifications',
            'label' => 'Out of stock',
            'description' => 'Urgent alert when an inventory item reaches zero stock.',
            'defaults' => ['telegram' => true, 'email' => true, 'in_app' => true, 'browser' => true],
        ],
        'business.daily_summary' => [
            'group' => 'business',
            'group_label' => 'Business Notifications',
            'label' => 'Daily sales summary',
            'description' => 'Automated daily digest of order volume and revenue.',
            'defaults' => ['telegram' => true, 'email' => true, 'in_app' => true, 'browser' => false],
        ],
        'business.account_alert' => [
            'group' => 'business',
            'group_label' => 'Business Notifications',
            'label' => 'Important account & subscription alerts',
            'description' => 'Critical alerts for subscription status, billing approvals, and account changes.',
            'defaults' => ['telegram' => true, 'email' => true, 'in_app' => true, 'browser' => true],
        ],
    ];

    protected $fillable = [
        'business_id',
        'user_id',
        'branch_id',
        'event_key',
        'telegram_enabled',
        'email_enabled',
        'in_app_enabled',
        'browser_enabled',
    ];

    protected function casts(): array
    {
        return [
            'telegram_enabled' => 'boolean',
            'email_enabled' => 'boolean',
            'in_app_enabled' => 'boolean',
            'browser_enabled' => 'boolean',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(BusinessBranch::class, 'branch_id');
    }

    /**
     * Return the event catalog grouped by category key while preserving order.
     *
     * @return array<string, array{
     *     label: string,
     *     events: array<string, array{
     *         group: string,
     *         group_label: string,
     *         label: string,
     *         description: string,
     *         defaults: array{telegram: bool, email: bool, in_app: bool, browser: bool}
     *     }>
     * }>
     */
    public static function catalogByGroup(): array
    {
        $grouped = [];

        foreach (self::EVENT_CATALOG as $eventKey => $meta) {
            $groupKey = $meta['group'];

            if (! isset($grouped[$groupKey])) {
                $grouped[$groupKey] = [
                    'label' => $meta['group_label'],
                    'events' => [],
                ];
            }

            $grouped[$groupKey]['events'][$eventKey] = $meta;
        }

        return $grouped;
    }

    /**
     * Resolve effective channel booleans for every catalog event in a business context.
     *
     * @return array<string, array{
     *     branch_id: ?string,
     *     telegram_enabled: bool,
     *     email_enabled: bool,
     *     in_app_enabled: bool,
     *     browser_enabled: bool
     * }>
     */
    public static function resolveForBusiness(string $businessId, ?int $userId = null): array
    {
        $rows = self::withoutGlobalScopes()
            ->where('business_id', $businessId)
            ->where(function ($query) use ($userId): void {
                $query->whereNull('user_id');

                if ($userId !== null) {
                    $query->orWhere('user_id', $userId);
                }
            })
            ->get();

        $businessRows = $rows->whereNull('user_id')->keyBy('event_key');
        $userRows = $userId !== null
            ? $rows->where('user_id', $userId)->keyBy('event_key')
            : collect();

        $resolved = [];

        foreach (self::EVENT_CATALOG as $eventKey => $meta) {
            $record = $userRows->get($eventKey) ?? $businessRows->get($eventKey);

            if ($record instanceof self) {
                $resolved[$eventKey] = [
                    'branch_id' => $record->branch_id,
                    'telegram_enabled' => (bool) $record->telegram_enabled,
                    'email_enabled' => (bool) $record->email_enabled,
                    'in_app_enabled' => (bool) $record->in_app_enabled,
                    'browser_enabled' => (bool) $record->browser_enabled,
                ];
            } else {
                $resolved[$eventKey] = [
                    'branch_id' => null,
                    'telegram_enabled' => (bool) $meta['defaults']['telegram'],
                    'email_enabled' => (bool) $meta['defaults']['email'],
                    'in_app_enabled' => (bool) $meta['defaults']['in_app'],
                    'browser_enabled' => (bool) $meta['defaults']['browser'],
                ];
            }
        }

        return $resolved;
    }
}
