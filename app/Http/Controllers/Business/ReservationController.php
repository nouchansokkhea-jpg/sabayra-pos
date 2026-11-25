<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Models\BusinessBranch;
use App\Models\Reservation;
use App\Models\RestaurantTable;
use App\Services\Tenant\TenantContext;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReservationController extends Controller
{
    /**
     * Display reservations calendar and list.
     */
    public function index(Request $request): View
    {
        $business = TenantContext::get();
        $date = $request->query('date', Carbon::today()->toDateString());
        $status = $request->query('status');

        $query = Reservation::with(['table', 'branch'])
            ->where('business_id', $business->id)
            ->whereDate('reservation_date', $date);

        if ($status) {
            $query->where('status', $status);
        }

        $reservations = $query->orderBy('reservation_time')->get();
        $tables = RestaurantTable::where('business_id', $business->id)->get();
        $branches = BusinessBranch::where('business_id', $business->id)->get();

        $stats = [
            'today_total' => Reservation::where('business_id', $business->id)->whereDate('reservation_date', Carbon::today())->count(),
            'pending' => Reservation::where('business_id', $business->id)->where('status', 'pending')->count(),
            'confirmed' => Reservation::where('business_id', $business->id)->where('status', 'confirmed')->count(),
        ];

        return view('business.reservations.index', compact('business', 'reservations', 'tables', 'branches', 'date', 'status', 'stats'));
    }

    /**
     * Store a new guest reservation.
     */
    public function store(Request $request): RedirectResponse
    {
        $business = TenantContext::get();

        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'required|string|max:50',
            'customer_email' => 'nullable|email|max:255',
            'party_size' => 'required|integer|min:1|max:100',
            'reservation_date' => 'required|date',
            'reservation_time' => 'required',
            'table_id' => 'nullable|uuid|exists:restaurant_tables,id',
            'branch_id' => 'nullable|uuid|exists:business_branches,id',
            'special_requests' => 'nullable|string|max:1000',
        ]);

        $validated['business_id'] = $business->id;
        $validated['status'] = 'confirmed';

        $reservation = Reservation::create($validated);

        app(\App\Services\Notifications\BusinessNotificationService::class)
            ->notifyReservationEvent($reservation, 'reservation.created');

        return back()->with('status', 'Reservation successfully booked!');
    }

    /**
     * Update reservation status (seated, cancelled, completed).
     */
    public function updateStatus(Request $request, Reservation $reservation): RedirectResponse
    {
        $business = TenantContext::get();
        abort_if($reservation->business_id !== $business->id, 403);

        $validated = $request->validate([
            'status' => 'required|in:pending,confirmed,seated,completed,cancelled',
        ]);

        $oldStatus = $reservation->status;
        $reservation->update(['status' => $validated['status']]);

        // If seated, optionally mark table as occupied
        if ($validated['status'] === 'seated' && $reservation->table) {
            $reservation->table->update(['status' => 'occupied']);
        } elseif (in_array($validated['status'], ['completed', 'cancelled']) && $reservation->table) {
            $reservation->table->update(['status' => 'available']);
        }

        if ($oldStatus !== $validated['status'] && in_array($validated['status'], ['confirmed', 'cancelled'], true)) {
            app(\App\Services\Notifications\BusinessNotificationService::class)
                ->notifyReservationEvent($reservation, "reservation.{$validated['status']}");
        }

        return back()->with('status', "Reservation status updated to {$validated['status']}.");
    }
}
