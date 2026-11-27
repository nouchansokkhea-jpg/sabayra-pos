<x-business-layout title="AI Weekly Insights" heading="SABAYRA AI Weekly Insights">
<div class="max-w-7xl mx-auto space-y-6" x-data="sabayraInsightsComponent()">
    @if(isset($monthlySummary) && isset($allowedAreas))
        @include('business.ai._nav')
    @endif

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 dark:border-emerald-800/60 dark:bg-emerald-950/30 dark:text-emerald-300">
            {{ session('success') }}
        </div>
    @endif

    <div class="p-5 bg-gradient-to-r from-[#0F1E3A] via-gray-900 to-orange-950/40 border border-amber-500/30 rounded-2xl shadow-xs text-white flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center space-x-3">
            <span class="text-3xl">💡</span>
            <div>
                <h3 class="text-base font-black text-white">SABAYRA AI Business Intelligence</h3>
                <p class="text-xs text-slate-300 mt-1">Weekly summaries synthesized strictly from {{ $business->name }}'s verified sales volume, order trends, product velocity, and inventory alerts.</p>
            </div>
        </div>
        <button type="button" @click="generateBrief()" :disabled="loading"
                class="shrink-0 rounded-xl bg-orange-600 px-4 py-2.5 text-xs font-bold text-white hover:bg-orange-700 transition cursor-pointer disabled:opacity-50">
            <span x-text="loading ? 'Generating Brief...' : '✨ Generate Persistent AI Brief (2 Credits)'"></span>
        </button>
    </div>

    <div x-show="errorMessage" x-cloak class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-xs font-medium text-rose-800 dark:border-rose-800/60 dark:bg-rose-950/30 dark:text-rose-200" x-text="errorMessage"></div>

    <!-- Summary Metrics -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="p-5 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-2xl shadow-xs">
            <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase">This Week's Revenue</span>
            <p class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1">${{ number_format($insights['this_week_revenue'], 2) }}</p>
            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Growth: <strong class="{{ $insights['revenue_growth_percent'] >= 0 ? 'text-emerald-500' : 'text-red-500' }}">{{ $insights['revenue_growth_percent'] }}%</strong></p>
        </div>
        <div class="p-5 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-2xl shadow-xs">
            <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase">Weekly Orders</span>
            <p class="text-2xl font-black text-gray-900 dark:text-white mt-1">{{ $insights['this_week_orders'] }}</p>
            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Last week: {{ $insights['last_week_orders'] }} orders</p>
        </div>
        <div class="p-5 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-2xl shadow-xs">
            <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase">New Customers Acquired</span>
            <p class="text-2xl font-black text-purple-600 dark:text-purple-400 mt-1">{{ $insights['new_customers'] }}</p>
            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Total customer base: {{ $insights['total_customers'] }}</p>
        </div>
    </div>

    <!-- Live Prioritized Recommendations -->
    <div class="p-6 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-2xl shadow-xs space-y-4">
        <h4 class="text-sm font-bold text-gray-900 dark:text-white">Live Weekly Observations</h4>

        <div class="space-y-3">
            @foreach ($insights['recommendations'] as $rec)
                <div class="p-4 rounded-2xl bg-slate-50 dark:bg-gray-900/50 border border-gray-200/80 dark:border-gray-700/50 text-xs space-y-1">
                    <div class="flex items-center justify-between">
                        <span class="font-bold text-sm text-gray-900 dark:text-white flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full {{ $rec['priority'] === 'high' ? 'bg-red-500' : ($rec['priority'] === 'medium' ? 'bg-amber-500' : 'bg-emerald-500') }}"></span>
                            {{ $rec['title'] }}
                        </span>
                        <span class="text-[10px] font-mono font-bold uppercase text-slate-500 dark:text-slate-400">{{ $rec['priority'] }} Priority</span>
                    </div>
                    <p class="text-slate-600 dark:text-slate-300 leading-relaxed pt-1">{{ $rec['description'] }}</p>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Saved & Manageable Persistent Insights -->
    @if(isset($persistedInsights))
        <div class="p-6 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-2xl shadow-xs space-y-4">
            <h4 class="text-sm font-bold text-gray-900 dark:text-white">Saved AI Business Insights Queue</h4>

            @forelse($persistedInsights as $item)
                <div class="p-4 rounded-2xl border border-slate-200 dark:border-gray-700 bg-slate-50/70 dark:bg-gray-900/60 space-y-2 text-xs">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-sm text-slate-900 dark:text-white">{{ $item->title ?: ucwords(str_replace('_', ' ', $item->insight_type)) }}</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase {{ match($item->status ?? 'new') { 'applied' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300', 'reviewed' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300', 'dismissed' => 'bg-slate-200 text-slate-600 dark:bg-gray-700 dark:text-gray-400', default => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300' } }}">
                                {{ ucfirst($item->status ?? 'new') }}
                            </span>
                        </div>
                        <span class="text-[11px] text-slate-500 dark:text-gray-400">
                            {{ $item->generated_at?->diffForHumans() }}
                            @if($item->reviewedBy)
                                · Reviewed by {{ $item->reviewedBy->name }}
                            @endif
                        </span>
                    </div>
                    @if($item->summary)
                        <p class="text-slate-700 dark:text-gray-200 leading-relaxed whitespace-pre-line">{{ $item->summary }}</p>
                    @endif
                    @if($item->recommended_action)
                        <p class="text-orange-700 dark:text-orange-300 font-semibold">Action: {{ $item->recommended_action }}</p>
                    @endif
                    <div class="pt-2 flex flex-wrap gap-2">
                        @foreach(['reviewed' => 'Mark Reviewed', 'applied' => 'Mark Applied', 'dismissed' => 'Dismiss'] as $statusKey => $btnLabel)
                            @if(($item->status ?? 'new') !== $statusKey)
                                <form method="POST" action="{{ route('business.ai.insights.status', $item) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="status" value="{{ $statusKey }}">
                                    <button type="submit" class="rounded-lg border border-slate-300 bg-white px-2.5 py-1 text-[11px] font-bold text-slate-700 hover:bg-slate-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 cursor-pointer">
                                        {{ $btnLabel }}
                                    </button>
                                </form>
                            @endif
                        @endforeach
                    </div>
                </div>
            @empty
                <p class="text-xs text-slate-500 dark:text-gray-400">No persistent insights saved yet. Click "Generate Persistent AI Brief" above to save an executive summary.</p>
            @endforelse
        </div>
    @endif
</div>

<script>
    function sabayraInsightsComponent() {
        return {
            loading: false,
            errorMessage: '',
            async generateBrief() {
                if (this.loading) return;
                this.loading = true;
                this.errorMessage = '';
                const requestUuid = (window.crypto && window.crypto.randomUUID)
                    ? window.crypto.randomUUID()
                    : ('ins_' + Date.now() + '_' + Math.random().toString(16).slice(2));

                try {
                    const res = await fetch({{ \Illuminate\Support\Js::from(route('business.ai.insights.generate')) }}, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                        },
                        body: JSON.stringify({ with_ai: true, request_uuid: requestUuid }),
                    });
                    const data = await res.json();
                    if (!res.ok || !data.ok) {
                        this.errorMessage = data.message || 'Unable to generate AI insight right now.';
                        return;
                    }
                    window.location.reload();
                } catch (err) {
                    this.errorMessage = 'Network error generating insight.';
                } finally {
                    this.loading = false;
                }
            },
        };
    }
</script>
</x-business-layout>
