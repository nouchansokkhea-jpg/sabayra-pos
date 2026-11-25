<?php

namespace App\Jobs;

use App\Models\Business;
use App\Models\NotificationPreference;
use App\Models\TelegramConnection;
use App\Services\Telegram\TelegramBotService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendTelegramBusinessNotificationJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new queued job instance.
     */
    public function __construct(
        public string $businessId,
        public string $eventType,
        public string $message,
        public ?string $preview = null,
        public ?string $branchId = null,
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(TelegramBotService $botService): void
    {
        $business = Business::find($this->businessId);

        if (! $business) {
            return;
        }

        if ($business->status !== 'active' && $this->eventType !== 'business.account_alert') {
            return;
        }

        $connection = TelegramConnection::withoutGlobalScopes()
            ->where('business_id', $this->businessId)
            ->first();

        if (! $connection || ! $connection->is_active || ! $connection->notifications_enabled) {
            return;
        }

        $resolved = NotificationPreference::resolveForBusiness($this->businessId);
        $eventPref = $resolved[$this->eventType] ?? null;

        if ($eventPref !== null) {
            if (! $eventPref['telegram_enabled']) {
                return;
            }

            if ($eventPref['branch_id'] !== null && $this->branchId !== null && $eventPref['branch_id'] !== $this->branchId) {
                return;
            }
        }

        $botService->deliverForConnection(
            connection: $connection,
            eventType: $this->eventType,
            message: $this->message,
            preview: $this->preview,
        );
    }
}
