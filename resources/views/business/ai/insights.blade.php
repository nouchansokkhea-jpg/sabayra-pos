<x-business-layout title="AI Weekly Insights" heading="SABAYRA AI Weekly Insights">
<div class="space-y-6">

    <div class="p-5 bg-gradient-to-r from-amber-950/40 via-gray-900 to-orange-950/30 border border-amber-500/30 rounded-2xl shadow-xs text-white">
        <div class="flex items-center space-x-3">
            <span class="text-3xl">💡</span>
            <div>
                <h3 class="text-base font-black text-white">SABAYRA AI Business Intelligence</h3>
                <p class="text-xs text-slate-300 mt-1">Algorithmic weekly summaries synthesized from sales volume, order trends, product velocity, and inventory alerts.</p>
            </div>
        </div>
    </div>

    <!-- Summary Metrics -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="p-5 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-2xl shadow-xs">
            <span class="text-xs font-bold text-slate-400 uppercase">This Week's Revenue</span>
            <p class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1">${{ number_format($insights['this_week_revenue'], 2) }}</p>
            <p class="text-[11px] text-slate-400 mt-1">Growth: <strong class="{{ $insights['revenue_growth_percent'] >= 0 ? 'text-emerald-500' : 'text-red-500' }}">{{ $insights['revenue_growth_percent'] }}%</strong></p>
        </div>
        <div class="p-5 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-2xl shadow-xs">
            <span class="text-xs font-bold text-slate-400 uppercase">Weekly Orders</span>
            <p class="text-2xl font-black text-gray-900 dark:text-white mt-1">{{ $insights['this_week_orders'] }}</p>
            <p class="text-[11px] text-slate-400 mt-1">Last week: {{ $insights['last_week_orders'] }} orders</p>
        </div>
        <div class="p-5 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-2xl shadow-xs">
            <span class="text-xs font-bold text-slate-400 uppercase">New Customers Acquired</span>
            <p class="text-2xl font-black text-purple-600 dark:text-purple-400 mt-1">{{ $insights['new_customers'] }}</p>
            <p class="text-[11px] text-slate-400 mt-1">Total customer base: {{ $insights['total_customers'] }}</p>
        </div>
    </div>

    <!-- Prioritized Recommendations -->
    <div class="p-6 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-2xl shadow-xs space-y-4">
        <h4 class="text-sm font-bold text-gray-900 dark:text-white">Actionable Executive Recommendations</h4>

        <div class="space-y-3">
            @foreach ($insights['recommendations'] as $rec)
                <div class="p-4 rounded-2xl bg-slate-50 dark:bg-gray-900/50 border border-gray-200/80 dark:border-gray-700/50 text-xs space-y-1">
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-sm text-gray-900 dark:text-white flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full {{ $rec['priority'] === 'high' ? 'bg-red-500' : ($rec['priority'] === 'medium' ? 'bg-amber-500' : 'bg-emerald-500') }}"></span>
                            {{ $rec['title'] }}
                        </span>
                        <span class="text-[10px] font-mono font-bold uppercase text-slate-400">{{ $rec['priority'] }} Priority</span>
                    </div>
                    <p class="text-slate-600 dark:text-slate-300 leading-relaxed pt-1">{{ $rec['description'] }}</p>
                </div>
            @endforeach
        </div>
    </div>

</div>
</x-business-layout>
