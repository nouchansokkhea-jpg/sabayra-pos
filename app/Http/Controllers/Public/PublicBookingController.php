<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\BusinessMember;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Reservation;
use App\Services\Business\CategoryExperienceService;
use App\Services\Notifications\BusinessNotificationService;
use App\Services\Tenant\TenantContext;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PublicBookingController extends Controller
{
    public function __construct(
        private readonly CategoryExperienceService $categoryExperience
    ) {
    }

    /**
     * Return available time slots for a service business on a selected date.
     */
    public function slots(Request $request, string $business_slug): JsonResponse
    {
        $business = $this->resolveActiveBusiness($business_slug);
        if (! $this->categoryExperience->isModuleEnabled($business, 'service_bookings')) {
            return response()->json(['slots' => [], 'error' => 'Service bookings are not enabled for this business.'], 422);
        }

        $validated = $request->validate([
            'date' => ['required', 'date', 'after_or_equal:today'],
            'service_id' => ['nullable', 'exists:products,id'],
            'staff_id' => ['nullable', 'exists:business_members,id'],
        ]);

        $date = Carbon::parse($validated['date']);
        $duration = 60;
        if (! empty($validated['service_id'])) {
            $service = Product::where('business_id', $business->id)->find($validated['service_id']);
            if ($service && (int) $service->duration_minutes > 0) {
                $duration = (int) $service->duration_minutes;
            }
        }

        $staffId = null;
        if (! empty($validated['staff_id'])) {
            $staffId = BusinessMember::where('business_id', $business->id)
                ->where('is_active', true)
                ->find($validated['staff_id'])?->id;
        }

        $existingBookings = Reservation::where('business_id', $business->id)
            ->where('booking_type', 'service')
            ->whereDate('reservation_date', $date->toDateString())
            ->whereNotIn('status', ['cancelled'])
            ->get(['reservation_time', 'duration_minutes', 'end_time', 'staff_member_id']);

        $activeStaffCount = max(1, BusinessMember::where('business_id', $business->id)->where('is_active', true)->count());

        $slots = [];
        for ($hour = 9; $hour <= 19; $hour++) {
            $startStr = sprintf('%02d:00', $hour);
            $slotStart = Carbon::parse($date->toDateString() . ' ' . $startStr);
            $slotEnd = $slotStart->copy()->addMinutes($duration);

            $overlappingCount = $existingBookings->filter(function (Reservation $res) use ($date, $slotStart, $slotEnd, $staffId): bool {
                if ($staffId !== null && $res->staff_member_id !== $staffId) {
                    return false;
                }
                $existingStart = Carbon::parse($date->toDateString() . ' ' . $res->reservation_time);
                $existingDur = max(15, (int) ($res->duration_minutes ?? 60));
                $existingEnd = $res->end_time
                    ? Carbon::parse($date->toDateString() . ' ' . $res->end_time)
                    : $existingStart->copy()->addMinutes($existingDur);

                return $slotStart->lt($existingEnd) && $slotEnd->gt($existingStart);
            })->count();

            $capacity = $staffId !== null ? 1 : $activeStaffCount;
            $slots[] = [
                'time' => $startStr,
                'end_time' => $slotEnd->format('H:i'),
                'available' => $overlappingCount < $capacity,
            ];
        }

        return response()->json([
            'date' => $date->toDateString(),
            'duration_minutes' => $duration,
            'slots' => $slots,
        ]);
    }

    /**
     * Create a new customer service appointment with conflict prevention.
     */
    public function store(Request $request, string $business_slug): JsonResponse
    {
        $business = $this->resolveActiveBusiness($business_slug);
        TenantContext::set($business);

        if (! $this->categoryExperience->isModuleEnabled($business, 'service_bookings')) {
            return response()->json([
                'success' => false,
                'error' => 'Online appointment booking is disabled for this business.',
            ], 422);
        }

        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:150'],
            'customer_phone' => ['required', 'string', 'max:50'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'service_product_id' => ['required', 'exists:products,id'],
            'staff_member_id' => ['nullable', 'exists:business_members,id'],
            'reservation_date' => ['required', 'date', 'after_or_equal:today'],
            'reservation_time' => ['required', 'date_format:H:i'],
            'special_requests' => ['nullable', 'string', 'max:1000'],
        ]);

        $service = Product::where('business_id', $business->id)
            ->where('is_available', true)
            ->findOrFail($validated['service_product_id']);

        $staffMember = null;
        if (! empty($validated['staff_member_id'])) {
            $staffMember = BusinessMember::where('business_id', $business->id)
                ->where('is_active', true)
                ->findOrFail($validated['staff_member_id']);
        }

        $dateStr = Carbon::parse($validated['reservation_date'])->toDateString();
        $duration = max(15, (int) ($service->duration_minutes ?? 60));
        $slotStart = Carbon::parse("{$dateStr} {$validated['reservation_time']}");
        $slotEnd = $slotStart->copy()->addMinutes($duration);

        try {
            $reservation = DB::transaction(function () use (
                $business,
                $validated,
                $service,
                $staffMember,
                $dateStr,
                $duration,
                $slotStart,
                $slotEnd
            ): Reservation {
                Business::whereKey($business->id)->lockForUpdate()->first();

                $activeSameDay = Reservation::where('business_id', $business->id)
                    ->where('booking_type', 'service')
                    ->whereDate('reservation_date', $dateStr)
                    ->whereNotIn('status', ['cancelled'])
                    ->get();

                $activeStaffCount = max(1, BusinessMember::where('business_id', $business->id)->where('is_active', true)->count());
                $capacity = $staffMember ? 1 : $activeStaffCount;

                $conflicts = $activeSameDay->filter(function (Reservation $existing) use ($dateStr, $slotStart, $slotEnd, $staffMember): bool {
                    if ($staffMember && $existing->staff_member_id !== $staffMember->id) {
                        return false;
                    }
                    $existingStart = Carbon::parse($dateStr . ' ' . $existing->reservation_time);
                    $existingEnd = $existing->end_time
                        ? Carbon::parse($dateStr . ' ' . $existing->end_time)
                        : $existingStart->copy()->addMinutes(max(15, (int) ($existing->duration_minutes ?? 60)));

                    return $slotStart->lt($existingEnd) && $slotEnd->gt($existingStart);
                })->count();

                if ($conflicts >= $capacity) {
                    throw new \RuntimeException('This time slot is no longer available. Please choose another time.');
                }

                Customer::firstOrCreate(
                    ['business_id' => $business->id, 'phone' => $validated['customer_phone']],
                    ['name' => $validated['customer_name'], 'email' => $validated['customer_email'] ?? null]
                );

                $bookingCode = 'BK-' . now()->format('ymd') . '-' . strtoupper(Str::random(4));

                return Reservation::create([
                    'business_id' => $business->id,
                    'booking_type' => 'service',
                    'booking_code' => $bookingCode,
                    'customer_name' => $validated['customer_name'],
                    'customer_phone' => $validated['customer_phone'],
                    'customer_email' => $validated['customer_email'] ?? null,
                    'party_size' => 1,
                    'reservation_date' => $dateStr,
                    'reservation_time' => $validated['reservation_time'],
                    'duration_minutes' => $duration,
                    'end_time' => $slotEnd->format('H:i'),
                    'price_usd' => (float) $service->effective_price,
                    'service_product_id' => $service->id,
                    'staff_member_id' => $staffMember?->id,
                    'special_requests' => $validated['special_requests'] ?? null,
                    'status' => 'confirmed',
                    'payment_status' => 'unpaid',
                ]);
            });
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 422);
        }

        try {
            app(BusinessNotificationService::class)->notifyReservationEvent($reservation, 'reservation.created');
        } catch (\Throwable $e) {
            Log::warning('Public booking notification skipped: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'booking_code' => $reservation->booking_code,
            'redirect_url' => route('public.booking-status', [
                'business_slug' => $business->slug,
                'booking_code' => $reservation->booking_code,
            ]),
            'message' => 'Your appointment is confirmed!',
        ]);
    }

    /**
     * Render appointment confirmation & status pass for the customer.
     */
    public function show(string $business_slug, string $booking_code): View
    {
        $business = $this->resolveActiveBusiness($business_slug);
        TenantContext::set($business);

        $reservation = Reservation::with(['serviceProduct', 'staffMember.user'])
            ->where('business_id', $business->id)
            ->where('booking_code', $booking_code)
            ->firstOrFail();

        return view('public.booking-status', compact('business', 'reservation'));
    }

    private function resolveActiveBusiness(string $business_slug): Business
    {
        $decodedSlug = urldecode($business_slug);

        return Business::where(function ($query) use ($business_slug, $decodedSlug): void {
            $query->where('slug', $business_slug)
                ->orWhere('slug', $decodedSlug);
        })
            ->where('status', 'active')
            ->firstOrFail();
    }
}
