<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\SubscriptionPayment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    /**
     * Display a listing of platform subscription payments & merchant transaction summaries.
     */
    public function index(Request $request): View
    {
        $query = SubscriptionPayment::withoutGlobalScopes()->with(['subscription.plan', 'business'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('transaction_reference', 'ilike', "%{$search}%")
                  ->orWhereHas('business', function ($b) use ($search) {
                      $b->where('name', 'ilike', "%{$search}%");
                  });
            });
        }

        $payments = $query->paginate(15)->withQueryString();

        $totalSaaSPaid = (float) SubscriptionPayment::withoutGlobalScopes()->where('status', 'completed')->sum('amount');
        $totalSaaSPending = (float) SubscriptionPayment::withoutGlobalScopes()->where('status', 'pending')->sum('amount');
        $totalMerchantGMV = (float) Order::withoutGlobalScopes()->where('payment_status', 'paid')->sum('total_amount');
        $totalMerchantGMVKhr = (float) Order::withoutGlobalScopes()->where('payment_status', 'paid')->sum('total_khr');

        return view('admin.payments.index', compact(
            'payments',
            'totalSaaSPaid',
            'totalSaaSPending',
            'totalMerchantGMV',
            'totalMerchantGMVKhr'
        ));
    }

    /**
     * Verify or update payment status.
     */
    public function updateStatus(Request $request, SubscriptionPayment $payment): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:completed,pending,failed,refunded'],
        ]);

        $payment->update([
            'status' => $validated['status'],
            'paid_at' => $validated['status'] === 'completed' ? ($payment->paid_at ?? now()) : null,
        ]);

        if ($validated['status'] === 'completed' && $payment->subscription) {
            $payment->subscription->update(['status' => 'active']);
        }

        return back()->with('success', "Payment #{$payment->transaction_reference} marked as {$validated['status']}.");
    }
}
