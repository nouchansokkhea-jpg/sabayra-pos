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
<body class="min-h-full font-sans antialiased text-gray-900 dark:text-gray-100 bg-gray-50 dark:bg-gray-950 p-4 sm:p-6 lg:p-8 flex items-center justify-center">
    <div class="w-full max-w-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-3xl shadow-xl overflow-hidden p-6 sm:p-8 space-y-6">
        @if(session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-xs font-semibold text-emerald-800 dark:border-emerald-800/60 dark:bg-emerald-950/40 dark:text-emerald-300">
                {{ session('success') }}
            </div>
        @endif

        @if(session('info'))
            <div class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-xs font-semibold text-blue-800 dark:border-blue-800/60 dark:bg-blue-950/40 dark:text-blue-300">
                {{ session('info') }}
            </div>
        @endif

        @if(session('error') || $errors->any())
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-xs font-semibold text-rose-800 dark:border-rose-800/60 dark:bg-rose-950/40 dark:text-rose-300">
                {{ session('error') ?? $errors->first() }}
            </div>
        @endif

        <!-- Top Status Card -->
        <div class="text-center space-y-2">
            <div class="w-14 h-14 mx-auto rounded-full {{ $order->payment_status === 'paid' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-400' : 'bg-[#0F1E3A] text-amber-400' }} flex items-center justify-center font-black text-2xl shadow-xs">
                {{ $order->payment_status === 'paid' ? '✓' : '★' }}
            </div>
            <h1 class="text-xl font-black text-gray-900 dark:text-white">Order {{ $order->order_number }}</h1>
            <p class="text-xs text-gray-700 dark:text-gray-200 font-medium">
                Customer: <span class="font-bold text-gray-900 dark:text-white">{{ $order->customer_name }}</span>
                • {{ $order->created_at->format('M d, Y - h:i A') }}
            </p>
            <div class="flex items-center justify-center gap-2 pt-1">
                <span class="px-3 py-1 rounded-full text-[11px] font-bold bg-slate-100 text-slate-800 dark:bg-gray-800 dark:text-slate-200">
                    {{ $order->paymentMethodLabel() }}
                </span>
                @php
                    $payBadgeClass = match ($order->payment_status) {
                        'paid' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300',
                        'pending_verification' => 'bg-amber-100 text-amber-900 dark:bg-amber-950/60 dark:text-amber-300',
                        'pending_collection' => 'bg-blue-100 text-blue-800 dark:bg-blue-950/60 dark:text-blue-300',
                        'rejected', 'failed' => 'bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300',
                        default => 'bg-slate-100 text-slate-800 dark:bg-gray-800 dark:text-slate-200',
                    };
                @endphp
                <span class="px-3 py-1 rounded-full text-[11px] font-bold {{ $payBadgeClass }}">
                    {{ $order->paymentStatusLabel() }}
                </span>
            </div>
        </div>

        <!-- Order Progress Bar -->
        @php
            $statuses = ['pending', 'confirmed', 'preparing', 'ready', 'completed'];
            $currentIndex = array_search($order->status, $statuses);
            if ($currentIndex === false) {
                $currentIndex = 0;
            }
        @endphp
        <div class="py-4 border-y border-gray-100 dark:border-gray-800">
            <p class="text-xs font-bold uppercase tracking-wider text-gray-700 dark:text-gray-200 mb-3 text-center">Live Order Progress</p>
            <div class="flex items-center justify-between text-[10px] font-bold">
                <span class="{{ $currentIndex >= 0 ? 'text-orange-600' : 'text-gray-400 dark:text-gray-500' }}">Received</span>
                <span class="{{ $currentIndex >= 1 ? 'text-orange-600' : 'text-gray-400 dark:text-gray-500' }}">Confirmed</span>
                <span class="{{ $currentIndex >= 2 ? 'text-orange-600' : 'text-gray-400 dark:text-gray-500' }}">Preparing</span>
                <span class="{{ $currentIndex >= 3 ? 'text-orange-600' : 'text-gray-400 dark:text-gray-500' }}">Ready</span>
                <span class="{{ $currentIndex >= 4 ? 'text-emerald-600' : 'text-gray-400 dark:text-gray-500' }}">Completed</span>
            </div>
            <div class="w-full bg-gray-100 dark:bg-gray-800 h-2 rounded-full overflow-hidden mt-2">
                <div class="bg-orange-600 h-full transition-all duration-500" style="width: {{ min(100, max(20, ($currentIndex + 1) * 20)) }}%"></div>
            </div>
        </div>

        <!-- Payment Settlement Panels -->
        @if($order->payment_status === 'paid')
            <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 dark:bg-emerald-950/30 dark:border-emerald-800/50 text-xs space-y-1 text-emerald-900 dark:text-emerald-200">
                <p class="font-bold">Payment Confirmed</p>
                <p>Method: <strong>{{ $order->paymentMethodLabel() }}</strong></p>
                @if($order->payment_reference)
                    <p>Reference: <span class="font-mono">{{ $order->payment_reference }}</span></p>
                @endif
                @if($order->paid_at)
                    <p>Confirmed at: {{ $order->paid_at->format('M d, Y h:i A') }}</p>
                @endif
            </div>
        @elseif($order->payment_method === 'khqr')
            <div class="p-5 rounded-2xl bg-rose-50/70 border border-rose-200 dark:bg-rose-950/20 dark:border-rose-900/40 text-center space-y-3">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white dark:bg-gray-900 border border-rose-200 text-rose-700 dark:text-rose-300 font-bold text-xs">
                    <img src="{{ asset('images/payments/bakong.png') }}" alt="Bakong KHQR" class="h-4 w-4 object-contain">
                    <span>Bakong KHQR</span>
                </div>
                <p class="text-xs text-rose-950 dark:text-rose-200 font-semibold">
                    Scan this official KHQR code with any Cambodian banking app.
                </p>
                @if($khqrSvg)
                    <div class="w-52 h-52 mx-auto bg-white p-3 rounded-2xl shadow-sm border border-rose-200 flex items-center justify-center">
                        {!! $khqrSvg !!}
                    </div>
                @else
                    <div class="p-3 rounded-xl bg-white dark:bg-gray-900 text-xs text-rose-700 dark:text-rose-300">
                        KHQR payload could not be rendered for this session.
                    </div>
                @endif
                <div class="text-xs">
                    <p class="font-black text-gray-900 dark:text-white text-base">${{ number_format($order->total_amount, 2) }}</p>
                    <p class="text-gray-600 dark:text-gray-300 font-mono">({{ number_format($order->total_khr, 0) }} ៛)</p>
                    @if($latestTransaction?->reference_number)
                        <p class="text-[11px] font-mono text-gray-500 mt-1">Ref: {{ $latestTransaction->reference_number }}</p>
                    @endif
                </div>

                @if($proofToken)
                    <form method="POST" action="{{ route('public.order-payment.verify', ['business_slug' => $business->slug, 'order_number' => $order->order_number]) }}">
                        @csrf
                        <input type="hidden" name="token" value="{{ $proofToken }}">
                        <button type="submit" class="px-5 py-2.5 rounded-xl bg-[#0F1E3A] hover:bg-[#192F59] text-white text-xs font-bold transition cursor-pointer">
                            Verify Payment Status with Bakong
                        </button>
                    </form>
                @endif
            </div>
        @elseif($order->payment_method === 'aba_payway')
            <div class="p-5 rounded-2xl bg-slate-50 border border-slate-200 dark:bg-gray-800/70 dark:border-gray-700 space-y-4">
                <div class="flex items-center justify-center gap-2">
                    <img src="{{ asset('images/payments/aba.png') }}" alt="ABA PayWay" class="h-6 w-6 rounded object-contain bg-white p-0.5">
                    <span class="font-black text-sm text-[#0F1E3A] dark:text-white">Pay with ABA PayWay</span>
                </div>

                @if($latestTransaction && ($latestTransaction->qr_image_url || $latestTransaction->khqr_payload || $latestTransaction->checkout_url))
                    @if($latestTransaction->qr_image_url)
                        <div class="w-48 h-48 mx-auto bg-white p-2 rounded-2xl border border-slate-200">
                            <img src="{{ $latestTransaction->qr_image_url }}" alt="ABA PayWay QR" class="w-full h-full object-contain">
                        </div>
                    @endif
                    @if($latestTransaction->checkout_url)
                        <div class="text-center">
                            <a href="{{ $latestTransaction->checkout_url }}" target="_blank" rel="noopener noreferrer"
                               class="inline-flex items-center justify-center px-5 py-2.5 rounded-xl bg-[#005E7C] hover:bg-[#004A62] text-white text-xs font-bold transition">
                                Open ABA PayWay Checkout
                            </a>
                        </div>
                    @endif
                @elseif($paymentMethods['aba_payway']['payway_link'] ?? null)
                    <div class="text-center">
                        <a href="{{ $paymentMethods['aba_payway']['payway_link'] }}" target="_blank" rel="noopener noreferrer"
                           class="inline-flex items-center justify-center px-5 py-2.5 rounded-xl bg-[#005E7C] hover:bg-[#004A62] text-white text-xs font-bold transition">
                            Open Merchant ABA PayWay Link
                        </a>
                    </div>
                @endif

                @if($proofToken && ($paymentMethods['aba_payway']['mode'] ?? '') === 'api')
                    <form method="POST" action="{{ route('public.order-payment.verify', ['business_slug' => $business->slug, 'order_number' => $order->order_number]) }}" class="text-center">
                        @csrf
                        <input type="hidden" name="token" value="{{ $proofToken }}">
                        <button type="submit" class="px-5 py-2 rounded-xl bg-[#0F1E3A] hover:bg-[#192F59] text-white text-xs font-bold transition cursor-pointer">
                            Verify ABA PayWay Transaction Status
                        </button>
                    </form>
                @endif
            </div>
        @elseif($order->payment_method === 'bank_transfer')
            <div class="p-5 rounded-2xl bg-slate-50 border border-slate-200 dark:bg-gray-800/70 dark:border-gray-700 space-y-3 text-xs">
                <h3 class="font-black text-sm text-[#0F1E3A] dark:text-white">Bank Transfer Details</h3>
                <div class="grid grid-cols-1 gap-1.5 bg-white dark:bg-gray-900 p-3.5 rounded-xl border border-slate-200 dark:border-gray-700">
                    <div class="flex justify-between">
                        <span class="text-slate-500">Bank:</span>
                        <span class="font-bold text-slate-900 dark:text-white">{{ $paymentConfig->bank_name }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Account Holder:</span>
                        <span class="font-bold text-slate-900 dark:text-white">{{ $paymentConfig->bank_account_holder }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-slate-500">Account Number:</span>
                        <span class="font-mono font-bold text-slate-900 dark:text-white">{{ $paymentConfig->bank_account_number }}</span>
                    </div>
                    @if($paymentConfig->bank_instructions)
                        <p class="text-[11px] text-slate-600 dark:text-slate-300 pt-1.5 border-t border-slate-100 dark:border-gray-800">
                            {{ $paymentConfig->bank_instructions }}
                        </p>
                    @endif
                    @if($paymentConfig->bank_reference_instructions)
                        <p class="text-[11px] text-amber-800 dark:text-amber-300 font-medium">
                            Reference: {{ $paymentConfig->bank_reference_instructions }}
                        </p>
                    @endif
                </div>
            </div>
        @elseif($order->payment_method === 'static_qr')
            <div class="p-5 rounded-2xl bg-slate-50 border border-slate-200 dark:bg-gray-800/70 dark:border-gray-700 text-center space-y-3 text-xs">
                <h3 class="font-black text-sm text-[#0F1E3A] dark:text-white">{{ $paymentConfig->static_qr_label ?: 'QR Payment' }}</h3>
                @if($paymentConfig->static_qr_image_path)
                    <div class="w-48 h-48 mx-auto bg-white p-2 rounded-2xl border border-slate-200">
                        <img src="{{ $paymentConfig->static_qr_image_path }}" alt="Merchant Static QR" class="w-full h-full object-contain">
                    </div>
                @endif
                @if($paymentConfig->static_qr_instructions)
                    <p class="text-slate-600 dark:text-slate-300">{{ $paymentConfig->static_qr_instructions }}</p>
                @endif
            </div>
        @elseif($order->payment_method === 'cod')
            <div class="p-4 rounded-2xl bg-blue-50 border border-blue-200 dark:bg-blue-950/30 dark:border-blue-800/50 text-xs text-blue-900 dark:text-blue-200">
                <strong>Cash on Delivery (Pending Collection):</strong> Please have <strong>${{ number_format($order->total_amount, 2) }}</strong> ({{ number_format($order->total_khr, 0) }} ៛) ready when your order arrives.
            </div>
        @endif

        <!-- Proof Upload Form for Manual Verification Methods -->
        @if($proofToken && in_array($order->payment_method, ['bank_transfer', 'static_qr', 'aba_payway'], true) && $order->payment_status !== 'paid')
            <form method="POST"
                  action="{{ route('public.order-proof.upload', ['business_slug' => $business->slug, 'order_number' => $order->order_number]) }}"
                  enctype="multipart/form-data"
                  class="p-4 rounded-2xl border border-slate-200 dark:border-gray-700 bg-white dark:bg-gray-900 space-y-3 text-xs">
                @csrf
                <input type="hidden" name="proof_token" value="{{ $proofToken }}">
                <h4 class="font-bold text-slate-900 dark:text-white">Upload Payment Proof Screenshot</h4>
                <div>
                    <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-300 mb-1">Receipt Image (PNG, JPG, WebP, max 5MB) *</label>
                    <input type="file" name="proof_file" accept="image/png,image/jpeg,image/webp" required
                           class="block w-full text-xs text-slate-700 dark:text-slate-300">
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-300 mb-1">Transfer Reference (Optional)</label>
                        <input type="text" name="customer_reference" placeholder="e.g. TXN-12345"
                               class="w-full rounded-xl border border-slate-300 bg-white text-slate-900 dark:border-gray-700 dark:bg-gray-800 dark:text-white px-3 py-1.5 text-xs">
                    </div>
                    <div>
                        <label class="block text-[11px] font-bold text-slate-600 dark:text-slate-300 mb-1">Note (Optional)</label>
                        <input type="text" name="customer_note" placeholder="Account name sent from"
                               class="w-full rounded-xl border border-slate-300 bg-white text-slate-900 dark:border-gray-700 dark:bg-gray-800 dark:text-white px-3 py-1.5 text-xs">
                    </div>
                </div>
                <button type="submit" class="w-full py-2.5 rounded-xl bg-orange-600 hover:bg-orange-700 text-white font-bold text-xs transition cursor-pointer">
                    Submit Payment Proof for Verification
                </button>
            </form>
        @endif

        @if($order->proofs->isNotEmpty())
            <div class="p-4 rounded-2xl border border-slate-200 dark:border-gray-700 space-y-2 text-xs">
                <h4 class="font-bold text-slate-900 dark:text-white">Submitted Payment Proofs</h4>
                @foreach($order->proofs as $proof)
                    <div class="flex items-center justify-between py-1.5 border-b last:border-b-0 border-slate-100 dark:border-gray-800">
                        <div>
                            <span class="font-semibold">{{ $proof->customer_reference ?: $proof->original_filename }}</span>
                            @if($proof->review_note)
                                <p class="text-[11px] text-rose-600 dark:text-rose-400">Note: {{ $proof->review_note }}</p>
                            @endif
                        </div>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $proof->status === 'approved' ? 'bg-emerald-100 text-emerald-800' : ($proof->status === 'rejected' ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800') }}">
                            {{ $proof->status }}
                        </span>
                    </div>
                @endforeach
            </div>
        @endif

        <!-- Order Items Detail -->
        <div class="space-y-3 text-xs">
            <h3 class="font-bold uppercase tracking-wider text-gray-800 dark:text-gray-200">Order Items</h3>
            <div class="divide-y divide-gray-100 dark:divide-gray-800">
                @foreach ($order->items as $item)
                    <div class="py-2.5 flex justify-between items-start">
                        <div>
                            <p class="font-bold text-gray-900 dark:text-white">
                                <span class="text-orange-600">{{ (int) $item->quantity }}x</span> {{ $item->product_name }}
                            </p>
                            @if ($item->variant_name)
                                <p class="text-[11px] text-gray-600 dark:text-gray-300 font-medium">Size: {{ $item->variant_name }}</p>
                            @endif
                            @if ($item->addons->count() > 0)
                                <p class="text-[10px] text-gray-600 dark:text-gray-300 font-medium">
                                    Add-ons: {{ $item->addons->pluck('name')->implode(', ') }}
                                </p>
                            @endif
                        </div>
                        <span class="font-bold text-gray-900 dark:text-white">${{ number_format($item->subtotal, 2) }}</span>
                    </div>
                @endforeach
            </div>

            <div class="pt-3 border-t border-gray-100 dark:border-gray-800 space-y-1.5">
                <div class="flex justify-between text-gray-700 dark:text-gray-300 font-medium">
                    <span>{{ $order->tax_inclusive ? 'Subtotal (Tax Inclusive)' : 'Subtotal' }}</span>
                    <span>${{ number_format($order->subtotal, 2) }}</span>
                </div>
                @if ($order->discount_amount > 0)
                    <div class="flex justify-between text-emerald-600 font-medium">
                        <span>Discount</span>
                        <span>-${{ number_format($order->discount_amount, 2) }}</span>
                    </div>
                @endif
                @if (($order->service_charge_amount ?? 0) > 0)
                    <div class="flex justify-between text-gray-700 dark:text-gray-300 font-medium">
                        <span>Service Charge ({{ number_format((float) $order->service_charge_rate, 2) }}%)</span>
                        <span>${{ number_format($order->service_charge_amount, 2) }}</span>
                    </div>
                @endif
                @if ($order->tax_amount > 0)
                    <div class="flex justify-between text-gray-700 dark:text-gray-300 font-medium">
                        <span>{{ $order->tax_name ?: 'VAT' }} ({{ number_format((float) $order->tax_rate, 2) }}%{{ $order->tax_inclusive ? ' included' : '' }})</span>
                        <span>${{ number_format($order->tax_amount, 2) }}</span>
                    </div>
                @endif
                <div class="flex justify-between font-black text-sm pt-2 border-t border-gray-100 dark:border-gray-800 text-gray-900 dark:text-white">
                    <span>Total Amount</span>
                    <div class="text-right">
                        <span class="text-orange-600">${{ number_format($order->total_amount, 2) }}</span>
                        <p class="text-xs text-gray-600 dark:text-gray-300 font-semibold">({{ number_format($order->total_khr, 0) }} ៛)</p>
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
