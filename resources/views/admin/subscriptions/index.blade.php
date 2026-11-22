<x-admin-layout title="Manage Subscriptions - Super Admin" heading="Manage Subscriptions">
<div class="space-y-6">
    <!-- Header & Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
        <div class="p-4 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-xs">
            <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Subscriptions</span>
            <div class="text-2xl font-black text-slate-900 dark:text-white mt-1">{{ $stats['total'] }}</div>
        </div>
        <div class="p-4 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-xs">
            <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Active</span>
            <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1">{{ $stats['active'] }}</div>
        </div>
        <div class="p-4 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-xs">
            <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Expired</span>
            <div class="text-2xl font-black text-amber-600 dark:text-amber-400 mt-1">{{ $stats['expired'] }}</div>
        </div>
        <div class="p-4 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-xs">
            <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Cancelled</span>
            <div class="text-2xl font-black text-rose-600 dark:text-rose-400 mt-1">{{ $stats['cancelled'] }}</div>
        </div>
    </div>

    <!-- Filters & Search Bar -->
    <div class="p-4 bg-white border border-gray-100 rounded-2xl shadow-xs dark:bg-gray-800 dark:border-gray-700">
        <form method="GET" action="{{ route('admin.subscriptions.index') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-4">
            <div class="sm:col-span-2">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Search by business name or slug..."
                       class="w-full text-xs rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2.5 focus:border-orange-500 focus:ring-orange-500 font-medium">
            </div>

            <div>
                <select name="status" class="w-full text-xs rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2.5 focus:border-orange-500 focus:ring-orange-500 font-medium">
                    <option value="">All Statuses</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>Expired</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                </select>
            </div>

            <div class="flex space-x-2">
                <button type="submit" class="px-4 py-2.5 bg-orange-600 hover:bg-orange-700 text-white font-bold text-xs rounded-xl shadow-xs transition grow">
                    Filter
                </button>
                @if(request()->hasAny(['search', 'status', 'billing_cycle']))
                    <a href="{{ route('admin.subscriptions.index') }}" class="px-3 py-2.5 bg-slate-100 dark:bg-gray-700 text-slate-700 dark:text-slate-200 text-xs font-bold rounded-xl transition">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Subscriptions Table -->
    <div class="overflow-hidden bg-white border border-gray-100 rounded-2xl shadow-xs dark:bg-gray-800 dark:border-gray-700">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-750">
                    <tr class="text-left text-xs font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider">
                        <th class="px-6 py-3.5">Business</th>
                        <th class="px-6 py-3.5">Plan</th>
                        <th class="px-6 py-3.5">Billing Cycle</th>
                        <th class="px-6 py-3.5">Price</th>
                        <th class="px-6 py-3.5">Expires</th>
                        <th class="px-6 py-3.5">Status</th>
                        <th class="px-6 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60 text-xs">
                    @forelse ($subscriptions as $sub)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-750/50 transition">
                            <td class="px-6 py-4">
                                <a href="{{ route('admin.businesses.show', $sub->business_id) }}" class="font-bold text-slate-900 dark:text-white hover:text-orange-600 dark:hover:text-orange-400">
                                    {{ $sub->business->name ?? 'Unknown Business' }}
                                </a>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400 font-mono mt-0.5">{{ $sub->business->slug ?? '' }}</p>
                            </td>

                            <td class="px-6 py-4">
                                <span class="px-2.5 py-1 rounded-lg text-xs font-bold bg-purple-50 text-purple-700 dark:bg-purple-950/50 dark:text-purple-300 border border-purple-200 dark:border-purple-800/50">
                                    {{ $sub->plan->name ?? 'Standard Plan' }}
                                </span>
                            </td>

                            <td class="px-6 py-4 capitalize font-semibold text-slate-700 dark:text-slate-200">
                                {{ $sub->billing_cycle }}
                            </td>

                            <td class="px-6 py-4 font-mono font-bold text-slate-900 dark:text-white text-sm">
                                ${{ number_format($sub->price, 2) }}
                            </td>

                            <td class="px-6 py-4">
                                @if($sub->expires_at)
                                    <span class="font-mono text-xs {{ $sub->expires_at->isPast() ? 'text-rose-600 font-bold' : 'text-slate-700 dark:text-slate-200' }}">
                                        {{ $sub->expires_at->format('M d, Y') }}
                                        <span class="text-[10px] block text-slate-400">({{ $sub->expires_at->diffForHumans() }})</span>
                                    </span>
                                @else
                                    <span class="text-slate-400 italic">Lifetime / Open</span>
                                @endif
                            </td>

                            <td class="px-6 py-4">
                                <x-badge :color="match($sub->status) {
                                    'active' => 'green',
                                    'expired' => 'amber',
                                    'cancelled' => 'red',
                                    default => 'blue'
                                }">
                                    {{ ucfirst($sub->status) }}
                                </x-badge>
                            </td>

                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end space-x-2">
                                    <!-- Extend 30 Days Form -->
                                    <form method="POST" action="{{ route('admin.subscriptions.update-status', $sub) }}" class="inline">
                                        @csrf
                                        <input type="hidden" name="status" value="active">
                                        <input type="hidden" name="extend_days" value="30">
                                        <button type="submit" class="px-2.5 py-1 bg-emerald-50 text-emerald-700 hover:bg-emerald-600 hover:text-white dark:bg-emerald-950/50 dark:text-emerald-300 dark:hover:bg-emerald-600 rounded-lg text-[11px] font-bold transition">
                                            +30 Days
                                        </button>
                                    </form>

                                    @if($sub->status === 'active')
                                        <form method="POST" action="{{ route('admin.subscriptions.update-status', $sub) }}" class="inline" onsubmit="return confirm('Cancel this subscription?');">
                                            @csrf
                                            <input type="hidden" name="status" value="cancelled">
                                            <button type="submit" class="px-2.5 py-1 bg-rose-50 text-rose-700 hover:bg-rose-600 hover:text-white dark:bg-rose-950/50 dark:text-rose-300 dark:hover:bg-rose-600 rounded-lg text-[11px] font-bold transition">
                                                Cancel
                                            </button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('admin.subscriptions.update-status', $sub) }}" class="inline">
                                            @csrf
                                            <input type="hidden" name="status" value="active">
                                            <button type="submit" class="px-2.5 py-1 bg-blue-50 text-blue-700 hover:bg-blue-600 hover:text-white dark:bg-blue-950/50 dark:text-blue-300 dark:hover:bg-blue-600 rounded-lg text-[11px] font-bold transition">
                                                Activate
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-slate-400">No subscriptions found matching criteria.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($subscriptions->hasPages())
            <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">
                {{ $subscriptions->links() }}
            </div>
        @endif
    </div>
</div>
</x-admin-layout>
