<x-admin-layout title="Manage Payments - Super Admin" heading="Payments & Transactions">
<div class="space-y-6">
    <!-- Financial Overview Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
        <div class="p-4 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-xs">
            <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">SaaS Collected Revenue</span>
            <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1 font-mono">${{ number_format($totalSaaSPaid, 2) }}</div>
        </div>
        <div class="p-4 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-xs">
            <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Pending SaaS Payments</span>
            <div class="text-2xl font-black text-amber-600 dark:text-amber-400 mt-1 font-mono">${{ number_format($totalSaaSPending, 2) }}</div>
        </div>
        <div class="p-4 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-xs">
            <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Platform Merchant GMV</span>
            <div class="text-2xl font-black text-indigo-600 dark:text-indigo-400 mt-1 font-mono">${{ number_format($totalMerchantGMV, 2) }}</div>
        </div>
        <div class="p-4 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-xs">
            <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Merchant GMV (KHR)</span>
            <div class="text-2xl font-black text-purple-600 dark:text-purple-400 mt-1 font-mono">≈ {{ number_format($totalMerchantGMVKhr, 0) }} ៛</div>
        </div>
    </div>

    <!-- Filters & Search Bar -->
    <div class="p-4 bg-white border border-gray-100 rounded-2xl shadow-xs dark:bg-gray-800 dark:border-gray-700">
        <form method="GET" action="{{ route('admin.payments.index') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-4">
            <div class="sm:col-span-2">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Search transaction reference or business name..."
                       class="w-full text-xs rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2.5 focus:border-orange-500 focus:ring-orange-500 font-medium">
            </div>

            <div>
                <select name="status" class="w-full text-xs rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2.5 focus:border-orange-500 focus:ring-orange-500 font-medium">
                    <option value="">All Payment Statuses</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed / Paid</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                    <option value="refunded" {{ request('status') === 'refunded' ? 'selected' : '' }}>Refunded</option>
                </select>
            </div>

            <div class="flex space-x-2">
                <button type="submit" class="px-4 py-2.5 bg-orange-600 hover:bg-orange-700 text-white font-bold text-xs rounded-xl shadow-xs transition grow">
                    Filter
                </button>
                @if(request()->hasAny(['search', 'status', 'payment_method']))
                    <a href="{{ route('admin.payments.index') }}" class="px-3 py-2.5 bg-slate-100 dark:bg-gray-700 text-slate-700 dark:text-slate-200 text-xs font-bold rounded-xl transition">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Payments Table -->
    <div class="overflow-hidden bg-white border border-gray-100 rounded-2xl shadow-xs dark:bg-gray-800 dark:border-gray-700">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-750">
                    <tr class="text-left text-xs font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider">
                        <th class="px-6 py-3.5">Reference #</th>
                        <th class="px-6 py-3.5">Business / Plan</th>
                        <th class="px-6 py-3.5">Amount</th>
                        <th class="px-6 py-3.5">Payment Method</th>
                        <th class="px-6 py-3.5">Date Paid</th>
                        <th class="px-6 py-3.5">Status</th>
                        <th class="px-6 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60 text-xs">
                    @forelse ($payments as $pay)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-750/50 transition">
                            <td class="px-6 py-4 font-mono font-bold text-slate-900 dark:text-white">
                                {{ $pay->transaction_reference ?? 'N/A' }}
                            </td>

                            <td class="px-6 py-4">
                                <a href="{{ route('admin.businesses.show', $pay->business_id) }}" class="font-bold text-slate-900 dark:text-white hover:text-orange-600 dark:hover:text-orange-400">
                                    {{ $pay->business->name ?? 'Unknown Business' }}
                                </a>
                                <p class="text-[11px] text-purple-600 dark:text-purple-400 font-semibold mt-0.5">
                                    Plan: {{ $pay->subscription->plan->name ?? 'SaaS Plan' }}
                                </p>
                            </td>

                            <td class="px-6 py-4 font-mono font-black text-slate-900 dark:text-white text-sm">
                                ${{ number_format($pay->amount, 2) }}
                            </td>

                            <td class="px-6 py-4">
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-slate-100 text-slate-800 dark:bg-gray-700 dark:text-slate-200">
                                    @if(str_contains($pay->payment_method, 'khqr') || str_contains($pay->payment_method, 'bakong'))
                                        🇰🇭 Bakong KHQR
                                    @elseif($pay->payment_method === 'cash')
                                        💵 Cash
                                    @else
                                        💳 {{ strtoupper($pay->payment_method) }}
                                    @endif
                                </span>
                            </td>

                            <td class="px-6 py-4 font-mono text-slate-600 dark:text-slate-300">
                                {{ $pay->paid_at ? $pay->paid_at->format('M d, Y H:i') : ($pay->created_at->format('M d, Y')) }}
                            </td>

                            <td class="px-6 py-4">
                                <x-badge :color="match($pay->status) {
                                    'completed' => 'green',
                                    'pending' => 'amber',
                                    'refunded' => 'purple',
                                    default => 'red'
                                }">
                                    {{ ucfirst($pay->status) }}
                                </x-badge>
                            </td>

                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end space-x-2">
                                    @if($pay->status !== 'completed')
                                        <form method="POST" action="{{ route('admin.payments.update-status', $pay) }}" class="inline">
                                            @csrf
                                            <input type="hidden" name="status" value="completed">
                                            <button type="submit" class="px-2.5 py-1 bg-emerald-600 text-white rounded-lg text-[11px] font-bold shadow-xs hover:bg-emerald-700 transition">
                                                Verify & Complete
                                            </button>
                                        </form>
                                    @endif

                                    @if($pay->status === 'completed')
                                        <form method="POST" action="{{ route('admin.payments.update-status', $pay) }}" class="inline" onsubmit="return confirm('Refund this payment?');">
                                            @csrf
                                            <input type="hidden" name="status" value="refunded">
                                            <button type="submit" class="px-2.5 py-1 bg-purple-50 text-purple-700 hover:bg-purple-600 hover:text-white dark:bg-purple-950/50 dark:text-purple-300 rounded-lg text-[11px] font-bold transition">
                                                Refund
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-slate-400">No payment transactions recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($payments->hasPages())
            <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">
                {{ $payments->links() }}
            </div>
        @endif
    </div>
</div>
</x-admin-layout>
