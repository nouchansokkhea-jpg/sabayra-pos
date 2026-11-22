<x-business-layout title="Reports" heading="Analytics & Reports">
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 class="font-bold text-2xl text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                    <svg class="w-7 h-7 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    <span>{{ __('Financial Analytics & Sales Reports') }} <span class="text-slate-400 font-normal">/</span> <span class="font-khmer font-bold">របាយការណ៍ហិរញ្ញវត្ថុ</span></span>
                </h2>
                <p class="text-sm text-slate-600 dark:text-slate-300 font-medium mt-1">
                    {{ __('Monitor revenue in USD & KHR, track best sellers, sales by channel, and export audit sheets.') }}
                </p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('business.reports.export') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 border border-slate-300 dark:border-gray-700 rounded-xl text-sm font-bold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-gray-700 transition shadow-sm">
                    <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    {{ __('Export CSV') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- Period Filter Filter Tabs -->
        <div class="flex items-center gap-2 bg-white dark:bg-gray-800 p-2 rounded-2xl border border-slate-200/80 dark:border-gray-700 w-fit shadow-sm">
            @php
                $periods = [
                    'today' => __('Today'),
                    'week' => __('This Week'),
                    'month' => __('This Month'),
                    'year' => __('This Year'),
                ];
            @endphp
            @foreach($periods as $key => $label)
                <a href="{{ route('business.reports.index', ['period' => $key]) }}"
                   class="px-4 py-1.5 rounded-xl text-xs font-bold transition {{ $period === $key ? 'bg-slate-900 text-white dark:bg-orange-600 dark:text-white shadow-sm' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-gray-700' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>

        <!-- KPI Cards Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-slate-200/80 dark:border-gray-700 shadow-sm">
                <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">{{ __('Total Revenue') }}</span>
                <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1 font-mono">
                    ${{ number_format($totalRevenue, 2) }}
                </div>
                <div class="text-xs text-slate-600 dark:text-slate-300 font-mono font-medium mt-0.5">
                    ≈ {{ number_format($totalRevenueKhr) }} ៛
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-slate-200/80 dark:border-gray-700 shadow-sm">
                <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">{{ __('Total Orders') }}</span>
                <div class="text-2xl font-black text-slate-900 dark:text-white mt-1">
                    {{ $totalOrders }}
                </div>
                <div class="text-xs text-slate-600 dark:text-slate-300 font-medium mt-0.5">
                    {{ $completedOrders }} {{ __('completed') }}
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-slate-200/80 dark:border-gray-700 shadow-sm">
                <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">{{ __('Menu Views / Scans') }}</span>
                <div class="text-2xl font-black text-orange-600 dark:text-orange-400 mt-1 font-mono">
                    {{ number_format($periodQrScans ?? $totalQrScans) }}
                </div>
                <div class="text-xs text-slate-600 dark:text-slate-300 font-medium mt-0.5">
                    {{ number_format($totalQrScans) }} {{ __('all-time scans') }}
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-slate-200/80 dark:border-gray-700 shadow-sm">
                <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">{{ __('Avg Order Value (AOV)') }}</span>
                <div class="text-2xl font-black text-slate-900 dark:text-white mt-1 font-mono">
                    ${{ number_format($avgOrderValue, 2) }}
                </div>
                <div class="text-xs text-slate-600 dark:text-slate-300 font-mono font-medium mt-0.5">
                    ≈ {{ number_format($business->khrEquivalent($avgOrderValue)) }} ៛
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-slate-200/80 dark:border-gray-700 shadow-sm">
                <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">{{ __('Fulfillment Rate') }}</span>
                <div class="text-2xl font-black text-indigo-600 dark:text-indigo-400 mt-1">
                    {{ $totalOrders > 0 ? round(($completedOrders / $totalOrders) * 100) : 100 }}%
                </div>
                <div class="text-xs text-slate-600 dark:text-slate-300 font-medium mt-0.5">
                    {{ __('Success rate') }}
                </div>
            </div>
        </div>

        <!-- 14-Day Sales Trend Chart -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-slate-200/80 dark:border-gray-700 p-6">
            <h3 class="font-bold text-base text-slate-900 dark:text-white mb-4">{{ __('14-Day Daily Revenue Trend (USD $)') }}</h3>
            <div class="h-64">
                <canvas id="salesTrendChart"></canvas>
            </div>
        </div>

        <!-- Breakdowns Grid (Payment Methods & Channels & Top Selling) -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Payment Methods & Channels Breakdown -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-slate-200/80 dark:border-gray-700 p-6 space-y-6">
                <div>
                    <h3 class="font-bold text-base text-slate-900 dark:text-white pb-3 border-b border-slate-100 dark:border-gray-700 flex items-center justify-between">
                        <span>{{ __('Revenue by Payment Method') }}</span>
                    </h3>
                    <div class="space-y-3 mt-4">
                        @forelse($paymentBreakdown as $pay)
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-bold text-slate-800 dark:text-slate-200 capitalize flex items-center gap-2">
                                    @if($pay->payment_method === 'khqr') 🇰🇭 Bakong KHQR
                                    @elseif($pay->payment_method === 'cash') 💵 Cash
                                    @else 💳 Card
                                    @endif
                                    <span class="text-slate-500 dark:text-slate-400 font-medium">({{ $pay->count }} orders)</span>
                                </span>
                                <span class="font-mono font-bold text-slate-900 dark:text-white text-sm">${{ number_format($pay->total, 2) }}</span>
                            </div>
                        @empty
                            <p class="text-xs text-slate-500 dark:text-slate-400 italic py-2">{{ __('No settled payments for this period.') }}</p>
                        @endforelse
                    </div>
                </div>

                <div>
                    <h3 class="font-bold text-base text-slate-900 dark:text-white pb-3 border-b border-slate-100 dark:border-gray-700 flex items-center justify-between">
                        <span>{{ __('Sales by Order Channel') }}</span>
                    </h3>
                    <div class="space-y-3 mt-4">
                        @forelse($channelBreakdown as $chan)
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-bold text-slate-800 dark:text-slate-200 capitalize flex items-center gap-2">
                                    @if($chan->order_type === 'dine_in') 🍽️ Dine-In
                                    @elseif($chan->order_type === 'takeaway') 🛍️ Takeaway
                                    @else 🛵 Delivery
                                    @endif
                                    <span class="text-slate-500 dark:text-slate-400 font-medium">({{ $chan->count }} orders)</span>
                                </span>
                                <span class="font-mono font-bold text-slate-900 dark:text-white text-sm">${{ number_format($chan->total, 2) }}</span>
                            </div>
                        @empty
                            <p class="text-xs text-slate-500 dark:text-slate-400 italic py-2">{{ __('No orders for this period.') }}</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Top 5 Selling Products -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-slate-200/80 dark:border-gray-700 p-6">
                <h3 class="font-bold text-base text-slate-900 dark:text-white pb-3 border-b border-slate-100 dark:border-gray-700">
                    {{ __('Top 5 Best Selling Items') }}
                </h3>

                @if($topProducts->isEmpty())
                    <p class="text-xs text-slate-500 dark:text-slate-400 italic py-6 text-center">{{ __('No sales data recorded for this period.') }}</p>
                @else
                    <div class="divide-y divide-slate-100 dark:divide-gray-700 mt-2">
                        @foreach($topProducts as $top)
                            <div class="py-3 flex items-center justify-between">
                                <div>
                                    <h4 class="text-sm font-bold text-slate-900 dark:text-white">{{ $top->product_name }}</h4>
                                    <span class="text-xs text-slate-500 dark:text-slate-400 font-medium">{{ (int)$top->total_qty }} {{ __('units sold') }}</span>
                                </div>
                                <div class="text-right">
                                    <div class="font-mono font-black text-sm text-emerald-600 dark:text-emerald-400">${{ number_format($top->total_sales, 2) }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Chart.js Script -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const ctx = document.getElementById('salesTrendChart');
            if (ctx) {
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: {!! json_encode($chartLabels) !!},
                        datasets: [{
                            label: 'Daily Revenue ($)',
                            data: {!! json_encode($chartData) !!},
                            borderColor: '#059669',
                            backgroundColor: 'rgba(5, 150, 105, 0.08)',
                            fill: true,
                            tension: 0.35,
                            borderWidth: 2.5,
                            pointBackgroundColor: '#059669',
                            pointRadius: 4,
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    callback: (val) => '$' + val
                                }
                            }
                        }
                    }
                });
            }
        });
    </script>
</x-business-layout>
