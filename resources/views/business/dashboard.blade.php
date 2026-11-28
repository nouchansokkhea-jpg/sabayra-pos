<x-business-layout title="Dashboard" heading="{{ $dashboardCfg['headline_en'] ?? 'Business Overview' }}">
<div class="space-y-6" x-data="{ qrModalOpen: false, copied: false, customizeOpen: false }">

    <!-- 1. INDUSTRY EXPERIENCE BANNER & PUBLIC STOREFRONT CARD -->
    <div class="p-6 bg-gradient-to-r from-[#0F1E3A] via-slate-900 to-[#162B52] rounded-3xl text-white shadow-md relative overflow-hidden border border-[#1E3560]">
        <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6 relative z-10">
            <div class="space-y-2 max-w-2xl">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-bold bg-orange-500/20 text-orange-200 border border-orange-500/40">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        <span>{{ $categoryMeta['name_en'] }} · {{ $categoryMeta['name_km'] }}</span>
                    </span>
                    <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-white/10 text-slate-200 border border-white/15 capitalize">
                        {{ $storefrontCfg['title_en'] ?? 'Digital Storefront' }}
                    </span>
                </div>
                <h2 class="text-2xl font-black tracking-tight text-white">
                    {{ $dashboardCfg['headline_en'] ?? $business->name }}
                </h2>
                <p class="text-xs text-slate-300 leading-relaxed font-medium">
                    {{ $categoryMeta['description'] ?: 'Manage daily operations, POS checkouts, inventory, and customer orders in USD & KHR.' }}
                </p>
                <div class="pt-1 flex items-center gap-2 font-mono text-xs bg-black/30 p-2.5 rounded-xl border border-white/15 text-orange-200 break-all select-all">
                    <span>{{ $publicMenuUrl }}</span>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2.5">
                <button
                    @click="navigator.clipboard.writeText('{{ $publicMenuUrl }}'); copied = true; setTimeout(() => copied = false, 2500)"
                    class="px-4 py-2.5 bg-white text-slate-900 hover:bg-orange-50 font-bold rounded-xl text-xs shadow-sm transition flex items-center gap-1.5"
                >
                    <svg class="w-4 h-4 text-orange-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>
                    <span x-text="copied ? 'Copied to Clipboard!' : 'Copy Link'"></span>
                </button>

                <a href="{{ $publicMenuUrl }}" target="_blank" class="px-4 py-2.5 bg-orange-600 hover:bg-orange-700 text-white font-bold rounded-xl text-xs transition flex items-center gap-1.5">
                    <span>Open {{ $storefrontCfg['title_en'] ?? 'Storefront' }}</span>
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                </a>

                <button @click="qrModalOpen = true" class="px-4 py-2.5 bg-white/10 hover:bg-white/20 text-white font-bold rounded-xl text-xs border border-white/20 transition flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                    <span>Storefront QR</span>
                </button>

                <button type="button" @click="customizeOpen = !customizeOpen"
                        class="px-3.5 py-2.5 bg-white/10 hover:bg-white/20 text-white font-bold rounded-xl text-xs border border-white/20 transition">
                    Customize Widgets
                </button>
            </div>
        </div>

        <!-- Personalized Widget Visibility Drawer -->
        <div x-show="customizeOpen" x-cloak class="mt-4 pt-4 border-t border-white/15">
            <form method="POST" action="{{ route('business.dashboard.widgets.update') }}" class="flex flex-wrap items-center justify-between gap-4">
                @csrf
                <div class="flex flex-wrap items-center gap-3 text-xs">
                    <span class="font-bold text-orange-300 uppercase tracking-wider text-[11px]">Visible Dashboard Panels:</span>
                    @foreach ($availablePanels as $panelKey => $panelLabel)
                        <input type="hidden" name="available_panels[]" value="{{ $panelKey }}">
                        <label class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-black/25 border border-white/15 cursor-pointer">
                            <input type="checkbox" name="visible_panels[]" value="{{ $panelKey }}"
                                   {{ !in_array($panelKey, $hiddenWidgets, true) ? 'checked' : '' }}
                                   class="rounded border-gray-400 text-orange-500 focus:ring-orange-500">
                            <span class="font-semibold">{{ $panelLabel }}</span>
                        </label>
                    @endforeach
                </div>
                <button type="submit" class="px-4 py-1.5 rounded-lg bg-orange-600 hover:bg-orange-700 text-white text-xs font-bold transition">
                    Save Layout
                </button>
            </form>
        </div>
    </div>

    <!-- 2. CATEGORY-SPECIFIC SMART KPI GRID -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-{{ min(6, max(4, count($kpiCards))) }} gap-4">
        @foreach ($kpiCards as $card)
            <div class="p-5 bg-gray-800/90 border border-gray-700/70 rounded-2xl shadow-xs">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-xs font-bold text-slate-300 uppercase tracking-wider">{{ $card['label'] }}</span>
                    @if (!empty($card['badge']))
                        <span class="inline-flex items-center text-xs font-bold px-2 py-0.5 rounded-full {{ ($card['badge_tone'] ?? 'emerald') === 'emerald' ? 'text-emerald-400 bg-emerald-950/60 border border-emerald-800/50' : 'text-rose-400 bg-rose-950/60 border border-rose-800/50' }}">
                            {{ $card['badge'] }}
                        </span>
                    @endif
                </div>
                <div class="mt-2 text-2xl font-black text-white">
                    {{ $card['value'] }}
                </div>
                <div class="mt-1 flex items-center justify-between text-xs text-slate-300 font-medium">
                    <span>{{ $card['subtext'] }}</span>
                    @if (!empty($card['accent_right']))
                        <span class="font-bold text-orange-400">{{ $card['accent_right'] }}</span>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <!-- 3. SABAYRA AI ASSISTANT SNAPSHOT & QUICK OPERATIONAL ACTIONS -->
    @if(($aiOverviewWidget['enabled'] ?? false))
        <div class="p-5 bg-[#0F1E3A] border border-[#1E3560] rounded-2xl shadow-xs text-white flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div class="space-y-1">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-sm font-extrabold text-amber-300">✨ SABAYRA AI Center</span>
                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-white/10 border border-white/15">
                        {{ number_format($aiOverviewWidget['remaining_credits']) }} / {{ number_format($aiOverviewWidget['total_credits']) }} Credits Left
                    </span>
                    @if($aiOverviewWidget['low_stock_alerts_count'] > 0)
                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-rose-500/20 text-rose-200 border border-rose-400/40">
                            {{ $aiOverviewWidget['low_stock_alerts_count'] }} Low-Stock Alerts
                        </span>
                    @endif
                </div>
                @if($aiOverviewWidget['latest_insight'])
                    <p class="text-xs text-slate-200">
                        <strong class="text-white">{{ $aiOverviewWidget['latest_insight']->title ?: 'Latest Insight' }}:</strong>
                        {{ \Illuminate\Support\Str::limit($aiOverviewWidget['latest_insight']->summary ?? '', 140) }}
                    </p>
                @else
                    <p class="text-xs text-slate-300">
                        Ask Business Assistant for {{ strtolower($categoryMeta['name_en']) }} insights, pricing strategy, and real inventory/sales forecasts.
                    </p>
                @endif
            </div>
            <div class="flex flex-wrap items-center gap-2 shrink-0">
                <a href="{{ route('business.ai.assistant') }}"
                   class="rounded-xl bg-orange-600 hover:bg-orange-700 px-3.5 py-2 text-xs font-bold text-white transition">
                    Ask AI Assistant
                </a>
                <a href="{{ route('business.ai.overview') }}"
                   class="rounded-xl bg-white/10 hover:bg-white/20 border border-white/15 px-3.5 py-2 text-xs font-bold text-white transition">
                    Open AI Center &rarr;
                </a>
            </div>
        </div>
    @endif

    <div class="p-5 bg-gray-800/90 border border-gray-700/70 rounded-2xl shadow-xs">
        <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-200 mb-3">
            Quick {{ $categoryMeta['name_en'] }} Actions
        </h3>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-3">
            @foreach ($quickActionButtons as $action)
                <a href="{{ $action['route'] }}"
                   class="p-3 rounded-xl bg-gray-900/70 hover:bg-gray-900 border border-gray-700/80 hover:border-orange-500/60 text-slate-200 hover:text-white font-bold text-xs flex flex-col items-center justify-center gap-1.5 transition text-center">
                    <span class="w-2 h-2 rounded-full bg-orange-400"></span>
                    <span>{{ $action['label'] }}</span>
                </a>
            @endforeach
        </div>
    </div>

    <!-- 4. COMPACT WORKSPACE STATS BAR -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
        <div class="p-4 bg-gray-800/90 border border-gray-700/70 rounded-2xl shadow-xs">
            <span class="text-[11px] font-bold text-slate-300 uppercase tracking-wider">{{ $terminology['catalog'] ?? 'Total Items' }}</span>
            <div class="mt-1 text-2xl font-black text-white">{{ $totalProducts }}</div>
            <span class="text-[11px] text-emerald-400 font-semibold">{{ $activeProducts }} active</span>
        </div>

        <div class="p-4 bg-gray-800/90 border border-gray-700/70 rounded-2xl shadow-xs">
            <span class="text-[11px] font-bold text-slate-300 uppercase tracking-wider">Featured</span>
            <div class="mt-1 text-2xl font-black text-orange-400">{{ $featuredProducts }}</div>
            <span class="text-[11px] text-slate-300 font-medium">highlighted</span>
        </div>

        <div class="p-4 bg-gray-800/90 border border-gray-700/70 rounded-2xl shadow-xs">
            <span class="text-[11px] font-bold text-slate-300 uppercase tracking-wider">Categories</span>
            <div class="mt-1 text-2xl font-black text-white">{{ $totalCategories }}</div>
            <span class="text-[11px] text-slate-300 font-medium">catalog sections</span>
        </div>

        <div class="p-4 bg-gray-800/90 border border-gray-700/70 rounded-2xl shadow-xs">
            <span class="text-[11px] font-bold text-slate-300 uppercase tracking-wider">QR / Store Views</span>
            <div class="mt-1 text-2xl font-black text-cyan-400">{{ number_format($totalMenuViews) }}</div>
            <span class="text-[11px] text-slate-300 font-medium">{{ $todayMenuViews }} views today</span>
        </div>

        <div class="p-4 bg-gray-800/90 border border-gray-700/70 rounded-2xl shadow-xs">
            <span class="text-[11px] font-bold text-slate-300 uppercase tracking-wider">New {{ $terminology['customer'] ?? 'Customers' }}</span>
            <div class="mt-1 text-2xl font-black text-purple-400">{{ $newCustomersThisWeek }}</div>
            <span class="text-[11px] text-slate-300 font-medium">this week</span>
        </div>

        <div class="p-4 bg-gray-800/90 border border-gray-700/70 rounded-2xl shadow-xs">
            <span class="text-[11px] font-bold text-slate-300 uppercase tracking-wider">Weekly Revenue</span>
            <div class="mt-1 text-2xl font-black text-emerald-400">${{ number_format($weeklyRevenue, 2) }}</div>
            <span class="text-[11px] text-slate-300 font-medium">{{ $weeklyOrdersCount }} orders</span>
        </div>
    </div>

    <!-- 5. DYNAMIC INDUSTRY PANELS (Charts, Appointments, Expiry, Variants, Best Sellers, Low Stock) -->
    @include('business.partials.dashboard-panels')

    <!-- QR CODE MODAL WITH 5G CELLULAR & LOCAL NETWORK RESOLUTION -->
    <div x-show="qrModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto bg-black/80 backdrop-blur-md flex items-center justify-center p-4">
        <div @click.away="qrModalOpen = false" class="bg-gray-800 border border-gray-700 rounded-3xl max-w-lg w-full p-6 text-center space-y-4 shadow-2xl"
             x-data="{
                 mode: (!['localhost', '127.0.0.1'].includes(window.location.hostname)) ? 'live' : 'cellular',
                 publicDomain: (function() {
                     let stored = localStorage.getItem('sabayra_public_url_{{ $business->id }}');
                     if (stored && stored.includes('kheatopup.store')) {
                         localStorage.removeItem('sabayra_public_url_{{ $business->id }}');
                         stored = null;
                     }
                     let themeVal = '{{ $business->theme_settings['public_url'] ?? '' }}';
                     if (themeVal.includes('kheatopup.store')) {
                         themeVal = '';
                     }
                     return stored || themeVal;
                 })(),
                 lanUrl: 'http://172.20.10.3:8000/{{ $business->slug }}',
                 localUrl: window.location.origin + '/{{ $business->slug }}',
                 tunnelCopied: false,
                 get currentUrl() {
                     if (!['localhost', '127.0.0.1'].includes(window.location.hostname)) {
                         return window.location.origin + '/{{ $business->slug }}';
                     }
                     if (this.mode === 'cellular') {
                         if (this.publicDomain && this.publicDomain.trim().length > 0 && !this.publicDomain.includes('kheatopup.store')) {
                             let d = this.publicDomain.trim().replace(/\/+$/, '');
                             if (!d.startsWith('http://') && !d.startsWith('https://')) {
                                 d = 'https://' + d;
                             }
                             return d + '/{{ $business->slug }}';
                         }
                         return window.location.origin + '/{{ $business->slug }}';
                     } else if (this.mode === 'wifi') {
                         return this.lanUrl;
                     } else {
                         return this.localUrl;
                     }
                 },
                 savePublicDomain() {
                     if (this.publicDomain && !this.publicDomain.includes('kheatopup.store')) {
                         localStorage.setItem('sabayra_public_url_{{ $business->id }}', this.publicDomain);
                     }
                 }
             }">
            <div class="flex items-center justify-between pb-2.5 border-b border-gray-700">
                <div class="flex items-center gap-2">
                    <span class="w-8 h-8 rounded-xl bg-orange-500/20 text-orange-400 border border-orange-500/30 flex items-center justify-center text-sm font-black">
                        QR
                    </span>
                    <div class="text-left">
                        <h3 class="text-sm font-bold text-white">Storefront QR Code</h3>
                        <p class="text-[11px] text-slate-300">Scan from table standees or counter</p>
                    </div>
                </div>
                <button @click="qrModalOpen = false" class="text-slate-400 hover:text-white p-1">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <!-- Scanning Mode Selector (5G Cellular vs Wi-Fi LAN vs Localhost) -->
            <div class="text-left space-y-1.5">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-300 block">Customer Network Connection:</span>
                <div class="grid grid-cols-3 gap-1.5 text-xs">
                    <button type="button" @click="mode = 'cellular'"
                            :class="mode === 'cellular' ? 'bg-orange-600 text-white font-bold border-orange-500 shadow-sm' : 'bg-gray-900/90 text-slate-300 hover:bg-gray-700 border-gray-700'"
                            class="px-2 py-2 rounded-xl border text-center transition text-[11px] flex flex-col items-center gap-0.5">
                        <span class="font-bold text-xs">🌐 5G Cellular</span>
                        <span class="text-[9px] opacity-80">Public Internet</span>
                    </button>
                    <button type="button" @click="mode = 'wifi'"
                            :class="mode === 'wifi' ? 'bg-orange-600 text-white font-bold border-orange-500 shadow-sm' : 'bg-gray-900/90 text-slate-300 hover:bg-gray-700 border-gray-700'"
                            class="px-2 py-2 rounded-xl border text-center transition text-[11px] flex flex-col items-center gap-0.5">
                        <span class="font-bold text-xs">📶 Wi-Fi LAN</span>
                        <span class="text-[9px] opacity-80">Same Network</span>
                    </button>
                    <button type="button" @click="mode = 'local'"
                            :class="mode === 'local' ? 'bg-orange-600 text-white font-bold border-orange-500 shadow-sm' : 'bg-gray-900/90 text-slate-300 hover:bg-gray-700 border-gray-700'"
                            class="px-2 py-2 rounded-xl border text-center transition text-[11px] flex flex-col items-center gap-0.5">
                        <span class="font-bold text-xs">💻 PC Local</span>
                        <span class="text-[9px] opacity-80">This Computer</span>
                    </button>
                </div>
            </div>

            <!-- Mode 1: 5G Cellular Domain / Tunnel Input -->
            <div x-show="mode === 'cellular'" class="text-left bg-gray-900/90 p-3 rounded-xl border border-gray-700/80 space-y-2">
                <div class="flex items-center justify-between">
                    <label class="text-[10px] font-bold uppercase tracking-wider text-emerald-400 flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        <span>Your Public Domain or Tunnel URL:</span>
                    </label>
                    <span class="text-[10px] text-slate-400">Works with Smart, Cellcard, Metfone</span>
                </div>
                <div class="flex gap-2">
                    <input type="text" x-model="publicDomain" @input="savePublicDomain()"
                           placeholder="e.g. https://xxxx.pinggy.link or https://sabayra.com"
                           class="w-full text-xs font-mono rounded-lg border border-gray-700 bg-gray-950 text-white px-3 py-2 focus:border-orange-500 focus:ring-0">
                </div>

                <!-- 1-Click Free Tunnel Command -->
                <div class="pt-2 border-t border-gray-800 text-[11px] text-slate-300 space-y-1.5">
                    <div class="flex items-center justify-between text-[10px] text-slate-400 font-bold uppercase">
                        <span>Instant Free Tunnel for 5G (No signup):</span>
                        <button type="button"
                                @click="navigator.clipboard.writeText('ssh -p 443 -R0:localhost:8000 a.pinggy.io'); tunnelCopied = true; setTimeout(() => tunnelCopied = false, 2500)"
                                class="text-orange-400 hover:text-orange-300 flex items-center gap-1">
                            <span x-text="tunnelCopied ? 'Copied!' : 'Copy Command'"></span>
                        </button>
                    </div>
                    <code class="block bg-black/60 p-2 rounded-lg text-[10px] font-mono text-amber-300 border border-gray-800 select-all">
                        ssh -p 443 -R0:localhost:8000 a.pinggy.io
                    </code>
                    <p class="text-[10px] text-slate-400">Run this in your terminal to get an instant HTTPS link, paste it above, and scan with 5G immediately!</p>
                </div>
            </div>

            <!-- Mode 2: Wi-Fi LAN Info -->
            <div x-show="mode === 'wifi'" class="text-left bg-gray-900/90 p-2.5 rounded-xl border border-gray-700/80 text-[11px] text-slate-300">
                <span class="text-amber-300 font-bold block mb-1">📶 Wi-Fi LAN Mode:</span>
                <span>Both your phone and this computer must be connected to the same Wi-Fi router.</span>
            </div>

            <!-- Mode 3: Local PC Info -->
            <div x-show="mode === 'local'" class="text-left bg-gray-900/90 p-2.5 rounded-xl border border-gray-700/80 text-[11px] text-slate-300">
                <span class="text-sky-300 font-bold block mb-1">💻 Local PC Mode:</span>
                <span>For testing directly in your computer's browser at <code class="text-white">localhost:8000</code>.</span>
            </div>

            <!-- QR Code Display Box -->
            <div class="p-4 bg-white rounded-2xl border-2 border-orange-500/30 inline-block shadow-lg">
                <img :src="'https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=' + encodeURIComponent(currentUrl) + '&color=000000'"
                     alt="Storefront QR" class="w-48 h-48 mx-auto">
            </div>

            <!-- URL Text Display -->
            <div class="text-[11px] text-slate-200 font-mono bg-gray-900 p-2.5 rounded-xl border border-gray-700/80 break-all select-all font-bold"
                 x-text="currentUrl">
            </div>

            <div class="flex gap-2 pt-2 border-t border-gray-700">
                <a :href="'https://api.qrserver.com/v1/create-qr-code/?size=800x800&data=' + encodeURIComponent(currentUrl)"
                   download="{{ $business->slug }}-qr.png" target="_blank"
                   class="flex-1 py-2.5 px-4 bg-orange-600 text-white rounded-xl text-xs font-bold hover:bg-orange-700 transition shadow-xs flex items-center justify-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    <span>Download High-Res QR</span>
                </a>
                <button @click="qrModalOpen = false" class="py-2.5 px-4 bg-gray-700 hover:bg-gray-600 text-white rounded-xl text-xs font-bold transition">
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
