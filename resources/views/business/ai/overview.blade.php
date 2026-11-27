<x-business-layout title="AI Center Overview" heading="AI Center — Overview">
    <div class="max-w-7xl mx-auto space-y-6">
        @include('business.ai._nav')

        @if(! ($platformConfig['enabled'] ?? true))
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm text-rose-800 dark:border-rose-800/60 dark:bg-rose-950/30 dark:text-rose-200">
                <strong class="font-bold">AI Center Paused Platform-Wide:</strong>
                Platform administration has temporarily disabled live AI completions. Historical data and forecasts remain visible below.
            </div>
        @elseif(! ($platformConfig['gemini_key_configured'] ?? false) && ! ($platformConfig['openai_key_configured'] ?? false))
            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-900 dark:border-amber-700/60 dark:bg-amber-950/30 dark:text-amber-200">
                <strong class="font-bold">AI Provider API Key Not Yet Configured:</strong>
                Statistical inventory and sales data work normally; generative AI calls require a Google Gemini or OpenAI key set in Super Admin Platform Settings.
            </div>
        @endif

        <!-- Top Live Telemetry KPIs -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="rounded-2xl bg-white border border-slate-200 p-5 shadow-xs dark:bg-gray-800 dark:border-gray-700/60">
                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-gray-400">AI Requests This Month</p>
                <p class="mt-2 text-2xl font-black text-slate-900 dark:text-white">{{ number_format($monthlySummary['completed_requests']) }}</p>
                <p class="mt-1 text-xs text-slate-500 dark:text-gray-400">
                    {{ number_format($monthlySummary['requests_count']) }} total attempts ({{ $monthlySummary['refunded_requests'] }} refunded)
                </p>
            </div>

            <div class="rounded-2xl bg-white border border-slate-200 p-5 shadow-xs dark:bg-gray-800 dark:border-gray-700/60">
                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-gray-400">Remaining AI Credits</p>
                <p class="mt-2 text-2xl font-black text-orange-600 dark:text-orange-400">{{ number_format($monthlySummary['remaining_credits']) }}</p>
                <p class="mt-1 text-xs text-slate-500 dark:text-gray-400">
                    Used {{ number_format($monthlySummary['used_credits']) }} of {{ number_format($monthlySummary['total_credits']) }} ({{ $monthlySummary['period_ym'] }})
                </p>
            </div>

            <div class="rounded-2xl bg-white border border-slate-200 p-5 shadow-xs dark:bg-gray-800 dark:border-gray-700/60">
                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-gray-400">Tokens Processed</p>
                <p class="mt-2 text-2xl font-black text-slate-900 dark:text-white">{{ number_format($monthlySummary['total_tokens']) }}</p>
                <p class="mt-1 text-xs text-slate-500 dark:text-gray-400">
                    Est. API Cost: ${{ number_format($monthlySummary['estimated_cost_usd'], 4) }}
                </p>
            </div>

            <div class="rounded-2xl bg-white border border-slate-200 p-5 shadow-xs dark:bg-gray-800 dark:border-gray-700/60">
                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-gray-400">Inventory Warnings</p>
                <p class="mt-2 text-2xl font-black {{ $lowStockProducts->count() > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                    {{ number_format($lowStockProducts->count()) }}
                </p>
                <p class="mt-1 text-xs text-slate-500 dark:text-gray-400">
                    {{ $lowStockProducts->count() > 0 ? 'Items at or below reorder threshold' : 'All tracked items healthy' }}
                </p>
            </div>
        </div>

        <!-- AI Features Available Matrix -->
        @php
            $featureCards = [
                [
                    'key' => 'assistant',
                    'area' => 'assistant',
                    'route' => route('business.ai.assistant'),
                    'title' => 'AI Business Assistant',
                    'desc' => 'Ask questions in Khmer or English backed by live sales, menu, and stock queries.',
                    'toggle' => (bool) $featureSettings->assistant_enabled,
                ],
                [
                    'key' => 'menu_creator',
                    'area' => 'menu_creator',
                    'route' => route('business.ai.menu-creator'),
                    'title' => 'AI Menu Creator',
                    'desc' => 'Generate bilingual Khmer & English item copy, combos, and pricing recommendations.',
                    'toggle' => (bool) $featureSettings->menu_creator_enabled,
                ],
                [
                    'key' => 'marketing_studio',
                    'area' => 'marketing_studio',
                    'route' => route('business.ai.marketing'),
                    'title' => 'AI Marketing Studio',
                    'desc' => 'Craft Facebook posts, TikTok scripts, and Telegram promotions tailored to Cambodia.',
                    'toggle' => (bool) $featureSettings->marketing_studio_enabled,
                ],
                [
                    'key' => 'inventory_forecast',
                    'area' => 'inventory_forecast',
                    'route' => route('business.ai.inventory-forecast'),
                    'title' => 'AI Inventory Forecast',
                    'desc' => 'Moving-average stock depletion runway and explainable reorder alerts.',
                    'toggle' => (bool) $featureSettings->forecasting_enabled,
                ],
                [
                    'key' => 'sales_prediction',
                    'area' => 'sales_prediction',
                    'route' => route('business.ai.sales-prediction'),
                    'title' => 'AI Sales Prediction',
                    'desc' => '1-day, 7-day, and 30-day revenue outlook computed from historical transactions.',
                    'toggle' => (bool) $featureSettings->forecasting_enabled,
                ],
                [
                    'key' => 'usage',
                    'area' => 'usage',
                    'route' => route('business.ai.usage'),
                    'title' => 'Usage & Credit Ledger',
                    'desc' => 'Transparent request history, token counts, refunds, and monthly credit allocation.',
                    'toggle' => true,
                ],
            ];
        @endphp

        <div class="rounded-2xl bg-white border border-slate-200 p-5 sm:p-6 shadow-xs dark:bg-gray-800 dark:border-gray-700/60">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-sm font-bold uppercase tracking-wider text-slate-900 dark:text-white">Available AI Capabilities</h3>
                    <p class="text-xs text-slate-500 dark:text-gray-400 mt-0.5">Access reflects your subscription plan, role permissions, and store toggles.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($featureCards as $card)
                    @php
                        $planAllows = $card['key'] === 'usage' ? true : (bool) ($entitlements['features'][$card['key']] ?? false);
                        $roleAllows = (bool) ($allowedAreas[$card['area']] ?? false);
                        $active = $planAllows && $roleAllows && $card['toggle'];
                    @endphp
                    <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-4 dark:border-gray-700 dark:bg-gray-900/50 flex flex-col justify-between gap-3">
                        <div>
                            <div class="flex items-center justify-between gap-2">
                                <h4 class="text-sm font-bold text-slate-900 dark:text-white">{{ $card['title'] }}</h4>
                                @if(! $roleAllows)
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-200 text-slate-700 dark:bg-gray-700 dark:text-gray-300">Role Restricted</span>
                                @elseif(! $planAllows)
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">Upgrade Plan</span>
                                @elseif(! $card['toggle'])
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-200 text-slate-700 dark:bg-gray-700 dark:text-gray-300">Disabled</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">Ready</span>
                                @endif
                            </div>
                            <p class="text-xs text-slate-600 dark:text-gray-300 mt-1.5">{{ $card['desc'] }}</p>
                        </div>

                        @if($roleAllows)
                            <a href="{{ $card['route'] }}"
                               class="inline-flex items-center justify-center rounded-xl px-3 py-2 text-xs font-bold transition {{ $active ? 'bg-[#0F1E3A] text-white hover:bg-[#192F59]' : 'bg-white border border-slate-300 text-slate-700 hover:bg-slate-100 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-200' }}">
                                Open {{ $card['title'] }} &rarr;
                            </a>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Suggested Next Actions (100% Real DB Conditions) -->
            <div class="rounded-2xl bg-white border border-slate-200 p-5 sm:p-6 shadow-xs dark:bg-gray-800 dark:border-gray-700/60">
                <h3 class="text-sm font-bold uppercase tracking-wider text-slate-900 dark:text-white mb-4">Suggested Next Actions</h3>

                @forelse($suggestedActions as $action)
                    <div class="mb-3 last:mb-0 rounded-xl border border-slate-200 bg-slate-50/70 p-4 dark:border-gray-700 dark:bg-gray-900/50 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div>
                            <p class="text-sm font-bold text-slate-900 dark:text-white">{{ $action['title'] }}</p>
                            <p class="text-xs text-slate-600 dark:text-gray-300 mt-0.5">{{ $action['description'] }}</p>
                        </div>
                        <a href="{{ $action['route'] }}"
                           class="shrink-0 inline-flex items-center rounded-xl bg-orange-600 px-3 py-2 text-xs font-bold text-white hover:bg-orange-700 transition">
                            {{ $action['cta'] }}
                        </a>
                    </div>
                @empty
                    <div class="rounded-xl border border-dashed border-slate-200 dark:border-gray-700 p-6 text-center text-xs text-slate-500 dark:text-gray-400">
                        Your store catalog, inventory thresholds, and AI workflows look healthy. Open Business Assistant to explore deeper metrics.
                    </div>
                @endforelse
            </div>

            <!-- Live Inventory Warnings -->
            <div class="rounded-2xl bg-white border border-slate-200 p-5 sm:p-6 shadow-xs dark:bg-gray-800 dark:border-gray-700/60">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-bold uppercase tracking-wider text-slate-900 dark:text-white">Live Inventory Warnings</h3>
                    <a href="{{ route('business.inventory.index') }}" class="text-xs font-bold text-orange-600 dark:text-orange-400 hover:underline">Manage Stock &rarr;</a>
                </div>

                @forelse($lowStockProducts as $item)
                    <div class="py-2.5 border-b last:border-b-0 border-slate-100 dark:border-gray-700/60 flex items-center justify-between text-xs">
                        <div>
                            <p class="font-bold text-slate-900 dark:text-white">{{ $item->localized_name }}</p>
                            <p class="text-slate-500 dark:text-gray-400">Reorder threshold: {{ $item->low_stock_threshold }} {{ $item->unit ?? 'units' }}</p>
                        </div>
                        <span class="px-2.5 py-1 rounded-full font-bold bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300">
                            {{ rtrim(rtrim(number_format((float) $item->stock_quantity, 2), '0'), '.') }} left
                        </span>
                    </div>
                @empty
                    <div class="rounded-xl border border-dashed border-slate-200 dark:border-gray-700 p-6 text-center text-xs text-slate-500 dark:text-gray-400">
                        No products are currently at or below their low-stock threshold.
                    </div>
                @endforelse
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Latest Real Business Insights -->
            <div class="rounded-2xl bg-white border border-slate-200 p-5 sm:p-6 shadow-xs dark:bg-gray-800 dark:border-gray-700/60">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-bold uppercase tracking-wider text-slate-900 dark:text-white">Latest Business Insights</h3>
                    <a href="{{ route('business.ai.insights') }}" class="text-xs font-bold text-orange-600 dark:text-orange-400 hover:underline">View All &rarr;</a>
                </div>

                @forelse($latestInsights as $insight)
                    <div class="mb-3 last:mb-0 rounded-xl border border-slate-200 p-3.5 dark:border-gray-700">
                        <div class="flex items-center justify-between gap-2">
                            <p class="text-xs font-bold text-slate-900 dark:text-white">{{ $insight->title ?: ucwords(str_replace('_', ' ', $insight->insight_type)) }}</p>
                            <span class="text-[11px] text-slate-500 dark:text-gray-400">{{ $insight->generated_at?->diffForHumans() }}</span>
                        </div>
                        @if($insight->summary)
                            <p class="text-xs text-slate-600 dark:text-gray-300 mt-1">{{ $insight->summary }}</p>
                        @endif
                    </div>
                @empty
                    <div class="rounded-xl border border-dashed border-slate-200 dark:border-gray-700 p-6 text-center text-xs text-slate-500 dark:text-gray-400">
                        No insights generated yet. Run a report or generate insights from your real store data.
                    </div>
                @endforelse
            </div>

            <!-- Recent AI Activities -->
            <div class="rounded-2xl bg-white border border-slate-200 p-5 sm:p-6 shadow-xs dark:bg-gray-800 dark:border-gray-700/60">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-bold uppercase tracking-wider text-slate-900 dark:text-white">Recent AI Activities</h3>
                    @if($allowedAreas['usage'] ?? false)
                        <a href="{{ route('business.ai.usage') }}" class="text-xs font-bold text-orange-600 dark:text-orange-400 hover:underline">Full Audit Log &rarr;</a>
                    @endif
                </div>

                @forelse($recentUsage as $log)
                    <div class="py-2.5 border-b last:border-b-0 border-slate-100 dark:border-gray-700/60 flex items-center justify-between text-xs gap-3">
                        <div class="min-w-0">
                            <p class="font-bold text-slate-900 dark:text-white truncate">
                                {{ ucwords(str_replace('_', ' ', $log->feature)) }}
                                <span class="font-normal text-slate-500 dark:text-gray-400">· {{ $log->provider }} ({{ $log->model }})</span>
                            </p>
                            <p class="text-[11px] text-slate-500 dark:text-gray-400">
                                {{ $log->user?->name ?? 'System' }} · {{ number_format($log->total_tokens) }} tokens · {{ $log->created_at?->diffForHumans() }}
                            </p>
                        </div>
                        <span class="shrink-0 px-2 py-0.5 rounded-full text-[10px] font-bold {{ $log->status === 'completed' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300' }}">
                            {{ ucfirst($log->status) }}
                        </span>
                    </div>
                @empty
                    <div class="rounded-xl border border-dashed border-slate-200 dark:border-gray-700 p-6 text-center text-xs text-slate-500 dark:text-gray-400">
                        No AI activity recorded yet for {{ $business->name }}.
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</x-business-layout>
