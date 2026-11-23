<x-business-layout title="Analytics" heading="Business Intelligence & Analytics">
<div class="space-y-6">

    <div class="p-4 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-2xl shadow-xs">
        <h3 class="text-sm font-bold text-gray-900 dark:text-white">Commercial Velocity & Traffic Analytics</h3>
        <p class="text-xs text-slate-400">Peak dining hours, category revenue distribution, and checkout preferences.</p>
    </div>

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
