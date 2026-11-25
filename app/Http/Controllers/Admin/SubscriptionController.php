<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    /**
     * Display a listing of subscriptions.
     */
    public function index(Request $request): View
    {
        $query = Subscription::withoutGlobalScopes()->with(['business', 'plan'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('billing_cycle')) {
            $query->where('billing_cycle', $request->billing_cycle);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('business', function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('slug', 'ilike', "%{$search}%");
            });
        }

        $subscriptions = $query->paginate(15)->withQueryString();

        $stats = [
            'total' => Subscription::withoutGlobalScopes()->count(),
            'active' => Subscription::withoutGlobalScopes()->where('status', 'active')->count(),
            'expired' => Subscription::withoutGlobalScopes()->where('status', 'expired')->count(),
            'cancelled' => Subscription::withoutGlobalScopes()->where('status', 'cancelled')->count(),
        ];

        $plans = Plan::where('is_active', true)->orderBy('sort_order')->get();

        return view('admin.subscriptions.index', compact('subscriptions', 'stats', 'plans'));
    }

    /**
     * Update subscription status or extend validity.
     */
    public function updateStatus(Request $request, Subscription $subscription): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:active,expired,cancelled,pending'],
            'extend_days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ]);

        $updateData = ['status' => $validated['status']];

        if (!empty($validated['extend_days'])) {
            $currentExpires = $subscription->expires_at && $subscription->expires_at->isFuture()
                ? $subscription->expires_at
                : now();
            $updateData['expires_at'] = $currentExpires->addDays((int)$validated['extend_days']);
            $updateData['status'] = 'active';
        }

        if ($validated['status'] === 'cancelled') {
            $updateData['cancelled_at'] = now();
        }

        $subscription->update($updateData);

        if ($subscription->business) {
            $expiresNote = $subscription->expires_at ? ' (expires ' . $subscription->expires_at->format('Y-m-d') . ')' : '';
            app(\App\Services\Notifications\BusinessNotificationService::class)->notifyAccountAlert(
                $subscription->business,
                'Subscription Status Updated',
                "Your business subscription status is now {$subscription->status}{$expiresNote}.",
                "sub_status:{$subscription->id}:{$subscription->status}:" . ($subscription->expires_at?->timestamp ?? 0),
            );
        }

        return back()->with('success', "Subscription for '{$subscription->business->name}' updated successfully.");
    }
}
