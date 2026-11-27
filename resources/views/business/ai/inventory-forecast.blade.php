<x-business-layout title="AI Inventory Forecast" heading="AI Center — Inventory Forecast">
    <div class="max-w-7xl mx-auto space-y-6" x-data="sabayraInventoryForecastComponent()">
        @include('business.ai._nav')

        <!-- Action Toolbar & Summary Cards -->
        <div class="rounded-2xl bg-white border border-slate-200 p-5 shadow-xs dark:bg-gray-800 dark:border-gray-700/60 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h3 class="text-sm font-bold uppercase tracking-wider text-slate-900 dark:text-white">Stock Runway &amp; Reorder Intelligence</h3>
                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold {{ match($forecast->confidence_level) { 'high' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300', 'medium' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300', 'low' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300', default => 'bg-slate-200 text-slate-700 dark:bg-gray-700 dark:text-gray-300' } }}">
                        Confidence: {{ ucfirst($forecast->confidence_level) }}
                    </span>
                </div>
                <p class="text-xs text-slate-500 dark:text-gray-400 mt-1">
                    Computed from {{ number_format($forecast->data_points_count) }} stock consumption events across {{ $forecast->historical_days_available }} day(s) ·
                    Updated {{ $forecast->generated_at?->diffForHumans() }}
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2.5">
                <button type="button" @click="runForecast(false)" :disabled="loading"
                        class="rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-xs font-bold text-slate-800 hover:bg-slate-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 transition cursor-pointer disabled:opacity-50">
                    Refresh Math (Free)
                </button>
                <button type="button" @click="runForecast(true)" :disabled="loading || status === 'insufficient_data'"
                        class="rounded-xl bg-orange-600 px-4 py-2 text-xs font-bold text-white hover:bg-orange-700 transition cursor-pointer disabled:opacity-50">
                    <span x-text="loading ? 'Analyzing...' : '✨ Explain &amp; Prioritize (2 Credits)'"></span>
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

        @if($forecast->ai_explanation)
            <div class="rounded-2xl bg-[#0F1E3A] text-white p-5 sm:p-6 shadow-xs border border-[#1E3560] space-y-2">
                <p class="text-[11px] font-bold uppercase tracking-wider text-amber-300">✨ AI Procurement Summary (Grounded in Verified Math)</p>
                <div class="text-sm text-slate-100 whitespace-pre-line leading-relaxed">{{ $forecast->ai_explanation }}</div>
            </div>
        @endif

        @php
            $items = (array) ($forecast->statistical_payload['items'] ?? []);
        @endphp

        <!-- Forecast Table -->
        <div class="rounded-2xl bg-white border border-slate-200 p-5 sm:p-6 shadow-xs dark:bg-gray-800 dark:border-gray-700/60">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-gray-700 text-xs">
                    <thead>
                        <tr class="text-left font-bold uppercase tracking-wider text-slate-500 dark:text-gray-400">
                            <th class="py-3 pr-4">Item / Ingredient</th>
                            <th class="py-3 pr-4">Type</th>
                            <th class="py-3 pr-4 text-right">Current Stock</th>
                            <th class="py-3 pr-4 text-right">30d Usage</th>
                            <th class="py-3 pr-4 text-right">Weighted Daily</th>
                            <th class="py-3 pr-4 text-right">Est. Runway</th>
                            <th class="py-3 pr-4 text-right">Suggested Reorder</th>
                            <th class="py-3">Risk</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-gray-700/60">
                        @forelse($items as $row)
                            <tr>
                                <td class="py-3 pr-4 font-bold text-slate-900 dark:text-white">
                                    {{ $row['name_en'] }}
                                    @if(! empty($row['name_km']))
                                        <span class="block text-[11px] font-normal text-slate-500 dark:text-gray-400">{{ $row['name_km'] }}</span>
                                    @endif
                                </td>
                                <td class="py-3 pr-4 capitalize text-slate-600 dark:text-gray-300">{{ $row['source'] }}</td>
                                <td class="py-3 pr-4 text-right font-mono text-slate-900 dark:text-white">
                                    {{ number_format((float) $row['current_stock'], 2) }} {{ $row['unit'] }}
                                </td>
                                <td class="py-3 pr-4 text-right font-mono text-slate-600 dark:text-gray-300">
                                    {{ number_format((float) $row['total_consumed_30d'], 2) }}
                                </td>
                                <td class="py-3 pr-4 text-right font-mono text-slate-600 dark:text-gray-300">
                                    {{ number_format((float) $row['weighted_daily_usage'], 2) }} / d
                                </td>
                                <td class="py-3 pr-4 text-right font-bold text-slate-900 dark:text-white">
                                    {{ $row['estimated_days_remaining'] !== null ? number_format((float) $row['estimated_days_remaining'], 1) . ' days' : '—' }}
                                </td>
                                <td class="py-3 pr-4 text-right font-bold text-orange-600 dark:text-orange-400">
                                    {{ number_format((float) $row['recommended_reorder_qty'], 2) }} {{ $row['unit'] }}
                                    @if($row['estimated_reorder_cost_usd'] !== null)
                                        <span class="block text-[10px] font-normal text-slate-500 dark:text-gray-400">~${{ number_format((float) $row['estimated_reorder_cost_usd'], 2) }}</span>
                                    @endif
                                </td>
                                <td class="py-3">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ match($row['risk_level']) { 'critical' => 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300', 'warning' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300', default => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300' } }}">
                                        {{ ucfirst($row['risk_level']) }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-8 text-center text-slate-500 dark:text-gray-400">
                                    No inventory items or inventory-tracked products found yet. Add inventory items to track stock runway automatically.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if(is_array($forecast->assumptions_json) && $forecast->assumptions_json !== [])
                <div class="mt-5 pt-4 border-t border-slate-100 dark:border-gray-700/60 text-[11px] text-slate-500 dark:text-gray-400 space-y-1">
                    <p class="font-bold uppercase tracking-wider text-slate-600 dark:text-gray-300">Forecast Methodology &amp; Assumptions</p>
                    @foreach($forecast->assumptions_json as $assumption)
                        <p>&bull; {{ $assumption }}</p>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <script>
        function sabayraInventoryForecastComponent() {
            return {
                status: {{ \Illuminate\Support\Js::from($forecast->status) }},
                loading: false,
                errorMessage: '',
                async runForecast(withAi) {
                    if (this.loading) return;
                    this.loading = true;
                    this.errorMessage = '';
                    const requestUuid = (window.crypto && window.crypto.randomUUID)
                        ? window.crypto.randomUUID()
                        : ('invfc_' + Date.now() + '_' + Math.random().toString(16).slice(2));

                    try {
                        const res = await fetch({{ \Illuminate\Support\Js::from(route('business.ai.inventory-forecast.run')) }}, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                            },
                            body: JSON.stringify({ with_ai: withAi, request_uuid: requestUuid }),
                        });
                        const data = await res.json();
                        if (!res.ok || !data.ok) {
                            this.errorMessage = data.message || 'Unable to compute inventory forecast.';
                            return;
                        }
                        window.location.reload();
                    } catch (err) {
                        this.errorMessage = 'Network error running inventory forecast.';
                    } finally {
                        this.loading = false;
                    }
                },
            };
        }
    </script>
</x-business-layout>
