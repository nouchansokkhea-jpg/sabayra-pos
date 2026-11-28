<x-business-layout title="Analytics" heading="Business Intelligence & Analytics">
<div class="space-y-6">

    <div class="p-4 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-2xl shadow-xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
        <div>
            <h3 class="text-sm font-bold text-gray-900 dark:text-white">{{ $categoryMeta['name_en'] ?? 'Commercial' }} Velocity &amp; Analytics</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400">Tailored peak hours, {{ strtolower($categoryMeta['name_en'] ?? 'business') }} insights, and payment distribution.</p>
        </div>
        <span class="px-2.5 py-1 rounded-xl text-[11px] font-bold bg-orange-50 dark:bg-orange-950/50 text-orange-700 dark:text-orange-300 border border-orange-200 dark:border-orange-800">
            {{ $categoryMeta['name_km'] ?? '' }}
        </span>
    </div>

    @if (!empty($sectorInsights['cards']))
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            @foreach ($sectorInsights['cards'] as $card)
                <div class="p-4 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-2xl shadow-xs">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ $card['label'] }}</span>
                    <div class="mt-1 text-2xl font-black text-slate-900 dark:text-white">{{ $card['value'] }}</div>
                </div>
            @endforeach
        </div>
    @endif

    @if (!empty($sectorInsights['rows']))
        <div class="p-6 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-2xl shadow-xs">
            <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">{{ $sectorInsights['rows_title'] }}</h4>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                @foreach ($sectorInsights['rows'] as $row)
                    <div class="p-3 rounded-xl bg-slate-50 dark:bg-gray-900/60 border border-gray-200 dark:border-gray-700 flex items-center justify-between text-xs">
                        <span class="font-bold text-slate-900 dark:text-white">{{ $row['label'] }}</span>
                        <span class="font-mono font-bold text-emerald-600 dark:text-emerald-400">{{ $row['value'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Row 1: Peak Hours Distribution Bar Chart -->
    <div class="p-6 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-2xl shadow-xs">
        <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-700/60 mb-4">
            <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider">Hourly Traffic & Order Volume (Peak Hours)</h4>
            <span class="text-[10px] text-slate-400">Actual 24h Order Distribution</span>
        </div>

        <div class="h-48 flex items-end gap-1.5 sm:gap-3 pt-6">
            @php $maxCount = max(1, collect($peakHours)->max('count')); @endphp
            @foreach ($peakHours as $ph)
                <div class="flex-1 flex flex-col items-center gap-1 group relative">
                    <!-- Tooltip -->
                    <div class="absolute -top-7 opacity-0 group-hover:opacity-100 transition text-[9px] font-bold bg-gray-900 text-white px-1.5 py-0.5 rounded pointer-events-none whitespace-nowrap">
                        {{ $ph['count'] }} orders
                    </div>
                    <div class="w-full rounded-t-lg bg-orange-500/80 hover:bg-orange-600 transition"
                         style="height: {{ max(4, round(($ph['count'] / $maxCount) * 100)) }}%;"></div>
                    <span class="text-[9px] text-slate-400 font-mono rotate-45 sm:rotate-0 mt-1">{{ substr($ph['hour'], 0, 5) }}</span>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Row 2: Category Breakdown & Payment Methods -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Category Breakdown -->
        <div class="p-6 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-2xl shadow-xs">
            <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-4">Revenue by Category</h4>

            <div class="space-y-3">
                @forelse ($categoryBreakdown as $cat)
                    <div>
                        <div class="flex justify-between text-xs font-bold mb-1">
                            <span class="text-gray-900 dark:text-white">{{ $cat->category_name }}</span>
                            <span class="text-emerald-600 font-mono">${{ number_format($cat->total_revenue, 2) }}</span>
                        </div>
                        <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-2">
                            <div class="bg-orange-500 h-2 rounded-full" style="width: 75%"></div>
                        </div>
                    </div>
                @empty
                    <div class="py-10 text-center text-xs text-slate-400">No category sales recorded yet.</div>
                @endforelse
            </div>
        </div>

        <!-- Payment Method Breakdown -->
        <div class="p-6 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-2xl shadow-xs">
            <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-4">Payment Method Distribution</h4>

            <div class="space-y-3">
                @forelse ($paymentMethods as $pm)
                    <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-gray-900/50 border border-gray-100 dark:border-gray-700/40 flex items-center justify-between text-xs">
                        <div class="flex items-center space-x-2">
                            <span class="font-bold text-gray-900 dark:text-white uppercase">{{ $pm->payment_method }}</span>
                            <span class="text-slate-400 text-[10px]">({{ $pm->count }} checkouts)</span>
                        </div>
                        <span class="font-black text-emerald-600 font-mono">${{ number_format($pm->amount, 2) }}</span>
                    </div>
                @empty
                    <div class="py-10 text-center text-xs text-slate-400">No paid transactions logged yet.</div>
                @endforelse
            </div>
        </div>

    </div>

</div>
</x-business-layout>
