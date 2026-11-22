<x-admin-layout title="Platform Analytics - Super Admin" heading="Revenue & Platform Analytics">
<div class="space-y-6">
    <!-- Top Financial Metrics -->
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-5">
        <div class="p-5 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-xs">
            <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Monthly Recurring (MRR)</span>
            <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1 font-mono">${{ number_format($mrr, 2) }}</div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Annual Run Rate (ARR): ${{ number_format($arr, 2) }}</p>
        </div>

        <div class="p-5 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-xs">
            <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total SaaS Paid Invoices</span>
            <div class="text-2xl font-black text-slate-900 dark:text-white mt-1 font-mono">${{ number_format($totalSaaSRevenue, 2) }}</div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Collected from subscriptions</p>
        </div>

        <div class="p-5 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-xs">
            <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Gross Merchant Volume (GMV)</span>
            <div class="text-2xl font-black text-indigo-600 dark:text-indigo-400 mt-1 font-mono">${{ number_format($totalMerchantGMV, 2) }}</div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Across all registered businesses</p>
        </div>

        <div class="p-5 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-xs">
            <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Platform Orders Placed</span>
            <div class="text-2xl font-black text-purple-600 dark:text-purple-400 mt-1">{{ $totalOrders }}</div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ $completedOrders }} successfully completed</p>
        </div>
    </div>

    <!-- Middle: Plan Distribution & Payment Methods Breakdown -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Plans Distribution -->
        <div class="p-6 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-xs">
            <h3 class="font-bold text-base text-slate-900 dark:text-white pb-3 border-b border-gray-100 dark:border-gray-700">
                SaaS Subscription Plans Popularity
            </h3>
            <div class="space-y-4 mt-4">
                @foreach($planBreakdown as $plan)
                    <div class="flex items-center justify-between text-xs">
                        <div>
                            <span class="font-bold text-slate-900 dark:text-white text-sm">{{ $plan->name }}</span>
                            <span class="text-slate-500 dark:text-slate-400 block mt-0.5">${{ number_format($plan->monthly_price, 2) }}/mo • ${{ number_format($plan->yearly_price, 2) }}/yr</span>
                        </div>
                        <div class="text-right">
                            <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300">
                                {{ $plan->active_subs }} active subscribers
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- SaaS Payment Methods Breakdown -->
        <div class="p-6 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-xs">
            <h3 class="font-bold text-base text-slate-900 dark:text-white pb-3 border-b border-gray-100 dark:border-gray-700">
                SaaS Invoicing Payment Channels
            </h3>
            <div class="space-y-4 mt-4">
                @forelse($saasPaymentMethods as $pay)
                    <div class="flex items-center justify-between text-xs">
                        <div class="flex items-center gap-2">
                            <span class="text-base">
                                @if(str_contains($pay->payment_method, 'khqr') || str_contains($pay->payment_method, 'bakong')) 🇰🇭
                                @elseif($pay->payment_method === 'cash') 💵
                                @else 💳
                                @endif
                            </span>
                            <div>
                                <span class="font-bold text-slate-900 dark:text-white capitalize">{{ str_replace('_', ' ', $pay->payment_method) }}</span>
                                <span class="text-slate-500 dark:text-slate-400 block">({{ $pay->count }} completed transactions)</span>
                            </div>
                        </div>
                        <span class="font-mono font-black text-sm text-slate-900 dark:text-white">
                            ${{ number_format($pay->total, 2) }}
                        </span>
                    </div>
                @empty
                    <p class="text-xs text-slate-400 italic py-4">No completed subscription payments yet.</p>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Top Performing Businesses -->
    <div class="p-6 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-xs">
        <h3 class="font-bold text-base text-slate-900 dark:text-white pb-3 border-b border-gray-100 dark:border-gray-700">
            Top Performing Businesses by Sales Volume
        </h3>

        <div class="overflow-x-auto mt-3">
            <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700 text-xs">
                <thead>
                    <tr class="text-left font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider">
                        <th class="py-3">Business</th>
                        <th class="py-3">Category</th>
                        <th class="py-3">Total Orders</th>
                        <th class="py-3 text-right">Gross GMV</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                    @foreach($topBusinesses as $biz)
                        <tr>
                            <td class="py-3 font-bold text-slate-900 dark:text-white">
                                <a href="{{ route('admin.businesses.show', $biz) }}" class="hover:text-orange-600">
                                    {{ $biz->name }}
                                </a>
                            </td>
                            <td class="py-3 capitalize text-slate-600 dark:text-slate-300">
                                {{ str_replace('_', ' ', $biz->business_type) }}
                            </td>
                            <td class="py-3 font-bold text-slate-800 dark:text-slate-200">
                                {{ $biz->orders_count }} orders
                            </td>
                            <td class="py-3 text-right font-mono font-black text-sm text-emerald-600 dark:text-emerald-400">
                                ${{ number_format($biz->total_sales, 2) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
</x-admin-layout>
