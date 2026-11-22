<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-950">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Kitchen Display System (KDS) - {{ $business->name ?? 'SABAYRA' }}</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}" />

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Kantumruy+Pro:wght@400;500;600;700&family=Battambang:wght@400;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #0f172a; }
        ::-webkit-scrollbar-thumb { background: #334155; border-radius: 9999px; }
    </style>
</head>
<body class="h-full text-slate-100 bg-slate-950 font-sans antialiased overflow-hidden select-none"
      x-data="kitchenDisplay()"
      x-init="initKds()">

    <!-- Top Master Control Bar -->
    <header class="h-16 bg-slate-900 border-b border-slate-800 px-4 md:px-6 flex items-center justify-between shrink-0 shadow-lg z-20">
        <!-- Left: Brand & Business Name -->
        <div class="flex items-center space-x-3">
            <a href="{{ route('business.dashboard') }}" class="flex items-center space-x-2 text-slate-400 hover:text-white transition">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <div class="h-6 w-px bg-slate-800"></div>
            <div class="flex items-center space-x-2.5">
                <img src="{{ asset('images/sabayra-icon.png') }}" alt="SABAYRA" class="w-8 h-8 rounded-lg object-contain shrink-0" />
                <div>
                    <div class="flex items-center space-x-2">
                        <span class="text-orange-500 font-black tracking-tight text-lg leading-none">SABAYRA</span>
                        <span class="bg-red-500/20 text-red-400 text-[10px] font-mono font-bold px-1.5 py-0.5 rounded border border-red-500/30">KDS TERMINAL</span>
                    </div>
                    <p class="text-xs text-slate-400 font-medium truncate max-w-[200px] md:max-w-xs">{{ $business->name ?? 'Kitchen' }}</p>
                </div>
            </div>
        </div>

        <!-- Center: Status Quick Filters -->
        <div class="hidden md:flex items-center space-x-1.5 bg-slate-950/70 p-1 rounded-xl border border-slate-800">
            <button @click="filterType = 'all'"
                    :class="filterType === 'all' ? 'bg-orange-600 text-white font-bold shadow-sm' : 'text-slate-400 hover:text-slate-200'"
                    class="px-3 py-1.5 rounded-lg text-xs transition flex items-center space-x-1.5">
                <span>All Orders</span>
                <span class="bg-black/30 text-[10px] px-1.5 py-0.5 rounded-full font-mono" x-text="orders.length"></span>
            </button>
            <button @click="filterType = 'dine_in'"
                    :class="filterType === 'dine_in' ? 'bg-indigo-600 text-white font-bold shadow-sm' : 'text-slate-400 hover:text-slate-200'"
                    class="px-3 py-1.5 rounded-lg text-xs transition flex items-center space-x-1.5">
                <span>Dine-In</span>
                <span class="bg-black/30 text-[10px] px-1.5 py-0.5 rounded-full font-mono" x-text="orders.filter(o => o.order_type === 'dine_in').length"></span>
            </button>
            <button @click="filterType = 'takeaway'"
                    :class="filterType === 'takeaway' ? 'bg-emerald-600 text-white font-bold shadow-sm' : 'text-slate-400 hover:text-slate-200'"
                    class="px-3 py-1.5 rounded-lg text-xs transition flex items-center space-x-1.5">
                <span>Takeaway</span>
                <span class="bg-black/30 text-[10px] px-1.5 py-0.5 rounded-full font-mono" x-text="orders.filter(o => o.order_type === 'takeaway').length"></span>
            </button>
            <button @click="filterType = 'delivery'"
                    :class="filterType === 'delivery' ? 'bg-purple-600 text-white font-bold shadow-sm' : 'text-slate-400 hover:text-slate-200'"
                    class="px-3 py-1.5 rounded-lg text-xs transition flex items-center space-x-1.5">
                <span>Delivery</span>
                <span class="bg-black/30 text-[10px] px-1.5 py-0.5 rounded-full font-mono" x-text="orders.filter(o => o.order_type === 'delivery').length"></span>
            </button>
        </div>

        <!-- Right: Time, Sound & Controls -->
        <div class="flex items-center space-x-3">
            <!-- Clock -->
            <div class="hidden lg:flex flex-col text-right">
                <span class="text-sm font-mono font-bold text-slate-200" x-text="currentTime"></span>
                <span class="text-[10px] text-slate-400 uppercase tracking-wider">Phnom Penh Time</span>
            </div>

            <div class="h-6 w-px bg-slate-800 hidden lg:block"></div>

            <!-- Sound Bell Toggle -->
            <button @click="soundEnabled = !soundEnabled; if(soundEnabled) playChime();"
                    :class="soundEnabled ? 'bg-emerald-500/20 text-emerald-400 border-emerald-500/40' : 'bg-slate-800 text-slate-400 border-slate-700'"
                    class="p-2 rounded-xl border text-xs font-semibold flex items-center space-x-1.5 transition"
                    title="Audio Chime Alert on New Orders">
                <svg x-show="soundEnabled" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
                <svg x-show="!soundEnabled" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2"/>
                </svg>
                <span class="text-xs hidden sm:inline" x-text="soundEnabled ? 'Chime ON' : 'Muted'"></span>
            </button>

            <!-- Auto-refresh counter -->
            <button @click="fetchOrders()"
                    class="p-2 rounded-xl bg-slate-800 text-slate-300 hover:text-white border border-slate-700 flex items-center space-x-1.5 transition text-xs">
                <svg class="w-4 h-4" :class="isFetching ? 'animate-spin text-orange-400' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                <span class="font-mono text-[11px] hidden sm:inline" x-text="pollCountdown + 's'"></span>
            </button>

            <!-- Fullscreen Button -->
            <button @click="toggleFullscreen()"
                    class="p-2 rounded-xl bg-slate-800 text-slate-300 hover:text-white border border-slate-700 transition"
                    title="Toggle Fullscreen">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/>
                </svg>
            </button>
        </div>
    </header>

    <!-- Main Live Ticket Stage -->
    <main class="h-[calc(100vh-4rem)] p-4 md:p-6 overflow-x-auto overflow-y-hidden">
        <!-- Empty State -->
        <div x-show="filteredOrders().length === 0" x-cloak
             class="h-full flex flex-col items-center justify-center text-center p-8">
            <div class="w-20 h-20 rounded-2xl bg-slate-900 border border-slate-800 flex items-center justify-center text-emerald-400 mb-4 shadow-xl">
                <svg class="w-10 h-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h2 class="text-xl font-bold text-slate-200">Kitchen Queue All Clear!</h2>
            <p class="text-slate-400 text-sm mt-1 max-w-sm">No pending or cooking tickets at this moment. New incoming orders will appear here automatically with sound alert.</p>
        </div>

        <!-- Orders Grid / Carousel Columns -->
        <div x-show="filteredOrders().length > 0"
             class="flex space-x-4 h-full pb-2">
            <template x-for="order in filteredOrders()" :key="order.id">
                <div class="w-80 sm:w-96 shrink-0 h-full flex flex-col rounded-2xl border shadow-2xl overflow-hidden transition-all duration-300"
                     :class="{
                        'bg-slate-900 border-amber-500/50 shadow-amber-500/10': order.status === 'confirmed',
                        'bg-slate-900 border-blue-500/50 shadow-blue-500/10': order.status === 'preparing',
                        'bg-slate-900 border-emerald-500/50 shadow-emerald-500/10': order.status === 'ready',
                        'border-red-500 ring-2 ring-red-500/30 animate-pulse': order.elapsed_minutes >= 15
                     }">

                    <!-- Ticket Header -->
                    <div class="p-3.5 border-b border-slate-800 shrink-0"
                         :class="{
                            'bg-amber-500/10': order.status === 'confirmed',
                            'bg-blue-500/10': order.status === 'preparing',
                            'bg-emerald-500/10': order.status === 'ready'
                         }">
                        <div class="flex items-center justify-between">
                            <span class="font-mono font-black text-lg tracking-wider"
                                  :class="{
                                    'text-amber-400': order.status === 'confirmed',
                                    'text-blue-400': order.status === 'preparing',
                                    'text-emerald-400': order.status === 'ready'
                                  }"
                                  x-text="'#' + order.order_number.slice(-4)">
                            </span>

                            <div class="flex items-center space-x-1.5">
                                <!-- Order Type Badge -->
                                <span class="text-[11px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full"
                                      :class="{
                                        'bg-indigo-500/20 text-indigo-300 border border-indigo-500/30': order.order_type === 'dine_in',
                                        'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30': order.order_type === 'takeaway',
                                        'bg-purple-500/20 text-purple-300 border border-purple-500/30': order.order_type === 'delivery'
                                      }"
                                      x-text="order.order_type.replace('_', ' ')">
                                </span>

                                <!-- Status Badge -->
                                <span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded-full font-mono"
                                      :class="{
                                        'bg-amber-500 text-slate-950': order.status === 'confirmed',
                                        'bg-blue-500 text-white': order.status === 'preparing',
                                        'bg-emerald-500 text-white': order.status === 'ready'
                                      }"
                                      x-text="order.status">
                                </span>
                            </div>
                        </div>

                        <!-- Sub-header: Table / Customer & Elapsed Timer -->
                        <div class="mt-2 flex items-center justify-between text-xs">
                            <div class="flex items-center space-x-1.5 text-slate-300 font-semibold truncate">
                                <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                                <span x-text="order.table_name || order.customer_name || 'Counter Order'"></span>
                            </div>

                            <!-- Elapsed Timer -->
                            <div class="flex items-center space-x-1 font-mono font-bold text-xs"
                                 :class="{
                                    'text-slate-400': order.elapsed_minutes < 10,
                                    'text-amber-400': order.elapsed_minutes >= 10 && order.elapsed_minutes < 15,
                                    'text-red-400 font-black animate-pulse': order.elapsed_minutes >= 15
                                 }">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span x-text="order.elapsed_minutes + 'm'"></span>
                            </div>
                        </div>

                        <!-- Special Kitchen Order Notes -->
                        <template x-if="order.notes">
                            <div class="mt-2 p-1.5 rounded-lg bg-red-950/40 border border-red-500/30 text-[11px] text-red-300 flex items-start space-x-1.5">
                                <span class="font-bold text-red-400">NOTE:</span>
                                <span class="break-words font-medium" x-text="order.notes"></span>
                            </div>
                        </template>
                    </div>

                    <!-- Items List (Scrollable Area) -->
                    <div class="p-3.5 space-y-3 overflow-y-auto flex-1 divide-y divide-slate-800/80">
                        <template x-for="item in order.items" :key="item.id">
                            <div class="pt-2 first:pt-0">
                                <div class="flex items-start justify-between">
                                    <div class="flex items-start space-x-2">
                                        <span class="w-6 h-6 rounded-md bg-slate-800 text-orange-400 font-mono font-black flex items-center justify-center text-xs shrink-0 border border-slate-700"
                                              x-text="item.quantity + 'x'">
                                        </span>
                                        <div>
                                            <p class="font-bold text-sm text-slate-100 leading-snug" x-text="item.product_name"></p>
                                            <template x-if="item.variant_name">
                                                <p class="text-xs text-orange-400/90 font-medium" x-text="'Option: ' + item.variant_name"></p>
                                            </template>
                                        </div>
                                    </div>
                                </div>

                                <!-- Addons List -->
                                <template x-if="item.addons && item.addons.length > 0">
                                    <div class="ml-8 mt-1 space-y-0.5">
                                        <template x-for="addon in item.addons" :key="addon.addon_name">
                                            <p class="text-[11px] text-slate-400 flex items-center space-x-1">
                                                <span class="text-slate-500">+</span>
                                                <span x-text="addon.addon_name"></span>
                                                <span x-show="addon.quantity > 1" class="font-mono text-slate-500" x-text="'(' + addon.quantity + 'x)'"></span>
                                            </p>
                                        </template>
                                    </div>
                                </template>

                                <!-- Item Specific Note -->
                                <template x-if="item.notes">
                                    <p class="ml-8 mt-1 text-[11px] text-amber-300/90 italic bg-amber-950/20 px-1.5 py-0.5 rounded border border-amber-500/20 inline-block"
                                       x-text="'Note: ' + item.notes">
                                    </p>
                                </template>
                            </div>
                        </template>
                    </div>

                    <!-- Action Bump Button Footer -->
                    <div class="p-3 bg-slate-950/80 border-t border-slate-800 shrink-0 flex items-center space-x-2">
                        <!-- Transition Bump Action -->
                        <template x-if="order.status === 'confirmed'">
                            <button @click="bumpOrder(order, 'preparing')"
                                    :disabled="order.isBumping"
                                    class="w-full py-3 px-4 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 active:scale-95 text-white font-black text-sm uppercase tracking-wider flex items-center justify-center space-x-2 transition shadow-lg shadow-blue-600/30">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>Start Preparing</span>
                            </button>
                        </template>

                        <template x-if="order.status === 'preparing'">
                            <button @click="bumpOrder(order, 'ready')"
                                    :disabled="order.isBumping"
                                    class="w-full py-3 px-4 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 active:scale-95 text-white font-black text-sm uppercase tracking-wider flex items-center justify-center space-x-2 transition shadow-lg shadow-emerald-600/30">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <span>Mark Ready</span>
                            </button>
                        </template>

                        <template x-if="order.status === 'ready'">
                            <button @click="bumpOrder(order, 'completed')"
                                    :disabled="order.isBumping"
                                    class="w-full py-3 px-4 rounded-xl bg-gradient-to-r from-slate-700 to-slate-800 hover:from-slate-600 hover:to-slate-700 active:scale-95 text-white font-black text-sm uppercase tracking-wider flex items-center justify-center space-x-2 transition border border-slate-600">
                                <svg class="w-4 h-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span>Served / Completed</span>
                            </button>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </main>

    <script>
        function kitchenDisplay() {
            return {
                orders: @json($formattedOrders),
                filterType: 'all',
                soundEnabled: true,
                currentTime: '',
                pollCountdown: 8,
                pollInterval: null,
                clockInterval: null,
                isFetching: false,
                knownOrderIds: new Set(),

                initKds() {
                    this.orders.forEach(o => this.knownOrderIds.add(o.id));
                    this.updateClock();
                    this.clockInterval = setInterval(() => this.updateClock(), 1000);

                    // Poll every 8 seconds
                    this.pollInterval = setInterval(() => {
                        this.pollCountdown--;
                        if (this.pollCountdown <= 0) {
                            this.fetchOrders();
                            this.pollCountdown = 8;
                        }
                    }, 1000);
                },

                updateClock() {
                    const now = new Date();
                    this.currentTime = now.toLocaleTimeString('en-US', {
                        timeZone: 'Asia/Phnom_Penh',
                        hour12: false,
                        hour: '2-digit',
                        minute: '2-digit',
                        second: '2-digit'
                    });

                    // Update elapsed minutes on each order locally
                    this.orders.forEach(order => {
                        const created = new Date(order.created_at);
                        order.elapsed_minutes = Math.max(0, Math.floor((now - created) / 60000));
                    });
                },

                filteredOrders() {
                    if (this.filterType === 'all') return this.orders;
                    return this.orders.filter(o => o.order_type === this.filterType);
                },

                async fetchOrders() {
                    if (this.isFetching) return;
                    this.isFetching = true;

                    try {
                        const res = await fetch('{{ route("business.kitchen.api") }}', {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });
                        if (!res.ok) return;
                        const data = await res.json();

                        // Check for new orders to chime
                        let hasNewOrders = false;
                        data.orders.forEach(order => {
                            if (!this.knownOrderIds.has(order.id)) {
                                this.knownOrderIds.add(order.id);
                                hasNewOrders = true;
                            }
                        });

                        if (hasNewOrders && this.soundEnabled) {
                            this.playChime();
                        }

                        this.orders = data.orders;
                    } catch (err) {
                        console.error('KDS poll error', err);
                    } finally {
                        this.isFetching = false;
                    }
                },

                async bumpOrder(order, targetStatus) {
                    order.isBumping = true;

                    try {
                        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                        const res = await fetch(`/app/kitchen/orders/${order.id}/bump`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify({ target_status: targetStatus })
                        });

                        const data = await res.json();
                        if (data.success) {
                            if (targetStatus === 'completed') {
                                // Remove from screen
                                this.orders = this.orders.filter(o => o.id !== order.id);
                            } else {
                                order.status = targetStatus;
                            }
                        }
                    } catch (err) {
                        console.error('Bump error', err);
                    } finally {
                        order.isBumping = false;
                    }
                },

                playChime() {
                    try {
                        const ctx = new (window.AudioContext || window.webkitAudioContext)();
                        const now = ctx.currentTime;

                        // Tone 1
                        const osc1 = ctx.createOscillator();
                        const gain1 = ctx.createGain();
                        osc1.type = 'sine';
                        osc1.frequency.setValueAtTime(587.33, now); // D5
                        gain1.gain.setValueAtTime(0.3, now);
                        gain1.gain.exponentialRampToValueAtTime(0.001, now + 0.3);
                        osc1.connect(gain1);
                        gain1.connect(ctx.destination);
                        osc1.start(now);
                        osc1.stop(now + 0.3);

                        // Tone 2 (higher harmony)
                        const osc2 = ctx.createOscillator();
                        const gain2 = ctx.createGain();
                        osc2.type = 'sine';
                        osc2.frequency.setValueAtTime(880, now + 0.15); // A5
                        gain2.gain.setValueAtTime(0.3, now + 0.15);
                        gain2.gain.exponentialRampToValueAtTime(0.001, now + 0.5);
                        osc2.connect(gain2);
                        gain2.connect(ctx.destination);
                        osc2.start(now + 0.15);
                        osc2.stop(now + 0.5);
                    } catch (e) {
                        // AudioContext may be blocked before user gesture
                    }
                },

                toggleFullscreen() {
                    if (!document.fullscreenElement) {
                        document.documentElement.requestFullscreen().catch(() => {});
                    } else {
                        if (document.exitFullscreen) {
                            document.exitFullscreen().catch(() => {});
                        }
                    }
                }
            };
        }
    </script>
</body>
</html>
