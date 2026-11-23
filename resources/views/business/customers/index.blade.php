<x-business-layout title="Customer Management" heading="Customer Management & CRM">
<div class="space-y-6">

    @if (session('status'))
        <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-xs font-bold text-emerald-800 dark:text-emerald-200">
            {{ session('status') }}
        </div>
    @endif

    <!-- Customer Metrics -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="p-5 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-2xl shadow-xs">
            <span class="text-xs font-bold text-slate-400 uppercase">Total Profiles</span>
            <p class="text-2xl font-black text-gray-900 dark:text-white mt-1">{{ number_format($stats['total_customers']) }}</p>
        </div>
        <div class="p-5 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-2xl shadow-xs">
            <span class="text-xs font-bold text-slate-400 uppercase">Lifetime Customer Spend</span>
            <p class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1">${{ number_format($stats['total_spent'], 2) }}</p>
        </div>
        <div class="p-5 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-2xl shadow-xs">
            <span class="text-xs font-bold text-slate-400 uppercase">Average Order Value</span>
            <p class="text-2xl font-black text-purple-600 dark:text-purple-400 mt-1">${{ number_format($stats['avg_order_value'], 2) }}</p>
        </div>
    </div>

    <!-- Search Bar -->
    <div class="p-4 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-2xl shadow-xs">
        <form method="GET" class="flex gap-2">
            <input type="text" name="search" value="{{ $search }}" placeholder="Search by customer name, phone, or email..." class="flex-1 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-3 py-2 text-xs text-gray-900 dark:text-white" />
            <button type="submit" class="px-5 py-2 bg-orange-600 hover:bg-orange-500 text-white rounded-xl text-xs font-bold shadow-xs">Search</button>
        </form>
    </div>

    <!-- Customers Table -->
    <div class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-2xl shadow-xs overflow-hidden">
        <table class="w-full text-left text-xs">
            <thead class="bg-slate-50 dark:bg-gray-900/60 border-b border-gray-200 dark:border-gray-700/60 text-slate-500 uppercase tracking-wider text-[10px]">
                <tr>
                    <th class="p-4">Customer</th>
                    <th class="p-4">Contact</th>
                    <th class="p-4">Orders</th>
                    <th class="p-4">Total Spent</th>
                    <th class="p-4">Loyalty Pts</th>
                    <th class="p-4 text-right">Profile</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                @forelse ($customers as $c)
                    <tr>
                        <td class="p-4 font-bold text-gray-900 dark:text-white">{{ $c->name }}</td>
                        <td class="p-4 text-slate-500">{{ $c->phone ?: ($c->email ?: 'Walk-in guest') }}</td>
                        <td class="p-4 font-bold">{{ $c->total_orders }} orders</td>
                        <td class="p-4 font-black text-emerald-600 font-mono">${{ number_format($c->total_spent, 2) }}</td>
                        <td class="p-4 font-mono text-purple-600 font-bold">{{ $c->loyalty_points }} pts</td>
                        <td class="p-4 text-right">
                            <a href="{{ route('business.customers.show', $c) }}" class="px-3 py-1 bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 hover:bg-orange-600 hover:text-white rounded-lg text-[11px] font-bold transition">
                                View History &rarr;
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="p-10 text-center text-slate-400">
                            No customers found. Guests checkout records automatically build customer profiles.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if ($customers->hasPages())
            <div class="p-4 border-t border-gray-100 dark:border-gray-700/60">
                {{ $customers->links() }}
            </div>
        @endif
    </div>

</div>
</x-business-layout>
