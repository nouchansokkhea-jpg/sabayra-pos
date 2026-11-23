<x-business-layout title="Dashboard" heading="Business Overview">
<div class="space-y-6" x-data="{ qrModalOpen: false, copied: false }">

    <!-- 1. PUBLIC STOREFRONT PROMO & QUICK ACTIONS CARD -->
    <div class="p-6 bg-gradient-to-r from-orange-600 via-amber-600 to-orange-700 rounded-3xl text-white shadow-md relative overflow-hidden">
        <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6 relative z-10">
            <div class="space-y-2 max-w-xl">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-bold bg-white/20 backdrop-blur-md text-white border border-white/30">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span>Live Customer Storefront</span>
                </div>
                <h2 class="text-2xl font-black tracking-tight text-white">{{ $business->name }}</h2>
                <p class="text-xs text-orange-100 leading-relaxed font-medium">
                    Share your digital menu link or display your custom QR code on dining tables and counters for instant customer ordering in USD & KHR.
                </p>
                <div class="pt-2 flex items-center gap-2 font-mono text-xs bg-black/20 p-2.5 rounded-xl border border-white/20 text-orange-100 break-all select-all">
                    <span>{{ $publicMenuUrl }}</span>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2.5">
                <button
                    @click="navigator.clipboard.writeText('{{ $publicMenuUrl }}'); copied = true; setTimeout(() => copied = false, 2500)"
                    class="px-4 py-2.5 bg-white text-orange-700 hover:bg-orange-50 font-bold rounded-xl text-xs shadow-sm transition flex items-center gap-1.5"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>
                    <span x-text="copied ? 'Copied to Clipboard!' : 'Copy Link'"></span>
                </button>

                <a href="{{ $publicMenuUrl }}" target="_blank" class="px-4 py-2.5 bg-white/20 hover:bg-white/30 text-white font-bold rounded-xl text-xs backdrop-blur-md border border-white/30 transition flex items-center gap-1.5">
                    <span>View Storefront</span>
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                </a>

                <button @click="qrModalOpen = true" class="px-4 py-2.5 bg-black/30 hover:bg-black/40 text-white font-bold rounded-xl text-xs backdrop-blur-md border border-white/20 transition flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                    <span>Storefront QR</span>
                </button>
            </div>
        </div>
    </div>

    <!-- 2. TODAY VS YESTERDAY PERFORMANCE OVERVIEW -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Today's Revenue -->
        <div class="p-5 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-2xl shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider">Today's Revenue</span>
                @if ($revenueChangePercent >= 0)
                    <span class="inline-flex items-center text-xs font-bold text-emerald-600 bg-emerald-50 dark:bg-emerald-950/40 px-2 py-0.5 rounded-full">
                        ↑ +{{ $revenueChangePercent }}%
                    </span>
                @else
                    <span class="inline-flex items-center text-xs font-bold text-rose-600 bg-rose-50 dark:bg-rose-950/40 px-2 py-0.5 rounded-full">
                        ↓ {{ $revenueChangePercent }}%
                    </span>
                @endif
            </div>
            <div class="mt-2 text-2xl font-black text-gray-900 dark:text-white">
                ${{ number_format($todayRevenueUsd, 2) }}
            </div>
            <div class="mt-1 flex items-center justify-between text-xs text-gray-600 dark:text-gray-300 font-medium">
                <span>Yesterday: ${{ number_format($yesterdayRevenueUsd, 2) }}</span>
                <span class="font-bold text-orange-600">{{ number_format($todayRevenueUsd * $business->exchange_rate_khr, 0) }} ៛</span>
            </div>
        </div>

        <!-- Today's Orders -->
        <div class="p-5 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-2xl shadow-xs">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider">Today's Orders</span>
                @if ($ordersChangePercent >= 0)
                    <span class="inline-flex items-center text-xs font-bold text-emerald-600 bg-emerald-50 dark:bg-emerald-950/40 px-2 py-0.5 rounded-full">
                        ↑ +{{ $ordersChangePercent }}%
                    </span>
                @else
                    <span class="inline-flex items-center text-xs font-bold text-rose-600 bg-rose-50 dark:bg-rose-950/40 px-2 py-0.5 rounded-full">
                        ↓ {{ $ordersChangePercent }}%
                    </span>
                @endif
            </div>
            <div class="mt-2 text-2xl font-black text-gray-900 dark:text-white">
                {{ $todayOrders }} <span class="text-xs font-medium text-gray-500 dark:text-gray-300">orders</span>
            </div>
            <div class="mt-1 text-xs text-gray-600 dark:text-gray-300 font-medium">
                Yesterday: {{ $yesterdayOrders }} orders
            </div>
        </div>

        <!-- Active Floor Status -->
        <div class="p-5 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-2xl shadow-xs">
            <span class="text-xs font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider">Orders in Flow</span>
            <div class="mt-2 text-2xl font-black text-gray-900 dark:text-white">
                {{ $pendingOrders + $preparingOrders }}
            </div>
            <div class="mt-1 flex items-center gap-3 text-xs font-bold">
                <span class="text-amber-600">{{ $pendingOrders }} Pending</span>
                <span class="text-blue-600">{{ $preparingOrders }} In Kitchen</span>
            </div>
        </div>

        <!-- Weekly Summary -->
        <div class="p-5 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-2xl shadow-xs">
            <span class="text-xs font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider">Weekly Revenue</span>
            <div class="mt-2 text-2xl font-black text-emerald-600 dark:text-emerald-400">
                ${{ number_format($weeklyRevenue, 2) }}
            </div>
            <div class="mt-1 text-xs text-gray-600 dark:text-gray-300 font-medium">
                {{ $weeklyOrdersCount }} orders completed this week
            </div>
        </div>
    </div>

    <!-- 3. QUICK ACTIONS GRID -->
    <div class="p-5 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-2xl shadow-xs">
        <h3 class="text-xs font-extrabold uppercase tracking-wider text-gray-700 dark:text-gray-200 mb-3">Quick Operational Actions</h3>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-3">
            <a href="{{ route('business.pos.index') }}" class="p-3 rounded-xl bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-950/30 dark:hover:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-800/60 text-emerald-800 dark:text-emerald-300 font-bold text-xs flex flex-col items-center gap-1.5 transition">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                <span>Touch POS</span>
            </a>

            <a href="{{ route('business.products.create') }}" class="p-3 rounded-xl bg-orange-50 hover:bg-orange-100 dark:bg-orange-950/30 dark:hover:bg-orange-950/50 border border-orange-200 dark:border-orange-800/60 text-orange-800 dark:text-orange-300 font-bold text-xs flex flex-col items-center gap-1.5 transition">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Add Product</span>
            </a>

            <a href="{{ route('business.categories.index') }}" class="p-3 rounded-xl bg-blue-50 hover:bg-blue-100 dark:bg-blue-950/30 dark:hover:bg-blue-950/50 border border-blue-200 dark:border-blue-800/60 text-blue-800 dark:text-blue-300 font-bold text-xs flex flex-col items-center gap-1.5 transition">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                <span>Categories</span>
            </a>

            <a href="{{ route('business.kitchen.index') }}" class="p-3 rounded-xl bg-purple-50 hover:bg-purple-100 dark:bg-purple-950/30 dark:hover:bg-purple-950/50 border border-purple-200 dark:border-purple-800/60 text-purple-800 dark:text-purple-300 font-bold text-xs flex flex-col items-center gap-1.5 transition">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                <span>Kitchen (KDS)</span>
            </a>

            <a href="{{ route('business.tables.index') }}" class="p-3 rounded-xl bg-amber-50 hover:bg-amber-100 dark:bg-amber-950/30 dark:hover:bg-amber-950/50 border border-amber-200 dark:border-amber-800/60 text-amber-800 dark:text-amber-300 font-bold text-xs flex flex-col items-center gap-1.5 transition">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                <span>Table QR</span>
            </a>

            <a href="{{ route('business.reports.index') }}" class="p-3 rounded-xl bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-zinc-800 dark:text-zinc-200 font-bold text-xs flex flex-col items-center gap-1.5 transition">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                <span>Reports</span>
            </a>
        </div>
    </div>

    <!-- 4. BUSINESS STATS GRID (6 INDICATORS) -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
        <div class="p-4 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-2xl shadow-xs">
            <span class="text-[11px] font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider">Total Products</span>
            <div class="mt-1 text-xl font-black text-gray-900 dark:text-white">{{ $totalProducts }}</div>
            <span class="text-[11px] text-emerald-600 font-semibold">{{ $activeProducts }} active</span>
        </div>

        <div class="p-4 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-2xl shadow-xs">
            <span class="text-[11px] font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider">Featured Items</span>
            <div class="mt-1 text-xl font-black text-orange-600">{{ $featuredProducts }}</div>
            <span class="text-[11px] text-gray-600 dark:text-gray-300 font-medium">highlighted</span>
        </div>

        <div class="p-4 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-2xl shadow-xs">
            <span class="text-[11px] font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider">Categories</span>
            <div class="mt-1 text-xl font-black text-gray-900 dark:text-white">{{ $totalCategories }}</div>
            <span class="text-[11px] text-gray-600 dark:text-gray-300 font-medium">catalog sections</span>
        </div>

        <div class="p-4 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-2xl shadow-xs">
            <span class="text-[11px] font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider">Total Menu Views</span>
            <div class="mt-1 text-xl font-black text-cyan-600">{{ number_format($totalMenuViews) }}</div>
            <span class="text-[11px] text-gray-600 dark:text-gray-300 font-medium">{{ $todayMenuViews }} views today</span>
        </div>

        <div class="p-4 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-2xl shadow-xs">
            <span class="text-[11px] font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider">Total Customers</span>
            <div class="mt-1 text-xl font-black text-purple-600">{{ $newCustomersThisWeek }}</div>
            <span class="text-[11px] text-gray-600 dark:text-gray-300 font-medium">new this week</span>
        </div>

        <div class="p-4 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-2xl shadow-xs">
            <span class="text-[11px] font-bold text-gray-700 dark:text-gray-200 uppercase tracking-wider">Avg Order Value</span>
            <div class="mt-1 text-xl font-black text-emerald-600">${{ number_format($averageOrderValue, 2) }}</div>
            <span class="text-[11px] text-gray-600 dark:text-gray-300 font-medium">per checkout</span>
        </div>
    </div>

    <!-- 5. CHARTS & PERFORMANCE TREND -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- 7-Day Revenue Trend Chart -->
        <div class="lg:col-span-2 p-6 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-2xl shadow-xs">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">Revenue Trend (Last 7 Days)</h3>
                    <p class="text-xs text-gray-600 dark:text-gray-300 font-medium">Daily paid checkout revenue in USD</p>
                </div>
            </div>
            <div class="h-64">
                <canvas id="revenueTrendChart"></canvas>
            </div>
        </div>

        <!-- Security & Health Overview -->
        <div class="p-6 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-2xl shadow-xs flex flex-col justify-between">
            <div>
                <h3 class="text-base font-bold text-gray-900 dark:text-white mb-1 flex items-center gap-2">
                    <svg class="w-4 h-4 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                    <span>Security & Health</span>
                </h3>
                <p class="text-xs text-gray-600 dark:text-gray-300 font-medium mb-4">Account status & workspace protection</p>

                <div class="space-y-3 text-xs">
                    <div class="flex items-center justify-between p-2.5 rounded-xl bg-gray-50 dark:bg-gray-700/50">
                        <span class="text-gray-600 dark:text-gray-300">2FA Protection</span>
                        @if ($securityOverview['two_factor_enabled'])
                            <span class="font-bold text-emerald-600">Enabled</span>
                        @else
                            <span class="font-bold text-amber-600">Disabled</span>
                        @endif
                    </div>

                    <div class="flex items-center justify-between p-2.5 rounded-xl bg-gray-50 dark:bg-gray-700/50">
                        <span class="text-gray-600 dark:text-gray-300">Last Owner Login</span>
                        <span class="font-bold text-gray-900 dark:text-white">{{ $securityOverview['last_login'] }}</span>
                    </div>

                    <div class="flex items-center justify-between p-2.5 rounded-xl bg-gray-50 dark:bg-gray-700/50">
                        <span class="text-gray-600 dark:text-gray-300">Tenant Isolation</span>
                        <span class="font-bold text-emerald-600">Enforced</span>
                    </div>
                </div>
            </div>

            <div class="pt-4 border-t border-gray-100 dark:border-gray-700">
                <a href="{{ route('business.settings.index') }}" class="text-xs font-bold text-orange-600 hover:text-orange-500 flex items-center justify-between">
                    <span>Manage Settings & Roles</span>
                    <span>&rarr;</span>
                </a>
            </div>
        </div>
    </div>

    <!-- 6. TOP SELLERS & RECENT PRODUCTS -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Top Selling Items -->
        <div class="p-6 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-2xl shadow-xs">
            <h3 class="text-base font-bold text-gray-900 dark:text-white mb-4">Top Selling Items</h3>
            @if ($bestSellers->isEmpty())
                <div class="py-8 text-center text-xs text-gray-600 dark:text-gray-300 font-medium">
                    No sales recorded yet. As orders are placed via POS or live menu, top performers will appear here.
                </div>
            @else
                <div class="space-y-3">
                    @foreach ($bestSellers as $item)
                        <div class="flex items-center justify-between p-3 rounded-xl bg-gray-50 dark:bg-gray-700/40">
                            <div>
                                <h4 class="text-xs font-bold text-gray-900 dark:text-white">{{ $item->product_name }}</h4>
                                <span class="text-[11px] text-gray-600 dark:text-gray-300 font-medium">{{ (float) $item->total_sold }} units sold</span>
                            </div>
                            <span class="text-xs font-black text-emerald-600">${{ number_format($item->total_earned, 2) }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Recent Products Added -->
        <div class="p-6 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-2xl shadow-xs">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-bold text-gray-900 dark:text-white">Recent Catalog Products</h3>
                <a href="{{ route('business.products.index') }}" class="text-xs font-bold text-orange-600 hover:text-orange-500">View All</a>
            </div>

            @if ($recentProducts->isEmpty())
                <div class="py-8 text-center text-xs text-gray-600 dark:text-gray-300 font-medium">
                    No products added yet. Click <a href="{{ route('business.products.create') }}" class="text-orange-600 font-bold hover:underline">+ Add First Product</a> to start.
                </div>
            @else
                <div class="space-y-3">
                    @foreach ($recentProducts as $p)
                        <div class="flex items-center justify-between p-3 rounded-xl bg-gray-50 dark:bg-gray-700/40">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-lg bg-gray-200 dark:bg-gray-600 overflow-hidden shrink-0">
                                    @if ($p->image_url)
                                        <img src="{{ $p->image_url }}" alt="{{ $p->name_en }}" class="w-full h-full object-cover">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center text-xs text-gray-400 font-bold">Item</div>
                                    @endif
                                </div>
                                <div>
                                    <h4 class="text-xs font-bold text-gray-900 dark:text-white">{{ $p->name_en }}</h4>
                                    <span class="text-[10px] text-gray-600 dark:text-gray-300 font-medium">{{ $p->category?->name_en ?? 'General' }}</span>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="text-xs font-bold text-gray-900 dark:text-white">${{ number_format($p->base_price, 2) }}</span>
                                <div class="text-[10px] font-bold {{ $p->is_available ? 'text-emerald-600' : 'text-gray-500 dark:text-gray-400' }}">
                                    {{ $p->is_available ? 'Active' : 'Hidden' }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- QR CODE MODAL -->
    <div x-show="qrModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-black/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div @click.away="qrModalOpen = false" class="bg-white dark:bg-gray-800 rounded-3xl max-w-sm w-full p-6 text-center space-y-4 shadow-xl">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Storefront QR Code</h3>
            <p class="text-xs text-gray-600 dark:text-gray-300 font-medium">Scan with your smartphone camera to preview the live menu</p>
            <div class="p-4 bg-white rounded-2xl border-2 border-dashed border-orange-200 dark:border-gray-700 inline-block shadow-inner">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={{ urlencode($publicMenuUrl) }}&color=000000" alt="Storefront QR" class="w-48 h-48 mx-auto">
            </div>
            <div class="text-[11px] text-zinc-600 dark:text-zinc-300 font-mono bg-zinc-50 dark:bg-zinc-900 p-2 rounded-xl break-all">
                {{ $publicMenuUrl }}
            </div>
            @if (\Illuminate\Support\Str::contains($publicMenuUrl, ['192.168.', '172.', '10.']))
                <div class="text-[11px] text-amber-800 dark:text-amber-200 bg-amber-50 dark:bg-amber-950/40 p-2.5 rounded-xl border border-amber-200 dark:border-amber-800/50 text-left space-y-1">
                    <p class="font-bold flex items-center gap-1">
                        <span>📱 Phone Connection Tip:</span>
                    </p>
                    <p class="text-[10px] leading-relaxed">
                        Ensure your smartphone is connected to the <strong>same Wi-Fi network</strong> as this computer (not mobile data/5G) so it can reach this local IP address.
                    </p>
                </div>
            @endif
            <div class="flex gap-2">
                <a href="https://api.qrserver.com/v1/create-qr-code/?size=800x800&data={{ urlencode($publicMenuUrl) }}" download="{{ $business->slug }}-qr.png" target="_blank" class="flex-1 py-2 px-4 bg-orange-600 text-white rounded-xl text-xs font-bold hover:bg-orange-700 transition">
                    Download PNG
                </a>
                <button @click="qrModalOpen = false" class="py-2 px-4 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-xl text-xs font-bold hover:bg-gray-200 transition">
                    Close
                </button>
            </div>
        </div>
    </div>

</div>

<!-- CHART.JS SCRIPT -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const ctx = document.getElementById('revenueTrendChart');
        if (ctx) {
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: @json($chartDates),
                    datasets: [{
                        label: 'Revenue ($)',
                        data: @json($chartSales),
                        borderColor: '#ea580c',
                        backgroundColor: 'rgba(234, 88, 12, 0.1)',
                        fill: true,
                        tension: 0.35,
                        borderWidth: 2.5,
                        pointBackgroundColor: '#ea580c',
                        pointRadius: 4,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) { return '$' + value; },
                                color: document.documentElement.classList.contains('dark') ? '#cbd5e1' : '#475569',
                                font: { size: 11, weight: 'bold' }
                            },
                            grid: { color: 'rgba(156, 163, 175, 0.15)' }
                        },
                        x: {
                            ticks: {
                                color: document.documentElement.classList.contains('dark') ? '#cbd5e1' : '#475569',
                                font: { size: 11, weight: 'bold' }
                            },
                            grid: { display: false }
                        }
                    }
                }
            });
        }
    });
</script>
</x-business-layout>
