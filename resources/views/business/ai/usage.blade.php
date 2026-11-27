<x-business-layout title="AI Usage & History" heading="AI Center — Usage & Credit History">
    <div class="max-w-7xl mx-auto space-y-6">
        @include('business.ai._nav')

        <!-- Monthly Credit Ledger Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
            <div class="rounded-2xl bg-white border border-slate-200 p-4 shadow-xs dark:bg-gray-800 dark:border-gray-700/60">
                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-gray-400">Period</p>
                <p class="mt-1 text-xl font-black text-slate-900 dark:text-white">{{ $monthlySummary['period_ym'] }}</p>
            </div>
            <div class="rounded-2xl bg-white border border-slate-200 p-4 shadow-xs dark:bg-gray-800 dark:border-gray-700/60">
                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-gray-400">Allocated + Bonus</p>
                <p class="mt-1 text-xl font-black text-slate-900 dark:text-white">{{ number_format($monthlySummary['total_credits']) }}</p>
            </div>
            <div class="rounded-2xl bg-white border border-slate-200 p-4 shadow-xs dark:bg-gray-800 dark:border-gray-700/60">
                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-gray-400">Used Credits</p>
                <p class="mt-1 text-xl font-black text-slate-900 dark:text-white">{{ number_format($monthlySummary['used_credits']) }}</p>
            </div>
            <div class="rounded-2xl bg-white border border-slate-200 p-4 shadow-xs dark:bg-gray-800 dark:border-gray-700/60">
                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-gray-400">Remaining</p>
                <p class="mt-1 text-xl font-black text-orange-600 dark:text-orange-400">{{ number_format($monthlySummary['remaining_credits']) }}</p>
            </div>
            <div class="rounded-2xl bg-white border border-slate-200 p-4 shadow-xs dark:bg-gray-800 dark:border-gray-700/60 col-span-2 lg:col-span-1">
                <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-gray-400">Est. Token Cost</p>
                <p class="mt-1 text-xl font-black text-emerald-600 dark:text-emerald-400">${{ number_format($monthlySummary['estimated_cost_usd'], 4) }}</p>
            </div>
        </div>

        <!-- Filters & Audit Table -->
        <div class="rounded-2xl bg-white border border-slate-200 p-5 sm:p-6 shadow-xs dark:bg-gray-800 dark:border-gray-700/60 space-y-4">
            <form method="GET" action="{{ route('business.ai.usage') }}" class="flex flex-wrap items-end gap-3">
                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-gray-300 mb-1">Feature</label>
                    <select name="feature"
                            class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs text-slate-900 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                        <option value="">All Features</option>
                        @foreach(['assistant' => 'Business Assistant', 'menu_creator' => 'Menu Creator', 'marketing_studio' => 'Marketing Studio', 'inventory_forecast' => 'Inventory Forecast', 'sales_prediction' => 'Sales Prediction', 'insights' => 'Business Insights'] as $key => $label)
                            <option value="{{ $key }}" @selected($featureFilter === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 dark:text-gray-300 mb-1">Status</label>
                    <select name="status"
                            class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs text-slate-900 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                        <option value="">All Statuses</option>
                        <option value="completed" @selected($statusFilter === 'completed')>Completed</option>
                        <option value="refunded" @selected($statusFilter === 'refunded')>Refunded</option>
                        <option value="pending" @selected($statusFilter === 'pending')>Pending</option>
                    </select>
                </div>

                <button type="submit" class="rounded-xl bg-[#0F1E3A] px-4 py-2 text-xs font-bold text-white hover:bg-[#192F59] transition cursor-pointer">
                    Apply Filter
                </button>
                @if($featureFilter !== '' || $statusFilter !== '')
                    <a href="{{ route('business.ai.usage') }}" class="rounded-xl border border-slate-300 px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 dark:border-gray-700 dark:text-gray-300">
                        Reset
                    </a>
                @endif
            </form>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 dark:divide-gray-700 text-xs">
                    <thead>
                        <tr class="text-left font-bold uppercase tracking-wider text-slate-500 dark:text-gray-400">
                            <th class="py-3 pr-4">Timestamp</th>
                            <th class="py-3 pr-4">Feature</th>
                            <th class="py-3 pr-4">Provider / Model</th>
                            <th class="py-3 pr-4">User</th>
                            <th class="py-3 pr-4 text-right">Tokens</th>
                            <th class="py-3 pr-4 text-right">Credits</th>
                            <th class="py-3 pr-4 text-right">Latency</th>
                            <th class="py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-gray-700/60">
                        @forelse($usageLogs as $log)
                            <tr>
                                <td class="py-3 pr-4 whitespace-nowrap text-slate-600 dark:text-gray-300">
                                    {{ $log->created_at?->format('Y-m-d H:i') }}
                                </td>
                                <td class="py-3 pr-4 font-bold text-slate-900 dark:text-white">
                                    {{ ucwords(str_replace('_', ' ', $log->feature)) }}
                                </td>
                                <td class="py-3 pr-4 text-slate-600 dark:text-gray-300">
                                    {{ ucfirst($log->provider) }} · <span class="font-mono text-[11px]">{{ $log->model }}</span>
                                </td>
                                <td class="py-3 pr-4 text-slate-600 dark:text-gray-300">
                                    {{ $log->user?->name ?? 'System' }}
                                </td>
                                <td class="py-3 pr-4 text-right font-mono text-slate-800 dark:text-gray-200">
                                    {{ number_format($log->total_tokens) }}
                                </td>
                                <td class="py-3 pr-4 text-right font-bold text-slate-900 dark:text-white">
                                    {{ $log->status === 'refunded' ? '0 (refunded)' : $log->credits_charged }}
                                </td>
                                <td class="py-3 pr-4 text-right text-slate-600 dark:text-gray-300">
                                    {{ $log->latency_ms !== null ? $log->latency_ms . 'ms' : '—' }}
                                </td>
                                <td class="py-3">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $log->status === 'completed' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300' }}">
                                        {{ ucfirst($log->status) }}
                                    </span>
                                    @if($log->error_message)
                                        <p class="text-[11px] text-rose-600 dark:text-rose-400 mt-0.5 max-w-xs truncate" title="{{ $log->error_message }}">{{ $log->error_message }}</p>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-8 text-center text-slate-500 dark:text-gray-400">
                                    No AI request logs match your current filters.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div>
                {{ $usageLogs->links() }}
            </div>
        </div>
    </div>
</x-business-layout>
