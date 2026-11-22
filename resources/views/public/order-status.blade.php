<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-gray-50 dark:bg-gray-950">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Order #{{ $order->order_number }} - {{ $business->name }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Kantumruy+Pro:wght@400;500;600;700&family=Battambang:wght@400;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans antialiased text-gray-900 dark:text-gray-100 bg-gray-50 dark:bg-gray-950 p-4 sm:p-6 lg:p-8 flex items-center justify-center">
    <div class="w-full max-w-lg bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-3xl shadow-xl overflow-hidden p-6 sm:p-8 space-y-6">
        <!-- Top Status Card -->
        <div class="text-center space-y-2">
            <div class="w-14 h-14 mx-auto rounded-full bg-emerald-50 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400 flex items-center justify-center font-black text-2xl shadow-xs">
                ✓
            </div>
            <h1 class="text-xl font-black text-gray-900 dark:text-white">Order Received!</h1>
            <p class="text-xs text-gray-500 font-mono">Order Number: <span class="font-bold text-orange-600">{{ $order->order_number }}</span></p>
            <p class="text-xs text-gray-600 dark:text-gray-300 font-medium">{{ __('Customer') }}: <span class="font-bold text-gray-900 dark:text-white">{{ $order->customer_name }}</span></p>
            <p class="text-[11px] text-gray-400">{{ $order->created_at->format('M d, Y - h:i A') }}</p>
        </div>

        <!-- Order Progress Bar -->
        @php
            $statuses = ['pending', 'confirmed', 'preparing', 'ready', 'completed'];
            $currentIndex = array_search($order->status, $statuses);
            if ($currentIndex === false) $currentIndex = 0;
        @endphp
        <div class="py-4 border-y border-gray-100 dark:border-gray-800">
            <p class="text-xs font-bold uppercase tracking-wider text-gray-500 mb-3 text-center">Live Order Progress</p>
            <div class="flex items-center justify-between text-[10px] font-bold">
                <span class="{{ $currentIndex >= 0 ? 'text-orange-600' : 'text-gray-400' }}">Received</span>
                <span class="{{ $currentIndex >= 1 ? 'text-orange-600' : 'text-gray-400' }}">Confirmed</span>
                <span class="{{ $currentIndex >= 2 ? 'text-orange-600' : 'text-gray-400' }}">Kitchen (Preparing)</span>
                <span class="{{ $currentIndex >= 3 ? 'text-orange-600' : 'text-gray-400' }}">Ready</span>
                <span class="{{ $currentIndex >= 4 ? 'text-emerald-600' : 'text-gray-400' }}">Completed</span>
            </div>
            <div class="w-full bg-gray-100 dark:bg-gray-800 h-2 rounded-full overflow-hidden mt-2">
                <div class="bg-orange-600 h-full transition-all duration-500" style="width: {{ min(100, max(20, ($currentIndex + 1) * 20)) }}%"></div>
            </div>
        </div>

        <!-- KHQR Scan to Pay Banner (if KHQR payment method & unpaid) -->
        @if ($order->payment_method === 'khqr' && $order->payment_status === 'unpaid')
            <div class="p-4 rounded-2xl bg-red-50/70 border border-red-200 dark:bg-red-950/20 dark:border-red-900/40 text-center space-y-3">
                <div class="inline-flex items-center space-x-1.5 px-2.5 py-0.5 rounded bg-red-600 text-white font-bold text-[10px] uppercase tracking-wider">
                    <span>Bakong KHQR</span>
                </div>
                <p class="text-xs text-red-950 dark:text-red-300 font-semibold">
                    Please scan KHQR with any Cambodian Banking App (ABA, ACLEDA, Wing, Canadia...)
                </p>
                <div class="w-40 h-40 mx-auto bg-white p-2.5 rounded-2xl shadow-sm border border-red-200 flex items-center justify-center">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data={{ urlencode("KHQR://{$business->slug}/{$order->order_number}/{$order->total_amount}") }}"
                         alt="KHQR" class="w-full h-full object-contain">
                </div>
                <div class="text-xs">
                    <p class="font-bold text-gray-900 dark:text-white">${{ number_format($order->total_amount, 2) }}</p>
                    <p class="text-gray-500 font-mono text-[11px]">(~ {{ number_format($order->total_khr, 0) }} ៛)</p>
                </div>
            </div>
        @endif

        <!-- Order Items Detail -->
        <div class="space-y-3 text-xs">
            <h3 class="font-bold uppercase tracking-wider text-gray-700 dark:text-gray-300">Order Items</h3>
            <div class="divide-y divide-gray-100 dark:divide-gray-800">
                @foreach ($order->items as $item)
                    <div class="py-2.5 flex justify-between items-start">
                        <div>
                            <p class="font-bold text-gray-900 dark:text-white">
                                <span class="text-orange-600">{{ (int)$item->quantity }}x</span> {{ $item->product_name }}
                            </p>
                            @if ($item->variant_name)
                                <p class="text-[11px] text-gray-400">Size: {{ $item->variant_name }}</p>
                            @endif
                            @if ($item->addons->count() > 0)
                                <p class="text-[10px] text-gray-400 font-medium">
                                    Add-ons: {{ $item->addons->pluck('name')->implode(', ') }}
                                </p>
                            @endif
                        </div>
                        <span class="font-bold text-gray-900 dark:text-white">${{ number_format($item->subtotal, 2) }}</span>
                    </div>
                @endforeach
            </div>

            <div class="pt-3 border-t border-gray-100 dark:border-gray-800 space-y-1.5">
                <div class="flex justify-between text-gray-500">
                    <span>Subtotal</span>
                    <span>${{ number_format($order->subtotal, 2) }}</span>
                </div>
                @if ($order->tax_amount > 0)
                    <div class="flex justify-between text-gray-500">
                        <span>Tax</span>
                        <span>${{ number_format($order->tax_amount, 2) }}</span>
                    </div>
                @endif
                <div class="flex justify-between font-black text-sm pt-2 border-t border-gray-100 dark:border-gray-800">
                    <span>Total Amount</span>
                    <div class="text-right">
                        <span class="text-orange-600">${{ number_format($order->total_amount, 2) }}</span>
                        <p class="text-xs text-gray-400 font-normal">({{ number_format($order->total_khr, 0) }} ៛)</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="pt-2 text-center">
            <a href="/{{ $business->slug }}" class="inline-flex items-center text-xs font-bold text-orange-600 hover:text-orange-700">
                &larr; Back to {{ $business->name }} Menu
            </a>
        </div>
    </div>
</body>
</html>
