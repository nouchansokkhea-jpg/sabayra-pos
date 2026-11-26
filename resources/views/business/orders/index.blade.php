<x-business-layout title="Orders" heading="Orders & Live Sales">
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 class="font-bold text-2xl text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                    <svg class="w-7 h-7 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                    </svg>
                    <span>{{ __('Live Orders & Sales') }} <span class="text-slate-400 font-normal">/</span> <span class="font-khmer font-bold">ការគ្រប់គ្រងការបញ្ជាទិញ</span></span>
                </h2>
                <p class="text-sm text-slate-600 dark:text-slate-300 font-medium mt-1">
                    {{ __('Manage incoming QR orders, dine-in requests, takeaway, and customer delivery orders in real time.') }}
                </p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('business.pos.index') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-sm font-bold transition shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                    {{ __('Open Touch POS') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Status Filter Tabs -->
        <div class="flex flex-wrap items-center gap-2 pb-2 border-b border-slate-200 dark:border-gray-700">
            @php
                $currentStatus = request('status', '');
                $statuses = [
                    '' => ['label' => __('All Orders'), 'count' => $statusCounts['all'], 'color' => 'slate'],
                    'pending' => ['label' => __('Pending'), 'count' => $statusCounts['pending'], 'color' => 'amber'],
                    'confirmed' => ['label' => __('Confirmed'), 'count' => $statusCounts['confirmed'], 'color' => 'blue'],
                    'preparing' => ['label' => __('Preparing / Kitchen'), 'count' => $statusCounts['preparing'], 'color' => 'purple'],
                    'ready' => ['label' => __('Ready for Pickup'), 'count' => $statusCounts['ready'], 'color' => 'teal'],
                    'completed' => ['label' => __('Completed'), 'count' => $statusCounts['completed'], 'color' => 'emerald'],
                    'cancelled' => ['label' => __('Cancelled'), 'count' => $statusCounts['cancelled'], 'color' => 'rose'],
                ];
            @endphp

            @foreach($statuses as $key => $meta)
                <a href="{{ route('business.orders.index', array_merge(request()->query(), ['status' => $key ?: null])) }}"
                   class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-xl text-xs font-bold transition shadow-sm {{ $currentStatus === $key ? 'bg-slate-900 text-white dark:bg-orange-600 dark:text-white' : 'bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 dark:bg-gray-800 dark:border-gray-700 dark:text-slate-200 dark:hover:bg-gray-700' }}">
                    <span>{{ $meta['label'] }}</span>
                    <span class="px-1.5 py-0.5 rounded-full text-[10px] font-bold {{ $currentStatus === $key ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-700 dark:bg-gray-700 dark:text-slate-200' }}">
                        {{ $meta['count'] }}
                    </span>
                </a>
            @endforeach
        </div>

        <!-- Filter Bar -->
        <div class="bg-white dark:bg-gray-800 p-4 rounded-2xl shadow-sm border border-slate-200/80 dark:border-gray-700">
            <form method="GET" action="{{ route('business.orders.index') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
                @if(request('status'))
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif
                <div class="sm:col-span-2">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('Search order #, customer name or phone...') }}" class="w-full text-sm border-slate-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white rounded-xl px-3.5 py-2 focus:ring-emerald-500 focus:border-emerald-500 font-medium">
                </div>
                <div>
                    <select name="order_type" class="w-full text-sm border-slate-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white rounded-xl px-3.5 py-2 focus:ring-emerald-500 focus:border-emerald-500 font-medium">
                        <option value="">{{ __('All Order Types') }}</option>
                        <option value="dine_in" {{ request('order_type') === 'dine_in' ? 'selected' : '' }}>Dine-In (Table)</option>
                        <option value="takeaway" {{ request('order_type') === 'takeaway' ? 'selected' : '' }}>Takeaway</option>
                        <option value="delivery" {{ request('order_type') === 'delivery' ? 'selected' : '' }}>Delivery</option>
                    </select>
                </div>
                <div class="flex items-center gap-2">
                    <button type="submit" class="flex-1 px-4 py-2 bg-slate-900 hover:bg-black dark:bg-orange-600 dark:hover:bg-orange-700 text-white text-sm font-bold rounded-xl transition">
                        {{ __('Filter') }}
                    </button>
                    @if(request()->hasAny(['search', 'order_type', 'status']))
                        <a href="{{ route('business.orders.index') }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-gray-700 dark:text-slate-200 dark:hover:bg-gray-600 text-sm font-bold rounded-xl transition">
                            {{ __('Reset') }}
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Orders Table -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-slate-200/80 dark:border-gray-700 overflow-hidden">
            @if($orders->isEmpty())
                <div class="text-center py-16">
                    <div class="w-16 h-16 mx-auto mb-4 bg-slate-100 dark:bg-gray-700 text-slate-400 dark:text-slate-300 rounded-2xl flex items-center justify-center">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                    </div>
                    <h4 class="text-base font-bold text-slate-900 dark:text-white">{{ __('No orders found') }}</h4>
                    <p class="text-sm text-slate-600 dark:text-slate-300 font-medium max-w-sm mx-auto mt-1">{{ __('When customers place orders from your QR menu or POS terminal, they will appear here.') }}</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-700 dark:text-slate-200">
                        <thead class="bg-slate-50/80 dark:bg-gray-900/60 border-b border-slate-200/80 dark:border-gray-700 text-xs uppercase tracking-wider text-slate-600 dark:text-slate-300 font-bold">
                            <tr>
                                <th class="py-3.5 px-4">{{ __('Order Details') }}</th>
                                <th class="py-3.5 px-4">{{ __('Customer / Channel') }}</th>
                                <th class="py-3.5 px-4">{{ __('Items Ordered') }}</th>
                                <th class="py-3.5 px-4">{{ __('Total') }}</th>
                                <th class="py-3.5 px-4">{{ __('Status') }}</th>
                                <th class="py-3.5 px-4 text-right">{{ __('Quick Action') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-gray-700/60 font-normal">
                            @foreach($orders as $order)
                                <tr class="hover:bg-slate-50/60 dark:hover:bg-gray-750/50 transition">
                                    <td class="py-4 px-4 align-top">
                                        <div class="font-bold text-slate-900 dark:text-white font-mono text-sm">
                                            <a href="{{ route('business.orders.show', $order) }}" class="text-emerald-700 hover:text-emerald-600 dark:text-emerald-400 dark:hover:text-emerald-300 font-bold hover:underline">
                                                {{ $order->order_number }}
                                            </a>
                                        </div>
                                        <span class="text-xs text-slate-600 dark:text-slate-300 font-medium block mt-0.5">{{ $order->created_at->format('M d, Y h:i A') }}</span>
                                        <div class="mt-2 flex items-center gap-1.5">
                                            @if($order->payment_status === 'paid')
                                                <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300 border border-emerald-300/40">
                                                    Paid ({{ strtoupper((string) $order->payment_method) }})
                                                </span>
                                            @elseif(in_array($order->payment_status, ['rejected', 'failed'], true))
                                                <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-rose-100 text-rose-800 dark:bg-rose-950/50 dark:text-rose-300 border border-rose-300/40">
                                                    {{ $order->paymentStatusLabel() }} ({{ strtoupper((string) $order->payment_method) }})
                                                </span>
                                            @else
                                                <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-950/50 dark:text-amber-300 border border-amber-300/40">
                                                    {{ $order->paymentStatusLabel() }} ({{ strtoupper((string) $order->payment_method) }})
                                                </span>
                                            @endif
                                        </div>
                                    </td>

                                    <td class="py-4 px-4 align-top">
                                        <div class="font-bold text-slate-900 dark:text-white">{{ $order->customer_name }}</div>
                                        <div class="text-xs text-slate-600 dark:text-slate-300 font-mono font-medium">{{ $order->customer_phone }}</div>
                                        <div class="mt-2">
                                            @if($order->order_type === 'dine_in')
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg text-xs font-bold bg-indigo-50 text-indigo-700 dark:bg-indigo-950/50 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800/50">
                                                    🍽️ Table #{{ $order->table?->table_number ?? 'Dine-In' }}
                                                </span>
                                            @elseif($order->order_type === 'takeaway')
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg text-xs font-bold bg-amber-50 text-amber-800 dark:bg-amber-950/50 dark:text-amber-300 border border-amber-200 dark:border-amber-800/50">
                                                    🛍️ Takeaway
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg text-xs font-bold bg-blue-50 text-blue-700 dark:bg-blue-950/50 dark:text-blue-300 border border-blue-200 dark:border-blue-800/50">
                                                    🛵 Delivery
                                                </span>
                                            @endif
                                        </div>
                                    </td>

                                    <td class="py-4 px-4 align-top max-w-xs">
                                        <ul class="space-y-1 text-xs">
                                            @foreach($order->items->take(3) as $item)
                                                <li class="flex items-start justify-between gap-2">
                                                    <span class="text-slate-800 dark:text-slate-200 font-medium">
                                                        <strong class="text-emerald-700 dark:text-emerald-400 font-bold">{{ (int)$item->quantity }}x</strong> {{ $item->product_name }}
                                                        @if($item->variant_name) <span class="text-slate-600 dark:text-slate-300 font-medium">({{ $item->variant_name }})</span> @endif
                                                    </span>
                                                    <span class="text-slate-700 dark:text-slate-300 font-mono font-semibold">${{ number_format($item->subtotal, 2) }}</span>
                                                </li>
                                            @endforeach
                                            @if($order->items->count() > 3)
                                                <li class="text-[11px] text-slate-600 dark:text-slate-300 italic font-semibold">
                                                    +{{ $order->items->count() - 3 }} {{ __('more item(s)...') }}
                                                </li>
                                            @endif
                                        </ul>
                                    </td>

                                    <td class="py-4 px-4 align-top">
                                        <div class="font-black text-slate-900 dark:text-white text-base font-mono">
                                            ${{ number_format($order->total_amount, 2) }}
                                        </div>
                                        <div class="text-xs text-slate-600 dark:text-slate-300 font-mono font-semibold">
                                            {{ number_format($order->total_khr) }} ៛
                                        </div>
                                    </td>

                                    <td class="py-4 px-4 align-top">
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
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold {{ $statusClasses[$order->status] ?? 'bg-slate-100 text-slate-800' }}">
                                            {{ ucfirst(str_replace('_', ' ', $order->status)) }}
                                        </span>
                                    </td>

                                    <td class="py-4 px-4 align-top text-right space-y-2">
                                        <!-- Quick Status Step Form -->
                                        @if($order->status === 'pending')
                                            <form method="POST" action="{{ route('business.orders.update-status', $order) }}" class="inline-block">
                                                @csrf
                                                <input type="hidden" name="status" value="confirmed">
                                                <button type="submit" class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-bold shadow-sm transition">
                                                    Accept / Confirm
                                                </button>
                                            </form>
                                        @elseif($order->status === 'confirmed')
                                            <form method="POST" action="{{ route('business.orders.update-status', $order) }}" class="inline-block">
                                                @csrf
                                                <input type="hidden" name="status" value="preparing">
                                                <button type="submit" class="px-3 py-1.5 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-xs font-bold shadow-sm transition">
                                                    Send to Kitchen
                                                </button>
                                            </form>
                                        @elseif($order->status === 'preparing')
                                            <form method="POST" action="{{ route('business.orders.update-status', $order) }}" class="inline-block">
                                                @csrf
                                                <input type="hidden" name="status" value="ready">
                                                <button type="submit" class="px-3 py-1.5 bg-teal-600 hover:bg-teal-700 text-white rounded-lg text-xs font-bold shadow-sm transition">
                                                    Mark Ready
                                                </button>
                                            </form>
                                        @elseif($order->status === 'ready')
                                            <form method="POST" action="{{ route('business.orders.update-status', $order) }}" class="inline-block">
                                                @csrf
                                                <input type="hidden" name="status" value="completed">
                                                <button type="submit" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold shadow-sm transition">
                                                    Complete Order
                                                </button>
                                            </form>
                                        @endif

                                        <div class="flex items-center justify-end gap-2 pt-1">
                                            <a href="{{ route('business.orders.show', $order) }}" class="text-xs font-bold text-slate-700 hover:text-emerald-700 dark:text-slate-300 dark:hover:text-emerald-400">
                                                {{ __('View') }}
                                            </a>
                                            <span class="text-slate-300 dark:text-slate-600">|</span>
                                            <a href="{{ route('business.orders.receipt', $order) }}" target="_blank" class="text-xs font-bold text-slate-700 hover:text-emerald-700 dark:text-slate-300 dark:hover:text-emerald-400">
                                                {{ __('Receipt') }}
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="p-4 border-t border-slate-100 dark:border-gray-700">
                    {{ $orders->links() }}
                </div>
            @endif
        </div>
    </div>
</x-business-layout>
