<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="min-h-full bg-gray-50 dark:bg-gray-950">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Booking {{ $reservation->booking_code }} - {{ $business->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full font-sans antialiased text-gray-900 dark:text-gray-100 py-10 px-4">
    <div class="max-w-lg mx-auto bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-3xl p-6 sm:p-8 shadow-xl space-y-6">
        <div class="text-center space-y-2">
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300 uppercase">
                Appointment {{ ucfirst($reservation->status) }}
            </span>
            <h1 class="text-2xl font-black text-gray-900 dark:text-white">{{ $business->name }}</h1>
            <p class="text-xs font-mono font-bold text-orange-600 dark:text-orange-400">Code: {{ $reservation->booking_code }}</p>
        </div>

        <div class="p-4 rounded-2xl bg-gray-50 dark:bg-gray-800/60 border border-gray-200 dark:border-gray-700 space-y-3 text-xs">
            <div class="flex justify-between">
                <span class="text-gray-500 dark:text-gray-400">Service</span>
                <span class="font-bold text-gray-900 dark:text-white">{{ $reservation->serviceProduct?->name_en ?? 'Scheduled Visit' }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500 dark:text-gray-400">Date &amp; Time</span>
                <span class="font-mono font-bold text-gray-900 dark:text-white">{{ $reservation->reservation_date?->format('Y-m-d') }} · {{ $reservation->reservation_time }}{{ $reservation->end_time ? ' - ' . $reservation->end_time : '' }}</span>
            </div>
            @if ($reservation->staffMember?->user)
                <div class="flex justify-between">
                    <span class="text-gray-500 dark:text-gray-400">Specialist</span>
                    <span class="font-bold text-gray-900 dark:text-white">{{ $reservation->staffMember->user->name }}</span>
                </div>
            @endif
            <div class="flex justify-between">
                <span class="text-gray-500 dark:text-gray-400">Customer</span>
                <span class="font-bold text-gray-900 dark:text-white">{{ $reservation->customer_name }} ({{ $reservation->customer_phone }})</span>
            </div>
            @if ($reservation->price_usd)
                <div class="flex justify-between pt-2 border-t border-gray-200 dark:border-gray-700">
                    <span class="font-bold text-gray-700 dark:text-gray-300">Estimated Fee</span>
                    <span class="font-mono font-black text-orange-600 dark:text-orange-400">${{ number_format((float) $reservation->price_usd, 2) }} ({{ number_format((float) $reservation->price_usd * (float) ($business->exchange_rate_khr ?: 4100)) }} ៛)</span>
                </div>
            @endif
        </div>

        <a href="{{ route('public.menu', ['business_slug' => $business->slug]) }}"
           class="block w-full text-center py-3 rounded-xl bg-slate-900 hover:bg-slate-800 dark:bg-orange-600 dark:hover:bg-orange-700 text-white font-bold text-xs transition">
            &larr; Back to {{ $business->name }}
        </a>
    </div>
</body>
</html>
