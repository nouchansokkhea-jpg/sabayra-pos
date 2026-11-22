<x-business-layout title="Dashboard" heading="Business Overview">
<div class="space-y-6">
    <!-- Top Action Bar -->
    <div class="flex flex-wrap items-center justify-between gap-4 p-5 bg-white border border-gray-100 rounded-2xl shadow-xs dark:bg-gray-800 dark:border-gray-700/60">
        <div>
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ $business->name }}</h2>
            <p class="text-xs text-slate-600 dark:text-slate-300 font-medium mt-0.5">
                Active Currency: <span class="font-bold text-emerald-600 dark:text-emerald-400">{{ $business->default_currency }}</span> &bull; 
                Rate: <span class="font-bold text-slate-800 dark:text-slate-100">1 USD = {{ number_format($business->exchange_rate_khr, 0) }} KHR</span>
            </p>
        </div>

        <div class="flex items-center space-x-2.5">
            <a href="{{ route('business.pos.index') }}" class="inline-flex items-center px-4 py-2 text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-xs transition">
                <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                </svg>
                Open POS Terminal
            </a>

            <a href="{{ route('business.products.create') }}" class="inline-flex items-center px-4 py-2 text-xs font-bold text-white bg-orange-600 hover:bg-orange-700 rounded-xl shadow-xs transition">
                + New Product
            </a>

            <a href="/{{ $business->slug }}" target="_blank" class="inline-flex items-center px-3.5 py-2 text-xs font-bold text-gray-700 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600 rounded-xl transition">
                Live Menu
                <svg class="w-3.5 h-3.5 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                </svg>
            </a>
        </div>
    </div>

    <!-- Low Stock Warning Alerts (if any) -->
    @if ($lowStockProducts->count() > 0 || $lowStockIngredients->count() > 0)
        <div class="p-4 rounded-2xl bg-amber-50 border border-amber-200 dark:bg-amber-950/40 dark:border-amber-800/60 text-xs text-amber-900 dark:text-amber-200 font-medium">
            <div class="flex items-center space-x-2 font-bold mb-1">
                <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                <span>Low Stock Warning Detected!</span>
            </div>
            <div class="flex flex-wrap gap-2 mt-2">
                @foreach ($lowStockProducts as $p)
                    <span class="px-2 py-0.5 rounded bg-white dark:bg-gray-800 border border-amber-300 dark:border-amber-700 font-bold text-slate-800 dark:text-slate-200">
                        {{ $p->name_en }}: {{ $p->stock_quantity }} {{ $p->unit }} remaining
                    </span>
                @endforeach
                @foreach ($lowStockIngredients as $ing)
                    <span class="px-2 py-0.5 rounded bg-white dark:bg-gray-800 border border-amber-300 dark:border-amber-700 font-bold text-slate-800 dark:text-slate-200">
                        [Ingredient] {{ $ing->name_en }}: {{ $ing->current_stock }} {{ $ing->unit }}
                    </span>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Metrics Stat Cards (6 Key Indicators) -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
        <x-stat-card
            title="Total Products"
            :value="$totalProducts"
            subtitle="Available in catalog"
            color="blue"
            icon='<svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>'
        />

        <x-stat-card
            title="Total Categories"
            :value="$totalCategories"
            subtitle="Menu categories"
            color="orange"
            icon='<svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>'
        />

        <x-stat-card
            title="Total Menu Views"
            :value="number_format($totalMenuViews)"
            subtitle="Live QR scan views"
            color="cyan"
            icon='<svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>'
        />

        <x-stat-card
            title="Total Orders"
            :value="$totalOrders"
            subtitle="{{ $pendingOrders }} pending, {{ $preparingOrders }} kitchen"
            color="purple"
            icon='<svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>'
        />

        <x-stat-card
            title="Total Revenue"
            :value="'$' . number_format($totalRevenueUsd, 2)"
            subtitle="~ {{ number_format($totalRevenueKhr, 0) }} ៛"
            color="green"
            icon='<svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'
        />

        <x-stat-card
            title="Today's Sales"
            :value="'$' . number_format($todayRevenueUsd, 2)"
            subtitle="~ {{ number_format($todayRevenueKhr, 0) }} ៛ today"
            color="amber"
            icon='<svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>'
        />
    </div>

    <!-- Middle: 7-Day Chart & Best Sellers -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Sales Trend Chart -->
        <div class="p-6 bg-white border border-gray-100 rounded-2xl shadow-xs dark:bg-gray-800 dark:border-gray-700/60 lg:col-span-2">
            <h3 class="text-sm font-bold text-gray-900 dark:text-white">7-Day Sales Trend (USD)</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 font-medium mt-0.5">Daily completed revenue overview</p>

            <div class="mt-4 h-64">
                <canvas id="salesTrendChart"></canvas>
            </div>
        </div>

        <!-- Top Selling Items -->
        <div class="p-6 bg-white border border-gray-100 rounded-2xl shadow-xs dark:bg-gray-800 dark:border-gray-700/60">
            <h3 class="text-sm font-bold text-gray-900 dark:text-white">Best Selling Items</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 font-medium mt-0.5">Top products by total units sold</p>

            <div class="mt-4 space-y-3">
                @forelse ($bestSellers as $item)
                    <div class="flex items-center justify-between pb-3 border-b border-gray-50 dark:border-gray-700/40 text-xs">
                        <div>
                            <p class="font-bold text-gray-900 dark:text-white">{{ $item->product_name }}</p>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 font-medium">{{ (int)$item->total_sold }} units sold</p>
                        </div>
                        <span class="font-mono font-bold text-emerald-600 dark:text-emerald-400 text-sm">
                            ${{ number_format($item->total_earned, 2) }}
                        </span>
                    </div>
                @empty
                    <p class="py-8 text-center text-xs text-slate-500 dark:text-slate-400 font-medium">No sales transactions logged yet.</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Bottom: Recent Orders -->
    <div class="p-6 bg-white border border-gray-100 rounded-2xl shadow-xs dark:bg-gray-800 dark:border-gray-700/60">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-sm font-bold text-gray-900 dark:text-white">Recent Orders</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 font-medium mt-0.5">Live incoming customer orders</p>
            </div>
            <a href="{{ route('business.orders.index') }}" class="text-xs font-bold text-orange-600 hover:text-orange-700 dark:text-orange-400">View All Orders &rarr;</a>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700">
                <thead>
                    <tr class="text-left text-xs font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider">
                        <th class="pb-3">Order Number</th>
                        <th class="pb-3">Type / Table</th>
                        <th class="pb-3">Customer</th>
                        <th class="pb-3">Total Amount</th>
                        <th class="pb-3">Payment</th>
                        <th class="pb-3">Order Status</th>
                        <th class="pb-3 text-right">Time</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60 text-xs">
                    @forelse ($recentOrders as $order)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-750/50 transition">
                            <td class="py-3.5 font-bold font-mono text-gray-900 dark:text-white">
                                {{ $order->order_number }}
                            </td>
                            <td class="py-3.5">
                                <span class="capitalize text-slate-700 dark:text-slate-200 font-medium">{{ str_replace('_', ' ', $order->order_type) }}</span>
                                @if ($order->table)
                                    <span class="ml-1 text-[11px] font-bold text-orange-600 dark:text-orange-400">(Table {{ $order->table->table_number }})</span>
                                @endif
                            </td>
                            <td class="py-3.5 text-slate-700 dark:text-slate-200 font-medium">
                                {{ $order->customer_name ?? 'Guest' }}
                            </td>
                            <td class="py-3.5">
                                <span class="font-bold font-mono text-gray-900 dark:text-white">${{ number_format($order->total_amount, 2) }}</span>
                                <span class="text-[11px] font-mono text-slate-500 dark:text-slate-400 font-medium">({{ number_format($order->total_khr, 0) }} ៛)</span>
                            </td>
                            <td class="py-3.5">
                                <x-badge :color="$order->payment_status === 'paid' ? 'green' : 'amber'">
                                    {{ ucfirst($order->payment_status) }}
                                </x-badge>
                            </td>
                            <td class="py-3.5">
                                <x-badge :color="match($order->status) {
                                    'completed' => 'green',
                                    'preparing', 'confirmed' => 'blue',
                                    'cancelled' => 'red',
                                    default => 'amber'
                                }">
                                    {{ ucfirst($order->status) }}
                                </x-badge>
                            </td>
                            <td class="py-3.5 text-right text-slate-500 dark:text-slate-400 font-medium">
                                {{ $order->created_at->diffForHumans() }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-500 dark:text-slate-400 font-medium">No orders placed yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const ctx = document.getElementById('salesTrendChart');
        if (ctx) {
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: {!! json_encode($chartDates) !!},
                    datasets: [{
                        label: 'Sales ($ USD)',
                        data: {!! json_encode($chartSales) !!},
                        borderColor: '#f97316',
                        backgroundColor: 'rgba(249, 115, 22, 0.1)',
                        fill: true,
                        tension: 0.35,
                        borderWidth: 2,
                        pointBackgroundColor: '#f97316',
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { callback: value => '$' + value }
                        }
                    }
                }
            });
        }
    });
</script>
</x-business-layout>
