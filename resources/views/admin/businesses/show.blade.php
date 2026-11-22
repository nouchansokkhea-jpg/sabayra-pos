<x-admin-layout :title="$business->name . ' - Super Admin'" :heading="$business->name">

<div class="space-y-6">
    <!-- Top Action Header -->
    <div class="flex flex-wrap items-center justify-between gap-4 p-6 bg-white border border-gray-100 rounded-2xl shadow-xs dark:bg-gray-800 dark:border-gray-700/60">
        <div>
            <div class="flex items-center space-x-3">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ $business->name }}</h2>
                <x-badge :color="$business->status === 'active' ? 'green' : 'red'">
                    {{ ucfirst($business->status) }}
                </x-badge>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-semibold bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400 capitalize">
                    {{ str_replace('_', ' ', $business->business_type) }}
                </span>
            </div>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Slug: <code class="font-mono text-orange-600 dark:text-orange-400">/{{ $business->slug }}</code> &bull; Registered {{ $business->created_at->format('M d, Y') }}</p>
        </div>

        <div class="flex items-center space-x-3">
            <a href="/{{ $business->slug }}" target="_blank" class="inline-flex items-center px-3.5 py-2 text-xs font-semibold text-white bg-orange-600 hover:bg-orange-700 rounded-xl shadow-xs transition">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                </svg>
                View Public Menu
            </a>

            <form method="POST" action="{{ route('admin.businesses.toggle-status', $business) }}">
                @csrf
                <button type="submit" class="inline-flex items-center px-3.5 py-2 text-xs font-semibold rounded-xl border {{ $business->status === 'active' ? 'border-amber-300 text-amber-700 hover:bg-amber-50 dark:border-amber-700 dark:text-amber-400' : 'border-emerald-300 text-emerald-700 hover:bg-emerald-50 dark:border-emerald-700 dark:text-emerald-400' }}">
                    {{ $business->status === 'active' ? 'Suspend Business' : 'Activate Business' }}
                </button>
            </form>
        </div>
    </div>

    <!-- Quick Financial & Metric Stats -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <x-stat-card
            title="Total Revenue"
            :value="'$' . number_format($totalRevenue, 2)"
            subtitle="~ {{ number_format($totalRevenueKhr, 0) }} ៛"
            color="green"
            icon='<svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'
        />

        <x-stat-card
            title="Total Orders"
            :value="$business->orders()->count()"
            subtitle="Dine-in, takeaway & POS"
            color="purple"
            icon='<svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>'
        />

        <x-stat-card
            title="Products in Catalog"
            :value="$business->products()->count()"
            subtitle="Across {{ $business->categories()->count() }} categories"
            color="blue"
            icon='<svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>'
        />

        <x-stat-card
            title="Active Plan"
            :value="$business->activeSubscription->plan->name ?? 'Free Tier'"
            subtitle="{{ $business->activeSubscription ? ucfirst($business->activeSubscription->billing_cycle) : 'No subscription' }}"
            color="orange"
            icon='<svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>'
        />
    </div>

    <!-- Details Grid -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <!-- Contact & Location Info -->
        <div class="p-6 bg-white border border-gray-100 rounded-2xl shadow-xs dark:bg-gray-800 dark:border-gray-700/60">
            <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider mb-4">Contact & Location</h3>

            <dl class="divide-y divide-gray-100 dark:divide-gray-700/60 text-xs">
                <div class="py-2.5 flex justify-between">
                    <dt class="font-medium text-slate-600 dark:text-slate-300">Business Owner</dt>
                    <dd class="font-bold text-slate-900 dark:text-white">{{ $business->owner->name ?? 'None' }} ({{ $business->owner->email ?? '' }})</dd>
                </div>
                <div class="py-2.5 flex justify-between">
                    <dt class="font-medium text-slate-600 dark:text-slate-300">Phone</dt>
                    <dd class="font-bold text-slate-900 dark:text-white">{{ $business->phone ?? 'Not provided' }}</dd>
                </div>
                <div class="py-2.5 flex justify-between">
                    <dt class="font-medium text-slate-600 dark:text-slate-300">Email</dt>
                    <dd class="font-bold text-slate-900 dark:text-white">{{ $business->email ?? 'Not provided' }}</dd>
                </div>
                <div class="py-2.5 flex justify-between">
                    <dt class="font-medium text-slate-600 dark:text-slate-300">Telegram</dt>
                    <dd class="font-bold text-slate-900 dark:text-white">{{ $business->telegram_handle ?? 'Not provided' }}</dd>
                </div>
                <div class="py-2.5 flex justify-between">
                    <dt class="font-medium text-slate-600 dark:text-slate-300">Address</dt>
                    <dd class="font-bold text-slate-900 dark:text-white text-right">{{ $business->address ?? 'Not provided' }}, {{ $business->city }}</dd>
                </div>
                <div class="py-2.5 flex justify-between">
                    <dt class="font-medium text-slate-600 dark:text-slate-300">Currency & Exchange Rate</dt>
                    <dd class="font-bold text-emerald-700 dark:text-emerald-400">1 USD = {{ number_format($business->exchange_rate_khr, 0) }} KHR</dd>
                </div>
                <div class="py-2.5 flex justify-between">
                    <dt class="font-medium text-slate-600 dark:text-slate-300">Tax Percent</dt>
                    <dd class="font-bold text-slate-900 dark:text-white">{{ $business->tax_percent }}%</dd>
                </div>
            </dl>
        </div>

        <!-- Branches & Members -->
        <div class="p-6 bg-white border border-gray-100 rounded-2xl shadow-xs dark:bg-gray-800 dark:border-gray-700/60">
            <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider mb-4">Branches ({{ $business->branches->count() }})</h3>
            <div class="space-y-2 mb-6">
                @forelse ($business->branches as $branch)
                    <div class="p-3 border border-gray-100 rounded-xl bg-gray-50 dark:bg-gray-750 dark:border-gray-700/60 flex items-center justify-between text-xs">
                        <div>
                            <p class="font-bold text-gray-900 dark:text-white flex items-center">
                                {{ $branch->name }}
                                @if($branch->is_main)
                                    <span class="ml-2 text-[10px] bg-orange-100 text-orange-700 font-semibold px-2 py-0.5 rounded">Main Branch</span>
                                @endif
                            </p>
                            <p class="text-[11px] text-gray-500 mt-0.5">{{ $branch->address ?? 'No address' }}</p>
                        </div>
                        <x-badge :color="$branch->is_active ? 'green' : 'gray'">{{ $branch->is_active ? 'Active' : 'Inactive' }}</x-badge>
                    </div>
                @empty
                    <p class="text-xs text-gray-400">No branches added yet.</p>
                @endforelse
            </div>

            <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider mb-4">Staff & Roles ({{ $business->members->count() }})</h3>
            <div class="space-y-2">
                @forelse ($business->members as $member)
                    <div class="p-2.5 border border-gray-100 rounded-xl flex items-center justify-between text-xs dark:border-gray-700">
                        <div>
                            <p class="font-semibold text-gray-900 dark:text-white">{{ $member->user->name ?? 'User' }}</p>
                            <p class="text-[11px] text-gray-400">{{ $member->user->email ?? '' }}</p>
                        </div>
                        <span class="px-2 py-0.5 text-xs font-semibold rounded bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 capitalize">
                            {{ $member->role }}
                        </span>
                    </div>
                @empty
                    <p class="text-xs text-gray-400">No staff members listed.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
</x-admin-layout>
