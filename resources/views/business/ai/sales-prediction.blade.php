<x-business-layout title="AI Sales Prediction" heading="AI Center — Sales Prediction">
    @php
        $payload = (array) $forecast->statistical_payload;
        $horizons = (array) ($payload['horizons'] ?? []);
        $activeHorizon = $horizons[$horizon] ?? null;
        $dailySeries = (array) ($payload['daily_series'] ?? []);
        $chartLabels = array_map(static fn (array $d): string => (string) ($d['date'] ?? ''), $dailySeries);
        $chartValues = array_map(static fn (array $d): float => (float) ($d['revenue_usd'] ?? 0), $dailySeries);
    @endphp

    <div class="max-w-7xl mx-auto space-y-6" x-data="sabayraSalesPredictionComponent()">
        @include('business.ai._nav')

        <!-- Horizon Controls & Actions -->
        <div class="rounded-2xl bg-white border border-slate-200 p-5 shadow-xs dark:bg-gray-800 dark:border-gray-700/60 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h3 class="text-sm font-bold uppercase tracking-wider text-slate-900 dark:text-white">Revenue &amp; Order Demand Outlook</h3>
                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold {{ match($forecast->confidence_level) { 'high' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300', 'medium' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300', 'low' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300', default => 'bg-slate-200 text-slate-700 dark:bg-gray-700 dark:text-gray-300' } }}">
                        Confidence: {{ ucfirst($forecast->confidence_level) }}
                    </span>
                </div>
                <p class="text-xs text-slate-500 dark:text-gray-400 mt-1">
                    Analyzed {{ number_format($forecast->data_points_count) }} non-cancelled order(s) across {{ $forecast->historical_days_available }} day(s)
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                @foreach(['1d' => 'Next 1 Day', '7d' => 'Next 7 Days', '30d' => 'Next 30 Days'] as $hKey => $hLabel)
                    <a href="{{ route('business.ai.sales-prediction', ['horizon' => $hKey]) }}"
                       class="rounded-xl px-3 py-1.5 text-xs font-bold border transition {{ $horizon === $hKey ? 'bg-[#0F1E3A] text-white border-[#0F1E3A]' : 'bg-white text-slate-700 border-slate-300 hover:bg-slate-50 dark:bg-gray-900 dark:text-gray-200 dark:border-gray-700' }}">
                        {{ $hLabel }}
                    </a>
                @endforeach

                <button type="button" @click="runPrediction(false)" :disabled="loading"
                        class="rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-xs font-bold text-slate-800 hover:bg-slate-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 transition cursor-pointer disabled:opacity-50">
                    Refresh Stats (Free)
                </button>
                <button type="button" @click="runPrediction(true)" :disabled="loading || status === 'insufficient_data'"
                        class="rounded-xl bg-orange-600 px-4 py-2 text-xs font-bold text-white hover:bg-orange-700 transition cursor-pointer disabled:opacity-50">
                    <span x-text="loading ? 'Analyzing...' : '✨ Explain Drivers (2 Credits)'"></span>
                </button>
            </div>
        </div>

        <div x-show="errorMessage" x-cloak class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-xs font-medium text-rose-800 dark:border-rose-800/60 dark:bg-rose-950/30 dark:text-rose-200" x-text="errorMessage"></div>

        @if($forecast->limitations)
            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-xs text-amber-900 dark:border-amber-700/60 dark:bg-amber-950/30 dark:text-amber-200">
                <strong class="font-bold uppercase tracking-wider">Data Sufficiency Notice:</strong>
                {{ $forecast->limitations }}
            </div>
        @endif

        @if($forecast->status === 'ready' && $activeHorizon)
            <!-- Forecast Horizon Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="rounded-2xl bg-white border border-slate-200 p-5 shadow-xs dark:bg-gray-800 dark:border-gray-700/60">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-gray-400">Expected Revenue ({{ strtoupper($horizon) }})</p>
                    <p class="mt-2 text-2xl font-black text-orange-600 dark:text-orange-400">${{ number_format((float) $activeHorizon['expected_revenue_usd'], 2) }}</p>
                    <p class="mt-1 text-xs text-slate-500 dark:text-gray-400">~{{ number_format((float) $activeHorizon['expected_revenue_khr']) }} ៛</p>
                </div>

                <div class="rounded-2xl bg-white border border-slate-200 p-5 shadow-xs dark:bg-gray-800 dark:border-gray-700/60">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-gray-400">Conservative Range (&plusmn;15%)</p>
                    <p class="mt-2 text-lg font-black text-slate-900 dark:text-white">
                        ${{ number_format((float) $activeHorizon['low_estimate_usd'], 2) }} – ${{ number_format((float) $activeHorizon['high_estimate_usd'], 2) }}
                    </p>
                    <p class="mt-1 text-xs text-slate-500 dark:text-gray-400">Projected orders: ~{{ number_format((int) $activeHorizon['expected_orders']) }}</p>
                </div>

                <div class="rounded-2xl bg-white border border-slate-200 p-5 shadow-xs dark:bg-gray-800 dark:border-gray-700/60">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-gray-400">Recent 7d Daily Avg</p>
                    <p class="mt-2 text-2xl font-black text-slate-900 dark:text-white">${{ number_format((float) ($payload['avg_daily_revenue_7d'] ?? 0), 2) }}</p>
                    <p class="mt-1 text-xs text-slate-500 dark:text-gray-400">Overall avg: ${{ number_format((float) ($payload['avg_daily_revenue_overall'] ?? 0), 2) }}/day</p>
                </div>

                <div class="rounded-2xl bg-white border border-slate-200 p-5 shadow-xs dark:bg-gray-800 dark:border-gray-700/60">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-gray-400">Demand Momentum</p>
                    <p class="mt-2 text-2xl font-black {{ ((float) ($payload['trend_multiplier'] ?? 1.0)) >= 1.0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400' }}">
                        {{ number_format((float) ($payload['trend_multiplier'] ?? 1.0), 2) }}x
                    </p>
                    <p class="mt-1 text-xs text-slate-500 dark:text-gray-400">Observed: ${{ number_format((float) ($payload['observed_revenue_usd'] ?? 0), 2) }}</p>
                </div>
            </div>
        @endif

        @if($forecast->ai_explanation)
            <div class="rounded-2xl bg-[#0F1E3A] text-white p-5 sm:p-6 shadow-xs border border-[#1E3560] space-y-2">
                <p class="text-[11px] font-bold uppercase tracking-wider text-amber-300">✨ AI Demand &amp; Staffing Insights (Grounded in Verified Revenue Series)</p>
                <div class="text-sm text-slate-100 whitespace-pre-line leading-relaxed">{{ $forecast->ai_explanation }}</div>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Chart -->
            <div class="lg:col-span-8 rounded-2xl bg-white border border-slate-200 p-5 sm:p-6 shadow-xs dark:bg-gray-800 dark:border-gray-700/60 space-y-3">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900 dark:text-white">Observed Daily Revenue History (USD)</h3>
                @if($chartLabels !== [])
                    <div class="h-64">
                        <canvas id="sabayraSalesHistoryChart"></canvas>
                    </div>
                @else
                    <div class="rounded-xl border border-dashed border-slate-200 dark:border-gray-700 p-8 text-center text-xs text-slate-500 dark:text-gray-400">
                        Not enough historical order dates to chart yet.
                    </div>
                @endif
            </div>

            <!-- Seasonality & Top Categories -->
            <div class="lg:col-span-4 rounded-2xl bg-white border border-slate-200 p-5 sm:p-6 shadow-xs dark:bg-gray-800 dark:border-gray-700/60 space-y-4">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900 dark:text-white">Category Revenue Leaders</h3>
                @forelse((array) ($payload['top_categories'] ?? []) as $cat)
                    <div class="flex items-center justify-between text-xs py-1.5 border-b last:border-b-0 border-slate-100 dark:border-gray-700/60">
                        <span class="font-semibold text-slate-800 dark:text-gray-200">{{ $cat['category'] }}</span>
                        <span class="font-bold text-slate-900 dark:text-white">${{ number_format((float) $cat['revenue_usd'], 2) }}</span>
                    </div>
                @empty
                    <p class="text-xs text-slate-500 dark:text-gray-400">No category revenue breakdown available yet.</p>
                @endforelse

                @if(is_array($forecast->assumptions_json) && $forecast->assumptions_json !== [])
                    <div class="pt-3 border-t border-slate-100 dark:border-gray-700/60 text-[11px] text-slate-500 dark:text-gray-400 space-y-1">
                        <p class="font-bold uppercase tracking-wider text-slate-600 dark:text-gray-300">Methodology</p>
                        @foreach($forecast->assumptions_json as $assumption)
                            <p>&bull; {{ $assumption }}</p>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script>
        function sabayraSalesPredictionComponent() {
            return {
                horizon: {{ \Illuminate\Support\Js::from($horizon) }},
                status: {{ \Illuminate\Support\Js::from($forecast->status) }},
                loading: false,
                errorMessage: '',
                async runPrediction(withAi) {
                    if (this.loading) return;
                    this.loading = true;
                    this.errorMessage = '';
                    const requestUuid = (window.crypto && window.crypto.randomUUID)
                        ? window.crypto.randomUUID()
                        : ('salesfc_' + Date.now() + '_' + Math.random().toString(16).slice(2));

                    try {
                        const res = await fetch({{ \Illuminate\Support\Js::from(route('business.ai.sales-prediction.run')) }}, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                            },
                            body: JSON.stringify({
                                horizon: this.horizon,
                                with_ai: withAi,
                                request_uuid: requestUuid,
                            }),
                        });
                        const data = await res.json();
                        if (!res.ok || !data.ok) {
                            this.errorMessage = data.message || 'Unable to compute sales prediction.';
                            return;
                        }
                        window.location.reload();
                    } catch (err) {
                        this.errorMessage = 'Network error computing sales prediction.';
                    } finally {
                        this.loading = false;
                    }
                },
            };
        }

        document.addEventListener('DOMContentLoaded', function () {
            const canvas = document.getElementById('sabayraSalesHistoryChart');
            const labels = {{ \Illuminate\Support\Js::from($chartLabels) }};
            const values = {{ \Illuminate\Support\Js::from($chartValues) }};
            if (canvas && labels.length > 0 && typeof window.Chart !== 'undefined') {
                new window.Chart(canvas, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Daily Revenue (USD)',
                            data: values,
                            borderColor: '#ea580c',
                            backgroundColor: 'rgba(234, 88, 12, 0.14)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.3,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: { beginAtZero: true },
                        },
                    },
                });
            }
        });
    </script>
</x-business-layout>
