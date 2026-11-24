<x-admin-layout title="Platform Overview" heading="Super Admin Overview">
<div class="space-y-6">
    <!-- Super Admin Action Hub -->
    <div class="flex flex-wrap items-center justify-between gap-3 p-4 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-xs">
        <div class="flex items-center space-x-2">
            <span class="w-3 h-3 rounded-full bg-emerald-500 animate-pulse"></span>
            <span class="text-xs sm:text-sm font-black text-slate-900 dark:text-white">SABAYRA Platform Owner Portal</span>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.subscriptions.index') }}" class="px-3.5 py-2 bg-purple-50 hover:bg-purple-100 text-purple-800 border border-purple-200 dark:bg-purple-950/50 dark:border-purple-800/60 dark:text-purple-300 rounded-xl text-xs font-bold transition shadow-2xs">
                ⚡ Subscriptions
            </a>
            <a href="{{ route('admin.payments.index') }}" class="px-3.5 py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 dark:bg-emerald-950/50 dark:border-emerald-800/60 dark:text-emerald-300 rounded-xl text-xs font-bold transition shadow-2xs">
                💳 Payments
            </a>
            <a href="{{ route('admin.analytics.index') }}" class="px-3.5 py-2 bg-blue-50 hover:bg-blue-100 text-blue-800 border border-blue-200 dark:bg-blue-950/50 dark:border-blue-800/60 dark:text-blue-300 rounded-xl text-xs font-bold transition shadow-2xs">
                📈 Analytics
            </a>
            <a href="{{ route('admin.support.index') }}" class="px-3.5 py-2 bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-200 dark:bg-amber-950/50 dark:border-amber-800/60 dark:text-amber-300 rounded-xl text-xs font-bold transition shadow-2xs">
                🎧 Support
            </a>
            <a href="{{ route('admin.settings.index') }}" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-800 border border-slate-200 dark:bg-gray-700 dark:border-gray-600 dark:text-slate-100 rounded-xl text-xs font-bold transition shadow-2xs">
                ⚙️ Settings
            </a>
        </div>
    </div>

    <!-- Stat Cards Grid -->
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <x-stat-card
            title="Total Businesses"
            :value="$totalBusinesses"
            subtitle="{{ $activeBusinesses }} active, {{ $suspendedBusinesses }} suspended"
            color="orange"
            icon='<svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>'
        />

        <x-stat-card
            title="Total Users"
            :value="$totalUsers"
            subtitle="Platform registered accounts"
            color="blue"
            icon='<svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>'
        />

        <x-stat-card
            title="Monthly Recurring (MRR)"
            :value="'$' . number_format($mrr, 2)"
            subtitle="From active subscriptions"
            color="green"
            icon='<svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'
        />

        <x-stat-card
            title="Gross GMV (Platform Sales)"
            :value="'$' . number_format($totalSalesUsd, 2)"
            subtitle="~ {{ number_format($totalSalesKhr, 0) }} ៛ ({{ $totalOrders }} orders)"
            color="purple"
            icon='<svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>'
        />
    </div>

    <!-- Middle Section: Business Category Distribution & System Health -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Business Type Breakdown -->
        <div class="p-6 bg-white border border-gray-100 rounded-2xl shadow-xs dark:bg-gray-800 dark:border-gray-700/60 lg:col-span-2">
            <h3 class="text-base font-extrabold text-slate-900 dark:text-white">Businesses by Category</h3>
            <p class="mt-1 text-xs font-medium text-slate-600 dark:text-slate-300">Distribution across supported Cambodian retail & F&B sectors</p>

            <div class="grid grid-cols-2 gap-3 mt-4 sm:grid-cols-3 md:grid-cols-4">
                @foreach (config('menuhub.business_types') as $typeKey => $typeData)
                    @php $count = $businessTypes[$typeKey] ?? 0; @endphp
                    <div class="p-3.5 border border-slate-200/80 rounded-xl bg-slate-50/80 dark:bg-gray-750 dark:border-gray-700 shadow-2xs hover:border-orange-300 transition">
                        <p class="text-xs font-bold text-slate-900 dark:text-slate-100 truncate">{{ $typeData['name_en'] }}</p>
                        <p class="text-xl font-black text-slate-900 dark:text-white mt-1">{{ $count }}</p>
                        <p class="text-xs font-semibold text-slate-600 dark:text-slate-300 font-khmer truncate mt-0.5">{{ $typeData['name_km'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Infrastructure & Health Status -->
        <div class="p-6 bg-white border border-gray-100 rounded-2xl shadow-xs dark:bg-gray-800 dark:border-gray-700/60">
            <h3 class="text-base font-extrabold text-slate-900 dark:text-white">System Environment</h3>
            <p class="mt-1 text-xs font-medium text-slate-600 dark:text-slate-300">Infrastructure & service health</p>

            <div class="mt-4 space-y-3 text-xs">
                <div class="flex items-center justify-between pb-2 border-b border-gray-100 dark:border-gray-700">
                    <span class="font-medium text-slate-700 dark:text-slate-300">Database Engine</span>
                    <span class="font-bold text-slate-900 dark:text-white">PostgreSQL (Supabase)</span>
                </div>
                <div class="flex items-center justify-between pb-2 border-b border-gray-100 dark:border-gray-700">
                    <span class="font-medium text-slate-700 dark:text-slate-300">PHP Version</span>
                    <span class="font-bold text-slate-900 dark:text-white">{{ PHP_VERSION }}</span>
                </div>
                <div class="flex items-center justify-between pb-2 border-b border-gray-100 dark:border-gray-700">
                    <span class="font-medium text-slate-700 dark:text-slate-300">Framework</span>
                    <span class="font-bold text-slate-900 dark:text-white">Laravel {{ app()->version() }}</span>
                </div>
                <div class="flex items-center justify-between pb-2 border-b border-gray-100 dark:border-gray-700">
                    <span class="font-medium text-slate-700 dark:text-slate-300">Timezone</span>
                    <span class="font-bold text-slate-900 dark:text-white">{{ config('app.timezone') }}</span>
                </div>
                <div class="flex items-center justify-between pb-2 border-b border-gray-100 dark:border-gray-700">
                    <span class="font-medium text-slate-700 dark:text-slate-300">Default Exchange Rate</span>
                    <span class="font-bold text-emerald-700 dark:text-emerald-400">1 USD = 4,100 KHR</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="font-medium text-slate-700 dark:text-slate-300">Platform Status</span>
                    <x-badge color="green">Operational</x-badge>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Section: Recent Businesses, Users & Activity Logs -->
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <!-- Recent Businesses -->
        <div class="p-6 bg-white border border-gray-100 rounded-2xl shadow-xs dark:bg-gray-800 dark:border-gray-700/60">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-extrabold text-slate-900 dark:text-white">Recent Businesses / អាជីវកម្មថ្មីៗ</h3>
                <a href="{{ route('admin.businesses.index') }}" class="text-xs font-bold text-orange-600 hover:text-orange-700 dark:text-orange-400 hover:underline">View All &rarr;</a>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700/60">
                    <thead>
                        <tr class="text-left text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider bg-slate-50 dark:bg-gray-750/70">
                            <th class="py-2.5 px-3">Business</th>
                            <th class="py-2.5 px-3">Owner</th>
                            <th class="py-2.5 px-3">Type</th>
                            <th class="py-2.5 px-3">Status</th>
                            <th class="py-2.5 px-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/40 text-xs">
                        @forelse ($recentBusinesses as $biz)
                            <tr class="hover:bg-slate-50/60 dark:hover:bg-gray-750/50 transition">
                                <td class="py-3 px-3">
                                    <p class="font-bold text-slate-900 dark:text-white">{{ $biz->name }}</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 font-mono font-medium">/{{ $biz->slug }}</p>
                                </td>
                                <td class="py-3 px-3 font-medium text-slate-800 dark:text-slate-200">
                                    {{ $biz->owner->name ?? 'N/A' }}
                                </td>
                                <td class="py-3 px-3">
                                    <span class="capitalize font-medium text-slate-700 dark:text-slate-300">{{ str_replace('_', ' ', $biz->business_type) }}</span>
                                </td>
                                <td class="py-3 px-3">
                                    <x-badge :color="$biz->status === 'active' ? 'green' : 'red'">
                                        {{ ucfirst($biz->status) }}
                                    </x-badge>
                                </td>
                                <td class="py-3 px-3 text-right">
                                    <a href="{{ route('admin.businesses.show', $biz) }}" class="font-bold text-orange-600 hover:text-orange-700 dark:text-orange-400 hover:underline">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-6 text-center text-xs font-medium text-slate-500 dark:text-slate-400">No businesses registered yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Registered Users -->
        <div class="p-6 bg-white border border-gray-100 rounded-2xl shadow-xs dark:bg-gray-800 dark:border-gray-700/60">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-extrabold text-slate-900 dark:text-white">Recent Users / អ្នកប្រើប្រាស់ថ្មីៗ</h3>
                <a href="{{ route('admin.users.index') }}" class="text-xs font-bold text-orange-600 hover:text-orange-700 dark:text-orange-400 hover:underline">View All &rarr;</a>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700/60">
                    <thead>
                        <tr class="text-left text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider bg-slate-50 dark:bg-gray-750/70">
                            <th class="py-2.5 px-3">User</th>
                            <th class="py-2.5 px-3">Phone</th>
                            <th class="py-2.5 px-3">Role</th>
                            <th class="py-2.5 px-3 text-right">Registered</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/40 text-xs">
                        @forelse ($recentUsers as $u)
                            <tr class="hover:bg-slate-50/60 dark:hover:bg-gray-750/50 transition">
                                <td class="py-3 px-3">
                                    <p class="font-bold text-slate-900 dark:text-white">{{ $u->name }}</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ $u->email }}</p>
                                </td>
                                <td class="py-3 px-3 font-medium text-slate-700 dark:text-slate-300">
                                    {{ $u->phone ?? '—' }}
                                </td>
                                <td class="py-3 px-3">
                                    @if ($u->is_super_admin)
                                        <span class="inline-flex px-2 py-0.5 text-[10px] font-bold rounded-full bg-purple-100 text-purple-800 dark:bg-purple-900/50 dark:text-purple-300">Super Admin</span>
                                    @else
                                        <span class="inline-flex px-2 py-0.5 text-[10px] font-bold rounded-full bg-slate-100 text-slate-800 dark:bg-gray-700 dark:text-slate-200">User</span>
                                    @endif
                                </td>
                                <td class="py-3 px-3 text-right text-slate-500 dark:text-slate-400 font-mono text-[11px]">
                                    {{ $u->created_at ? $u->created_at->diffForHumans() : 'Recently' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-6 text-center text-xs font-medium text-slate-500 dark:text-slate-400">No users registered yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Activity Logs -->
        <div class="p-6 bg-white border border-gray-100 rounded-2xl shadow-xs dark:bg-gray-800 dark:border-gray-700/60">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-extrabold text-slate-900 dark:text-white">Audit & System Activity</h3>
                <a href="{{ route('admin.activity-logs.index') }}" class="text-xs font-bold text-orange-600 hover:text-orange-700 dark:text-orange-400 hover:underline">View All &rarr;</a>
            </div>

            <div class="space-y-3">
                @forelse ($recentActivities as $log)
                    <div class="flex items-start justify-between pb-3 border-b border-gray-50 dark:border-gray-700/40 text-xs">
                        <div>
                            <p class="font-medium text-slate-800 dark:text-slate-200">
                                <span class="font-bold text-orange-600 dark:text-orange-400">{{ $log->causer->name ?? 'System' }}</span>
                                {{ $log->description }}
                            </p>
                            <p class="text-xs font-semibold text-slate-500 dark:text-slate-400 font-mono mt-0.5">{{ $log->log_name ?? 'default' }}</p>
                        </div>
                        <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 shrink-0">{{ $log->created_at->diffForHumans() }}</span>
                    </div>
                @empty
                    <p class="py-6 text-center text-xs font-medium text-slate-500 dark:text-slate-400">No activity events logged yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
</x-admin-layout>
