<x-admin-layout title="All Businesses - Super Admin" heading="Businesses Management">

<div class="space-y-6">
    <!-- Filters & Search Bar -->
    <div class="p-4 bg-white border border-gray-100 rounded-2xl shadow-xs dark:bg-gray-800 dark:border-gray-700/60">
        <form method="GET" action="{{ route('admin.businesses.index') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-4">
            <div class="sm:col-span-2">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Search by business name, slug, email, phone..."
                       class="w-full text-xs rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2.5 focus:border-orange-500 focus:ring-orange-500">
            </div>

            <div>
                <select name="business_type" class="w-full text-xs rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2.5 focus:border-orange-500 focus:ring-orange-500">
                    <option value="">All Categories</option>
                    @foreach ($businessTypes as $typeKey => $typeData)
                        <option value="{{ $typeKey }}" {{ request('business_type') === $typeKey ? 'selected' : '' }}>
                            {{ $typeData['name_en'] }} ({{ $typeData['name_km'] }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex space-x-2">
                <select name="status" class="w-full text-xs rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2.5 focus:border-orange-500 focus:ring-orange-500">
                    <option value="">All Statuses</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="suspended" {{ request('status') === 'suspended' ? 'selected' : '' }}>Suspended</option>
                </select>

                <button type="submit" class="px-4 py-2.5 bg-orange-600 hover:bg-orange-700 text-white font-semibold text-xs rounded-xl shadow-xs transition">
                    Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Businesses Table -->
    <div class="overflow-hidden bg-white border border-gray-100 rounded-2xl shadow-xs dark:bg-gray-800 dark:border-gray-700/60">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-750">
                    <tr class="text-left text-xs font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider">
                        <th class="px-6 py-3.5">Business / Slug</th>
                        <th class="px-6 py-3.5">Category</th>
                        <th class="px-6 py-3.5">Owner</th>
                        <th class="px-6 py-3.5 text-center">Catalog</th>
                        <th class="px-6 py-3.5 text-center">Orders</th>
                        <th class="px-6 py-3.5">Plan</th>
                        <th class="px-6 py-3.5">Status</th>
                        <th class="px-6 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60 text-xs">
                    @forelse ($businesses as $biz)
                        <tr class="hover:bg-slate-50/70 dark:hover:bg-gray-750/50 transition">
                            <td class="px-6 py-4">
                                <a href="{{ route('admin.businesses.show', $biz) }}" class="font-bold text-slate-900 hover:text-orange-600 dark:text-white dark:hover:text-orange-400">
                                    {{ $biz->name }}
                                </a>
                                <p class="text-xs text-slate-500 dark:text-slate-400 font-mono font-medium mt-0.5">/{{ $biz->slug }}</p>
                                <span class="text-xs text-slate-600 dark:text-slate-300 font-medium">{{ $biz->phone ?? 'No phone' }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold bg-slate-100 text-slate-800 border border-slate-200 dark:bg-gray-700 dark:border-gray-600 dark:text-slate-200 capitalize">
                                    {{ str_replace('_', ' ', $biz->business_type) }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <p class="font-bold text-slate-900 dark:text-white">{{ $biz->owner->name ?? 'None' }}</p>
                                <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">{{ $biz->owner->email ?? '' }}</p>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="font-black text-slate-900 dark:text-white text-sm">{{ $biz->products_count }}</span>
                                <span class="text-xs font-medium text-slate-500 dark:text-slate-400">prods</span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="font-black text-slate-900 dark:text-white text-sm">{{ $biz->orders_count }}</span>
                            </td>
                            <td class="px-6 py-4">
                                @if ($biz->activeSubscription)
                                    <x-badge color="orange">{{ $biz->activeSubscription->plan->name ?? 'Custom' }}</x-badge>
                                @else
                                    <x-badge color="gray">No Plan</x-badge>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <x-badge :color="$biz->status === 'active' ? 'green' : 'red'">
                                    {{ ucfirst($biz->status) }}
                                </x-badge>
                            </td>
                            <td class="px-6 py-4 text-right space-x-2">
                                <a href="{{ route('admin.businesses.show', $biz) }}" class="font-bold text-blue-600 hover:text-blue-700 dark:text-blue-400 hover:underline">View</a>

                                <form method="POST" action="{{ route('admin.businesses.toggle-status', $biz) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="font-bold {{ $biz->status === 'active' ? 'text-amber-600 hover:text-amber-700' : 'text-emerald-600 hover:text-emerald-700' }} hover:underline">
                                        {{ $biz->status === 'active' ? 'Suspend' : 'Activate' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-8 text-center text-xs font-medium text-slate-500 dark:text-slate-400">No businesses match the search criteria.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($businesses->hasPages())
            <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">
                {{ $businesses->links() }}
            </div>
        @endif
    </div>
</div>
</x-admin-layout>
