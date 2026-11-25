<?php

namespace App\Console\Commands;

use App\Models\Business;
use App\Models\Order;
use App\Services\Notifications\BusinessNotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendDailySalesSummariesCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'sabayra:send-daily-sales-summaries
        {--date= : Target summary date in Y-m-d format (defaults to today)}
        {--business= : Optional single Business UUID to process}';

    /**
     * The console command description.
     */
    protected $description = 'Dispatch queued daily sales summary notifications across active SABAYRA businesses';

    /**
     * Execute the console command.
     */
    public function handle(BusinessNotificationService $notificationService): int
    {
        $targetDate = $this->option('date')
            ? Carbon::parse((string) $this->option('date'))->toDateString()
            : now()->toDateString();

        $startOfDay = Carbon::parse($targetDate)->startOfDay();
        $endOfDay = Carbon::parse($targetDate)->endOfDay();

        $query = Business::query()->where('status', 'active');

        if ($businessId = $this->option('business')) {
            $query->where('id', (string) $businessId);
        }

        $dispatched = 0;

        $query->orderBy('id')->chunk(50, function ($businesses) use ($startOfDay, $endOfDay, $targetDate, $notificationService, &$dispatched): void {
            foreach ($businesses as $business) {
                $orders = Order::withoutGlobalScopes()
                    ->where('business_id', $business->id)
                    ->whereBetween('created_at', [$startOfDay, $endOfDay])
                    ->get(['id', 'status', 'payment_status', 'total_amount']);

                $ordersCount = $orders->count();
                $completedCount = $orders->where('status', 'completed')->count();
                $cancelledCount = $orders->where('status', 'cancelled')->count();

                $grossRevenueUsd = (float) $orders
                    ->filter(fn (Order $order): bool => $order->status !== 'cancelled' && ($order->payment_status === 'paid' || $order->status === 'completed'))
                    ->sum('total_amount');

                $notificationService->notifyDailySalesSummary($business, [
                    'date' => $targetDate,
                    'orders_count' => $ordersCount,
                    'completed_orders_count' => $completedCount,
                    'cancelled_orders_count' => $cancelledCount,
                    'gross_revenue_usd' => round($grossRevenueUsd, 2),
                ]);

                $dispatched++;
            }
        });

        $this->info("Daily sales summaries dispatched for {$dispatched} active business(es) [{$targetDate}].");

        return self::SUCCESS;
    }
}
