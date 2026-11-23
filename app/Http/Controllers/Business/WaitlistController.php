<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\BusinessBranch;
use App\Models\CustomerWaitlist;
use App\Services\Tenant\TenantContext;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WaitlistController extends Controller
{
    /**
     * Display live customer waitlist queue.
     */
    public function index(): View
    {
        $business = TenantContext::get();

        $activeWaitlist = CustomerWaitlist::where('business_id', $business->id)
            ->whereDate('created_at', Carbon::today())
            ->whereIn('status', ['waiting', 'notified'])
            ->orderBy('created_at')
            ->get();

        $completedToday = CustomerWaitlist::where('business_id', $business->id)
            ->whereDate('created_at', Carbon::today())
            ->whereIn('status', ['seated', 'cancelled'])
            ->latest()
            ->take(10)
            ->get();

        $branches = BusinessBranch::where('business_id', $business->id)->get();

        return view('business.waitlist.index', compact('business', 'activeWaitlist', 'completedToday', 'branches'));
    }

    /**
     * Add a party to the live waitlist queue.
     */
    public function store(Request $request): RedirectResponse
    {
        $business = TenantContext::get();

        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:50',
            'party_size' => 'required|integer|min:1|max:50',
            'estimated_wait_minutes' => 'nullable|integer|min:1|max:180',
            'branch_id' => 'nullable|uuid|exists:business_branches,id',
            'notes' => 'nullable|string|max:500',
        ]);

        $todayCount = CustomerWaitlist::where('business_id', $business->id)
            ->whereDate('created_at', Carbon::today())
            ->count();

        $validated['business_id'] = $business->id;
        $validated['queue_number'] = 'Q-' . str_pad($todayCount + 1, 3, '0', STR_PAD_LEFT);
        $validated['status'] = 'waiting';
        $validated['estimated_wait_minutes'] = $validated['estimated_wait_minutes'] ?? 15;

        CustomerWaitlist::create($validated);

        return back()->with('status', "Party added to waitlist with queue ticket {$validated['queue_number']}!");
    }

    /**
     * Update waitlist queue status (notified, seated, cancelled).
     */
    public function updateStatus(Request $request, CustomerWaitlist $waitlist): RedirectResponse
    {
        $business = TenantContext::get();
        abort_if($waitlist->business_id !== $business->id, 403);

        $validated = $request->validate([
            'status' => 'required|in:waiting,notified,seated,cancelled',
        ]);

        $waitlist->update(['status' => $validated['status']]);

        return back()->with('status', "Queue ticket {$waitlist->queue_number} updated to {$validated['status']}.");
    }
}
