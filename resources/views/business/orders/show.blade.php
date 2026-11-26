<x-business-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div class="flex items-center gap-3">
                <a href="{{ route('business.orders.index') }}" class="p-2 rounded-xl border border-slate-200 dark:border-gray-700 text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-gray-800 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </a>
                <div>
                    <div class="flex items-center gap-3">
                        <h2 class="font-bold text-2xl text-slate-900 dark:text-white tracking-tight font-mono">
                            {{ $order->order_number }}
                        </h2>
                        @php
                            $statusClasses = [
                                'pending' => 'bg-amber-100 text-amber-800 dark:bg-amber-950/50 dark:text-amber-300',
                                'confirmed' => 'bg-blue-100 text-blue-800 dark:bg-blue-950/50 dark:text-blue-300',
                                'preparing' => 'bg-purple-100 text-purple-800 dark:bg-purple-950/50 dark:text-purple-300',
                                'ready' => 'bg-teal-100 text-teal-800 dark:bg-teal-950/50 dark:text-teal-300',
                                'out_for_delivery' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950/50 dark:text-indigo-300',
                                'completed' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300',
                                'cancelled' => 'bg-rose-100 text-rose-800 dark:bg-rose-950/50 dark:text-rose-300',
                            ];
                        @endphp
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold {{ $statusClasses[$order->status] ?? 'bg-slate-100 text-slate-800 dark:bg-gray-700 dark:text-slate-200' }}">
                            {{ ucfirst(str_replace('_', ' ', $order->status)) }}
                        </span>
                    </div>
                    <p class="text-xs text-slate-600 dark:text-slate-300 mt-1">
                        {{ __('Placed on') }} {{ $order->created_at->format('M d, Y \a\t h:i A') }} ({{ $order->created_at->diffForHumans() }})
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('business.orders.receipt', $order) }}" target="_blank" class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 border border-slate-300 dark:border-gray-700 rounded-xl text-sm font-semibold text-slate-800 dark:text-slate-100 hover:bg-slate-50 dark:hover:bg-gray-700 transition shadow-sm">
                    <svg class="w-4 h-4 text-slate-600 dark:text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    {{ __('Print 80mm Receipt') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Order Items & Details (2 Cols) -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Items Table Card -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-slate-200/80 dark:border-gray-700/70 p-6">
                <h3 class="font-bold text-lg text-slate-900 dark:text-white pb-4 border-b border-slate-100 dark:border-gray-700 flex items-center justify-between">
                    <span>{{ __('Order Items') }} ({{ $order->items->count() }})</span>
                    @if($order->order_type === 'dine_in')
                        <span class="text-xs font-bold text-indigo-700 dark:text-indigo-300 bg-indigo-50 dark:bg-indigo-950/50 border border-indigo-200 dark:border-indigo-800/50 px-3 py-1 rounded-lg">
                            🍽️ Table #{{ $order->table?->table_number ?? 'Dine-In' }}
                        </span>
                    @elseif($order->order_type === 'takeaway')
                        <span class="text-xs font-bold text-amber-800 dark:text-amber-300 bg-amber-50 dark:bg-amber-950/50 border border-amber-200 dark:border-amber-800/50 px-3 py-1 rounded-lg">
                            🛍️ Takeaway / Pickup
                        </span>
                    @else
                        <span class="text-xs font-bold text-blue-700 dark:text-blue-300 bg-blue-50 dark:bg-blue-950/50 border border-blue-200 dark:border-blue-800/50 px-3 py-1 rounded-lg">
                            🛵 Delivery
                        </span>
                    @endif
                </h3>

                <div class="divide-y divide-slate-100 dark:divide-gray-700/60 mt-2">
                    @foreach($order->items as $item)
                        <div class="py-4 flex items-start justify-between gap-4">
                            <div class="space-y-1">
                                <div class="font-bold text-slate-900 dark:text-white text-base flex items-center gap-2">
                                    <span class="text-emerald-700 dark:text-emerald-400 font-extrabold">{{ (int)$item->quantity }}x</span>
                                    <span>{{ $item->product_name }}</span>
                                    @if($item->variant_name)
                                        <span class="text-xs font-semibold px-2 py-0.5 rounded bg-slate-100 dark:bg-gray-700 text-slate-700 dark:text-slate-200">
                                            {{ $item->variant_name }}
                                        </span>
                                    @endif
                                </div>

                                @if($item->addons->isNotEmpty())
                                    <div class="pl-6 text-xs text-slate-600 dark:text-slate-300 space-y-0.5">
                                        <div class="font-semibold text-slate-700 dark:text-slate-200">{{ __('Add-ons') }}:</div>
                                        @foreach($item->addons as $addon)
                                            <div class="flex items-center gap-2 text-slate-600 dark:text-slate-300">
                                                <span>+ {{ $addon->name }}</span>
                                                <span class="font-mono text-slate-500 dark:text-slate-400">(+${{ number_format($addon->price, 2) }})</span>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                @if($item->notes)
                                    <div class="pl-6 text-xs text-amber-800 dark:text-amber-300 bg-amber-50 dark:bg-amber-950/30 p-2 rounded-lg mt-1 border border-amber-200/60 dark:border-amber-800/50 inline-block">
                                        <strong>{{ __('Note') }}:</strong> {{ $item->notes }}
                                    </div>
                                @endif
                            </div>

                            <div class="text-right shrink-0">
                                <div class="font-bold text-slate-900 dark:text-white font-mono">${{ number_format($item->subtotal, 2) }}</div>
                                <div class="text-xs text-slate-500 dark:text-slate-400 font-mono">${{ number_format($item->unit_price, 2) }} / each</div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Financial Calculation Section -->
                <div class="border-t border-slate-200/80 dark:border-gray-700 pt-4 mt-4 space-y-2 text-sm text-slate-700 dark:text-slate-300">
                    <div class="flex justify-between">
                        <span>{{ $order->tax_inclusive ? __('Subtotal (Tax Inclusive)') : __('Subtotal') }}</span>
                        <span class="font-mono font-semibold text-slate-900 dark:text-white">${{ number_format($order->subtotal, 2) }}</span>
                    </div>

                    @if($order->discount_amount > 0)
                        <div class="flex justify-between text-emerald-600 dark:text-emerald-400">
                            <span>{{ __('Discount') }}</span>
                            <span class="font-mono font-semibold">-${{ number_format($order->discount_amount, 2) }}</span>
                        </div>
                    @endif

                    @if(($order->service_charge_amount ?? 0) > 0)
                        <div class="flex justify-between">
                            <span>{{ __('Service Charge') }} ({{ number_format((float) $order->service_charge_rate, 2) }}%)</span>
                            <span class="font-mono font-semibold text-slate-900 dark:text-white">${{ number_format($order->service_charge_amount, 2) }}</span>
                        </div>
                    @endif

                    @if($order->tax_amount > 0)
                        <div class="flex justify-between">
                            <span>{{ $order->tax_name ?: 'VAT' }} ({{ number_format((float) $order->tax_rate, 2) }}%{{ $order->tax_inclusive ? ' included' : '' }})</span>
                            <span class="font-mono font-semibold text-slate-900 dark:text-white">${{ number_format($order->tax_amount, 2) }}</span>
                        </div>
                    @endif

                    <div class="flex justify-between text-lg font-black text-slate-900 dark:text-white border-t border-slate-200/80 dark:border-gray-700 pt-3">
                        <span>{{ __('Grand Total') }}</span>
                        <div class="text-right">
                            <div class="font-mono text-emerald-700 dark:text-emerald-400">${{ number_format($order->total_amount, 2) }}</div>
                            <div class="text-xs font-semibold text-slate-600 dark:text-slate-300 font-mono">{{ number_format($order->total_khr) }} ៛</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status History Timeline -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-slate-200/80 dark:border-gray-700/70 p-6">
                <h3 class="font-bold text-lg text-slate-900 dark:text-white pb-4 border-b border-slate-100 dark:border-gray-700">
                    {{ __('Status History & Audit Trail') }}
                </h3>

                <div class="mt-4 space-y-4">
                    @foreach($order->statusHistories->sortByDesc('created_at') as $history)
                        <div class="flex items-start gap-3">
                            <div class="w-2.5 h-2.5 rounded-full bg-emerald-600 mt-1.5 shrink-0"></div>
                            <div class="flex-1">
                                <div class="text-sm font-bold text-slate-900 dark:text-white">
                                    {{ ucfirst(str_replace('_', ' ', $history->to_status)) }}
                                    @if($history->from_status)
                                        <span class="text-xs font-normal text-slate-500 dark:text-slate-400">({{ __('was') }} {{ ucfirst(str_replace('_', ' ', $history->from_status)) }})</span>
                                    @endif
                                </div>
                                @if($history->note)
                                    <div class="text-xs text-slate-700 dark:text-slate-300 mt-0.5">{{ $history->note }}</div>
                                @endif
                                <div class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                                    {{ $history->created_at->format('M d, Y h:i A') }}
                                    @if($history->user) • {{ $history->user->name }} @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Sidebar Actions & Customer Info (1 Col) -->
        <div class="space-y-6">
            <!-- Order Action Workflow -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-slate-200/80 dark:border-gray-700/70 p-6">
                <h3 class="font-bold text-base text-slate-900 dark:text-white mb-4">{{ __('Update Order Status') }}</h3>
                <form method="POST" action="{{ route('business.orders.update-status', $order) }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">{{ __('Status') }}</label>
                        <select name="status" class="w-full text-sm font-semibold text-slate-900 dark:text-white bg-white dark:bg-gray-900 border-slate-300 dark:border-gray-700 rounded-xl py-2 px-3 focus:ring-emerald-500 focus:border-emerald-500">
                            <option value="pending" {{ $order->status === 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="confirmed" {{ $order->status === 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                            <option value="preparing" {{ $order->status === 'preparing' ? 'selected' : '' }}>Preparing (Kitchen)</option>
                            <option value="ready" {{ $order->status === 'ready' ? 'selected' : '' }}>Ready for Pickup</option>
                            <option value="out_for_delivery" {{ $order->status === 'out_for_delivery' ? 'selected' : '' }}>Out for Delivery</option>
                            <option value="completed" {{ $order->status === 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="cancelled" {{ $order->status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">{{ __('Internal Note (Optional)') }}</label>
                        <input type="text" name="note" placeholder="e.g. Cooked without peanuts" class="w-full text-sm font-medium text-slate-900 dark:text-white bg-white dark:bg-gray-900 border-slate-300 dark:border-gray-700 rounded-xl py-2 px-3 placeholder:text-slate-400 dark:placeholder:text-slate-500 focus:ring-emerald-500 focus:border-emerald-500">
                    </div>

                    <button type="submit" class="w-full py-2.5 bg-slate-800 hover:bg-slate-900 dark:bg-orange-600 dark:hover:bg-orange-700 text-white rounded-xl text-sm font-bold shadow-sm transition">
                        {{ __('Save Status Change') }}
                    </button>
                </form>
            </div>

            <!-- Payment Verification & Settlement -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-slate-200/80 dark:border-gray-700/70 p-6 space-y-4">
                <div class="flex items-center justify-between gap-2">
                    <h3 class="font-bold text-base text-slate-900 dark:text-white">{{ __('Payment Settlement') }}</h3>
                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold {{ $order->payment_status === 'paid' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300' : ($order->payment_status === 'pending_verification' ? 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300' : 'bg-slate-100 text-slate-800 dark:bg-gray-700 dark:text-slate-200') }}">
                        {{ $order->paymentStatusLabel() }}
                    </span>
                </div>

                <div class="rounded-xl bg-slate-50 dark:bg-gray-900/60 border border-slate-200 dark:border-gray-700 p-3 text-xs space-y-1 text-slate-700 dark:text-slate-300">
                    <div class="flex justify-between">
                        <span>Method:</span>
                        <span class="font-bold text-slate-900 dark:text-white">{{ $order->paymentMethodLabel() }}</span>
                    </div>
                    @if($order->payment_reference)
                        <div class="flex justify-between">
                            <span>Reference:</span>
                            <span class="font-mono text-slate-900 dark:text-white">{{ $order->payment_reference }}</span>
                        </div>
                    @endif
                    @if($order->payment_verified_at)
                        <div class="flex justify-between">
                            <span>Verified:</span>
                            <span>{{ $order->payment_verified_at->format('M d, h:i A') }} @if($order->paymentVerifiedBy) by {{ $order->paymentVerifiedBy->name }} @endif</span>
                        </div>
                    @endif
                    @if($order->payment_collected_at)
                        <div class="flex justify-between">
                            <span>COD Collected:</span>
                            <span>{{ $order->payment_collected_at->format('M d, h:i A') }} @if($order->paymentCollectedBy) by {{ $order->paymentCollectedBy->name }} @endif</span>
                        </div>
                    @endif
                </div>

                @if($order->proofs->isNotEmpty())
                    <div class="space-y-3 border-t border-slate-100 dark:border-gray-700 pt-3">
                        <div class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">Customer Payment Proofs</div>
                        @foreach($order->proofs as $proof)
                            <div class="rounded-xl border border-slate-200 dark:border-gray-700 p-3 space-y-2 text-xs">
                                <a href="{{ route('business.orders.payment-proof.show', [$order, $proof]) }}" target="_blank" class="block">
                                    <img src="{{ route('business.orders.payment-proof.show', [$order, $proof]) }}" alt="Payment proof" class="max-h-44 w-full object-contain rounded-lg bg-slate-100 dark:bg-gray-900">
                                </a>
                                <div class="flex justify-between items-center">
                                    <span class="font-mono text-[11px]">{{ $proof->customer_reference ?: $proof->original_filename }}</span>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $proof->status === 'approved' ? 'bg-emerald-100 text-emerald-800' : ($proof->status === 'rejected' ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800') }}">
                                        {{ $proof->status }}
                                    </span>
                                </div>
                                @if($proof->customer_note)
                                    <p class="text-[11px] text-slate-600 dark:text-slate-300">Customer note: {{ $proof->customer_note }}</p>
                                @endif
                            </div>
                        @endforeach

                        @if($order->payment_status !== 'paid')
                            <form method="POST" action="{{ route('business.orders.payment.approve', $order) }}" class="space-y-2">
                                @csrf
                                <input type="text" name="review_note" placeholder="Approval note (optional)"
                                       class="w-full text-xs rounded-xl border border-slate-300 bg-white text-slate-900 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2">
                                <button type="submit" class="w-full py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition cursor-pointer">
                                    Approve Payment Proof
                                </button>
                            </form>

                            <form method="POST" action="{{ route('business.orders.payment.reject', $order) }}" class="space-y-2">
                                @csrf
                                <input type="text" name="review_note" required placeholder="Rejection reason (required)"
                                       class="w-full text-xs rounded-xl border border-slate-300 bg-white text-slate-900 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2">
                                <button type="submit" class="w-full py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold transition cursor-pointer">
                                    Reject Payment Proof
                                </button>
                            </form>
                        @endif
                    </div>
                @endif

                @if($order->payment_method === 'cod' && $order->payment_status !== 'paid')
                    <form method="POST" action="{{ route('business.orders.payment.collect-cod', $order) }}" class="space-y-2 border-t border-slate-100 dark:border-gray-700 pt-3">
                        @csrf
                        <input type="text" name="collection_note" placeholder="Delivery collection note (optional)"
                               class="w-full text-xs rounded-xl border border-slate-300 bg-white text-slate-900 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2">
                        <button type="submit" class="w-full py-2.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition cursor-pointer">
                            Mark Cash on Delivery Collected
                        </button>
                    </form>
                @endif

                <form method="POST" action="{{ route('business.orders.update-payment', $order) }}" class="space-y-3 border-t border-slate-100 dark:border-gray-700 pt-3">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">{{ __('Payment Status') }}</label>
                        <select name="payment_status" class="w-full text-sm font-semibold text-slate-900 dark:text-white bg-white dark:bg-gray-900 border border-slate-300 dark:border-gray-700 rounded-xl py-2 px-3 focus:ring-emerald-500 focus:border-emerald-500">
                            <option value="unpaid" {{ $order->payment_status === 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                            <option value="pending_verification" {{ $order->payment_status === 'pending_verification' ? 'selected' : '' }}>Pending Verification</option>
                            <option value="pending_collection" {{ $order->payment_status === 'pending_collection' ? 'selected' : '' }}>Pending Collection</option>
                            <option value="paid" {{ $order->payment_status === 'paid' ? 'selected' : '' }}>Paid</option>
                            <option value="rejected" {{ $order->payment_status === 'rejected' ? 'selected' : '' }}>Rejected</option>
                            <option value="failed" {{ $order->payment_status === 'failed' ? 'selected' : '' }}>Failed</option>
                            <option value="refunded" {{ $order->payment_status === 'refunded' ? 'selected' : '' }}>Refunded</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1">{{ __('Payment Method') }}</label>
                        <select name="payment_method" class="w-full text-sm font-semibold text-slate-900 dark:text-white bg-white dark:bg-gray-900 border border-slate-300 dark:border-gray-700 rounded-xl py-2 px-3 focus:ring-emerald-500 focus:border-emerald-500">
                            <option value="cash" {{ $order->payment_method === 'cash' ? 'selected' : '' }}>Cash</option>
                            <option value="cod" {{ $order->payment_method === 'cod' ? 'selected' : '' }}>Cash on Delivery</option>
                            <option value="khqr" {{ $order->payment_method === 'khqr' ? 'selected' : '' }}>Bakong KHQR</option>
                            <option value="aba_payway" {{ $order->payment_method === 'aba_payway' ? 'selected' : '' }}>ABA PayWay</option>
                            <option value="bank_transfer" {{ $order->payment_method === 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                            <option value="static_qr" {{ $order->payment_method === 'static_qr' ? 'selected' : '' }}>Static QR</option>
                            <option value="card" {{ $order->payment_method === 'card' ? 'selected' : '' }}>Credit / Debit Card</option>
                        </select>
                    </div>

                    <button type="submit" class="w-full py-2.5 bg-[#0F1E3A] hover:bg-[#192F59] text-white rounded-xl text-sm font-bold shadow-sm transition cursor-pointer">
                        {{ __('Update Payment') }}
                    </button>
                </form>
            </div>

            <!-- Customer Details Card -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-slate-200/80 dark:border-gray-700/70 p-6 space-y-3">
                <h3 class="font-bold text-base text-slate-900 dark:text-white">{{ __('Customer Information') }}</h3>

                <div>
                    <span class="text-xs text-slate-500 dark:text-slate-400 block">{{ __('Name') }}</span>
                    <span class="font-semibold text-slate-900 dark:text-white text-sm">{{ $order->customer_name }}</span>
                </div>

                <div>
                    <span class="text-xs text-slate-500 dark:text-slate-400 block">{{ __('Phone') }}</span>
                    <a href="tel:{{ $order->customer_phone }}" class="font-semibold text-emerald-700 dark:text-emerald-400 text-sm font-mono hover:underline">
                        {{ $order->customer_phone }}
                    </a>
                </div>

                @if($order->delivery_address)
                    <div>
                        <span class="text-xs text-slate-500 dark:text-slate-400 block">{{ __('Delivery Address') }}</span>
                        <span class="text-sm text-slate-800 dark:text-slate-200 leading-relaxed">{{ $order->delivery_address }}</span>
                    </div>
                @endif

                @if($order->notes)
                    <div class="pt-2 border-t border-slate-100 dark:border-gray-700">
                        <span class="text-xs text-slate-500 dark:text-slate-400 block">{{ __('Customer Instructions') }}</span>
                        <p class="text-xs text-slate-700 dark:text-slate-300 italic mt-0.5">{{ $order->notes }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-business-layout>
