<x-business-layout title="Order Flow Guide" heading="Order Flow & Operations Guide">
<div class="space-y-8" x-data="{ activeFaq: null, copied: false }">

    <!-- PAGE HEADER & VISUAL WORKFLOW BANNER -->
    <div class="bg-gradient-to-r from-orange-600 via-amber-600 to-orange-700 rounded-3xl p-6 sm:p-8 text-white shadow-lg relative overflow-hidden">
        <div class="relative z-10 space-y-4 max-w-4xl">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-bold bg-white/20 backdrop-blur-md text-white border border-white/30">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                <span>Interactive Operational Flow Architecture</span>
            </div>
            <div>
                <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-white">Order Flow Guide</h1>
                <p class="text-sm text-orange-100 font-medium mt-1 leading-relaxed">
                    Complete workflow from customer ordering to kitchen preparation, payment, and invoice generation.
                </p>
            </div>

            <!-- VISUAL WORKFLOW PIPELINE -->
            <div class="pt-4">
                <div class="flex items-center gap-1 sm:gap-2 overflow-x-auto pb-2 scrollbar-none text-xs font-bold text-white/90">
                    <div class="flex items-center gap-1.5 shrink-0 px-3 py-1.5 bg-black/25 rounded-xl border border-white/20">
                        <span class="w-5 h-5 rounded-full bg-white text-orange-600 flex items-center justify-center text-[10px] font-black">1</span>
                        <span>Customer Order</span>
                    </div>
                    <svg class="w-4 h-4 shrink-0 text-white/60" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    
                    <div class="flex items-center gap-1.5 shrink-0 px-3 py-1.5 bg-black/25 rounded-xl border border-white/20">
                        <span class="w-5 h-5 rounded-full bg-white text-orange-600 flex items-center justify-center text-[10px] font-black">2</span>
                        <span>Staff Confirmation</span>
                    </div>
                    <svg class="w-4 h-4 shrink-0 text-white/60" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>

                    <div class="flex items-center gap-1.5 shrink-0 px-3 py-1.5 bg-black/25 rounded-xl border border-white/20">
                        <span class="w-5 h-5 rounded-full bg-white text-orange-600 flex items-center justify-center text-[10px] font-black">3</span>
                        <span>Kitchen Preparation</span>
                    </div>
                    <svg class="w-4 h-4 shrink-0 text-white/60" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>

                    <div class="flex items-center gap-1.5 shrink-0 px-3 py-1.5 bg-black/25 rounded-xl border border-white/20">
                        <span class="w-5 h-5 rounded-full bg-white text-orange-600 flex items-center justify-center text-[10px] font-black">4</span>
                        <span>Ready to Serve</span>
                    </div>
                    <svg class="w-4 h-4 shrink-0 text-white/60" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>

                    <div class="flex items-center gap-1.5 shrink-0 px-3 py-1.5 bg-black/25 rounded-xl border border-white/20">
                        <span class="w-5 h-5 rounded-full bg-white text-orange-600 flex items-center justify-center text-[10px] font-black">5</span>
                        <span>Invoice & Payment</span>
                    </div>
                    <svg class="w-4 h-4 shrink-0 text-white/60" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>

                    <div class="flex items-center gap-1.5 shrink-0 px-3 py-1.5 bg-black/35 rounded-xl border border-emerald-400/40 text-emerald-200">
                        <span class="w-5 h-5 rounded-full bg-emerald-400 text-gray-900 flex items-center justify-center text-[10px] font-black">✓</span>
                        <span>Complete</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- QUICK LINKS FOR YOUR SHOP -->
    <div class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-3xl p-6 shadow-xs space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider">Quick Links for Your Shop</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">Direct shortcuts to active store operations and customer touchpoints</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-xs text-gray-500 font-mono truncate max-w-xs bg-gray-50 dark:bg-gray-700/50 px-3 py-1.5 rounded-xl border border-gray-200 dark:border-gray-600">
                    {{ $publicMenuUrl }}
                </span>
                <button
                    @click="navigator.clipboard.writeText('{{ $publicMenuUrl }}'); copied = true; setTimeout(() => copied = false, 2500)"
                    class="px-3 py-1.5 bg-orange-600 hover:bg-orange-700 text-white font-bold text-xs rounded-xl shadow-xs transition shrink-0 flex items-center gap-1"
                >
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>
                    <span x-text="copied ? 'Copied!' : 'Copy URL'"></span>
                </button>
            </div>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-3 pt-2">
            <!-- View My Menu -->
            <a href="{{ $publicMenuUrl }}" target="_blank" class="p-3 bg-gray-50 dark:bg-gray-750 hover:bg-orange-50 dark:hover:bg-orange-950/20 border border-gray-200/80 dark:border-gray-700 rounded-2xl text-center group transition">
                <div class="w-8 h-8 mx-auto rounded-xl bg-orange-100 dark:bg-orange-900/40 text-orange-600 dark:text-orange-400 flex items-center justify-center mb-1.5 group-hover:scale-110 transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                </div>
                <span class="text-xs font-bold text-gray-800 dark:text-gray-200 block truncate">View Menu</span>
                <span class="text-[10px] text-gray-400 block">Customer Web</span>
            </a>

            <!-- Live Orders -->
            <a href="{{ route('business.orders.live') }}" class="p-3 bg-gray-50 dark:bg-gray-750 hover:bg-orange-50 dark:hover:bg-orange-950/20 border border-gray-200/80 dark:border-gray-700 rounded-2xl text-center group transition">
                <div class="w-8 h-8 mx-auto rounded-xl bg-amber-100 dark:bg-amber-900/40 text-amber-600 dark:text-amber-400 flex items-center justify-center mb-1.5 group-hover:scale-110 transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                </div>
                <span class="text-xs font-bold text-gray-800 dark:text-gray-200 block truncate">Live Orders</span>
                <span class="text-[10px] text-gray-400 block">Kanban Stream</span>
            </a>

            <!-- Kitchen Display -->
            <a href="{{ route('business.kitchen.index') }}" target="_blank" class="p-3 bg-gray-50 dark:bg-gray-750 hover:bg-orange-50 dark:hover:bg-orange-950/20 border border-gray-200/80 dark:border-gray-700 rounded-2xl text-center group transition">
                <div class="w-8 h-8 mx-auto rounded-xl bg-red-100 dark:bg-red-900/40 text-red-600 dark:text-red-400 flex items-center justify-center mb-1.5 group-hover:scale-110 transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                </div>
                <span class="text-xs font-bold text-gray-800 dark:text-gray-200 block truncate">Kitchen (KDS)</span>
                <span class="text-[10px] text-gray-400 block">Chef Screen</span>
            </a>

            <!-- Touch POS -->
            <a href="{{ route('business.pos.index') }}" class="p-3 bg-gray-50 dark:bg-gray-750 hover:bg-orange-50 dark:hover:bg-orange-950/20 border border-gray-200/80 dark:border-gray-700 rounded-2xl text-center group transition">
                <div class="w-8 h-8 mx-auto rounded-xl bg-blue-100 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400 flex items-center justify-center mb-1.5 group-hover:scale-110 transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                </div>
                <span class="text-xs font-bold text-gray-800 dark:text-gray-200 block truncate">Touch POS</span>
                <span class="text-[10px] text-gray-400 block">Cashier Desk</span>
            </a>

            <!-- QR Studio -->
            <a href="{{ route('business.qr-studio.index') }}" class="p-3 bg-gray-50 dark:bg-gray-750 hover:bg-orange-50 dark:hover:bg-orange-950/20 border border-gray-200/80 dark:border-gray-700 rounded-2xl text-center group transition">
                <div class="w-8 h-8 mx-auto rounded-xl bg-purple-100 dark:bg-purple-900/40 text-purple-600 dark:text-purple-400 flex items-center justify-center mb-1.5 group-hover:scale-110 transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                </div>
                <span class="text-xs font-bold text-gray-800 dark:text-gray-200 block truncate">QR Studio</span>
                <span class="text-[10px] text-gray-400 block">Standees & Menus</span>
            </a>

            <!-- Printers -->
            <a href="{{ route('business.printers.index') }}" class="p-3 bg-gray-50 dark:bg-gray-750 hover:bg-orange-50 dark:hover:bg-orange-950/20 border border-gray-200/80 dark:border-gray-700 rounded-2xl text-center group transition">
                <div class="w-8 h-8 mx-auto rounded-xl bg-emerald-100 dark:bg-emerald-900/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center mb-1.5 group-hover:scale-110 transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                </div>
                <span class="text-xs font-bold text-gray-800 dark:text-gray-200 block truncate">Printers</span>
                <span class="text-[10px] text-gray-400 block">Receipts & KDS</span>
            </a>

            <!-- Reports -->
            <a href="{{ route('business.reports.index') }}" class="p-3 bg-gray-50 dark:bg-gray-750 hover:bg-orange-50 dark:hover:bg-orange-950/20 border border-gray-200/80 dark:border-gray-700 rounded-2xl text-center group transition">
                <div class="w-8 h-8 mx-auto rounded-xl bg-indigo-100 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-400 flex items-center justify-center mb-1.5 group-hover:scale-110 transition">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                </div>
                <span class="text-xs font-bold text-gray-800 dark:text-gray-200 block truncate">Reports</span>
                <span class="text-[10px] text-gray-400 block">Sales & Insights</span>
            </a>
        </div>
    </div>

    <!-- 6-STEP OPERATIONAL WALKTHROUGH -->
    <div class="space-y-4">
        <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider">Detailed Operational Steps</h3>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

            <!-- STEP 1: CUSTOMER ORDERS -->
            <div class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-3xl p-6 shadow-xs flex flex-col justify-between hover:border-orange-500/40 transition">
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="w-8 h-8 rounded-2xl bg-amber-500/10 text-amber-600 dark:text-amber-400 font-black text-sm flex items-center justify-center border border-amber-500/20">
                            1
                        </span>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-mono font-bold bg-amber-50 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-700">
                            Status: pending
                        </span>
                    </div>
                    <h4 class="text-base font-bold text-gray-900 dark:text-white">Customer Orders & Order Placement</h4>
                    <p class="text-xs text-gray-600 dark:text-gray-400 leading-relaxed">
                        Guest scans the QR code at their table or counter. The storefront opens instantly in mobile browser without installing any app. Customer browses food, sets options/sugar/notes, adds to cart, and submits order.
                    </p>
                </div>
                <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700/60 space-y-2">
                    <div class="flex flex-wrap gap-1.5">
                        <span class="px-2 py-0.5 rounded-lg text-[10px] font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">Table QR Standee</span>
                        <span class="px-2 py-0.5 rounded-lg text-[10px] font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">Web Mobile Menu</span>
                        <span class="px-2 py-0.5 rounded-lg text-[10px] font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">Live Cart</span>
                    </div>
                    <p class="text-[11px] text-amber-600 dark:text-amber-400 font-semibold">
                        &rarr; Order registered with Table / Takeaway ID
                    </p>
                </div>
            </div>

            <!-- STEP 2: STAFF CONFIRMATION -->
            <div class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-3xl p-6 shadow-xs flex flex-col justify-between hover:border-orange-500/40 transition">
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="w-8 h-8 rounded-2xl bg-blue-500/10 text-blue-600 dark:text-blue-400 font-black text-sm flex items-center justify-center border border-blue-500/20">
                            2
                        </span>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-mono font-bold bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 border border-blue-200 dark:border-blue-700">
                            Status: confirmed
                        </span>
                    </div>
                    <h4 class="text-base font-bold text-gray-900 dark:text-white">Staff Confirmation</h4>
                    <p class="text-xs text-gray-600 dark:text-gray-400 leading-relaxed">
                        A real-time notification with sound alert rings on cashier POS and Live Orders feed. Staff reviews customer requests, checks kitchen availability, confirms order, and dispatches it for preparation.
                    </p>
                </div>
                <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700/60 space-y-2">
                    <div class="flex flex-wrap gap-1.5">
                        <span class="px-2 py-0.5 rounded-lg text-[10px] font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">Audio Chime Alert</span>
                        <span class="px-2 py-0.5 rounded-lg text-[10px] font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">Live Orders Feed</span>
                        <span class="px-2 py-0.5 rounded-lg text-[10px] font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">Item Modifications</span>
                    </div>
                    <p class="text-[11px] text-blue-600 dark:text-blue-400 font-semibold">
                        &rarr; Ticket routed to Kitchen / Barista station
                    </p>
                </div>
            </div>

            <!-- STEP 3: KITCHEN PREPARATION -->
            <div class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-3xl p-6 shadow-xs flex flex-col justify-between hover:border-orange-500/40 transition">
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="w-8 h-8 rounded-2xl bg-purple-500/10 text-purple-600 dark:text-purple-400 font-black text-sm flex items-center justify-center border border-purple-500/20">
                            3
                        </span>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-mono font-bold bg-purple-50 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 border border-purple-200 dark:border-purple-700">
                            Status: preparing
                        </span>
                    </div>
                    <h4 class="text-base font-bold text-gray-900 dark:text-white">Kitchen Preparation</h4>
                    <p class="text-xs text-gray-600 dark:text-gray-400 leading-relaxed">
                        Ticket appears on the chef's Kitchen Display System (KDS) and prints to 80mm thermal kitchen printer. Kitchen staff clicks "Start Preparing", and the preparation timer measures cooking speed.
                    </p>
                </div>
                <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700/60 space-y-2">
                    <div class="flex flex-wrap gap-1.5">
                        <span class="px-2 py-0.5 rounded-lg text-[10px] font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">Digital KDS Screen</span>
                        <span class="px-2 py-0.5 rounded-lg text-[10px] font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">80mm Thermal Docket</span>
                        <span class="px-2 py-0.5 rounded-lg text-[10px] font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">Prep Speed Timer</span>
                    </div>
                    <p class="text-[11px] text-purple-600 dark:text-purple-400 font-semibold">
                        &rarr; Color changes from green to red if delayed
                    </p>
                </div>
            </div>

            <!-- STEP 4: READY TO SERVE -->
            <div class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-3xl p-6 shadow-xs flex flex-col justify-between hover:border-orange-500/40 transition">
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="w-8 h-8 rounded-2xl bg-cyan-500/10 text-cyan-600 dark:text-cyan-400 font-black text-sm flex items-center justify-center border border-cyan-500/20">
                            4
                        </span>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-mono font-bold bg-cyan-50 dark:bg-cyan-900/30 text-cyan-700 dark:text-cyan-300 border border-cyan-200 dark:border-cyan-700">
                            Status: ready
                        </span>
                    </div>
                    <h4 class="text-base font-bold text-gray-900 dark:text-white">Ready to Serve</h4>
                    <p class="text-xs text-gray-600 dark:text-gray-400 leading-relaxed">
                        Kitchen chef bumps card to "Ready". Floor staff or delivery runners receive visual alert with Table Number. Food is picked up at the pass and served promptly to the customer.
                    </p>
                </div>
                <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700/60 space-y-2">
                    <div class="flex flex-wrap gap-1.5">
                        <span class="px-2 py-0.5 rounded-lg text-[10px] font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">Floor Runner Alert</span>
                        <span class="px-2 py-0.5 rounded-lg text-[10px] font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">Table Dispatch</span>
                        <span class="px-2 py-0.5 rounded-lg text-[10px] font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">Takeaway Pickup</span>
                    </div>
                    <p class="text-[11px] text-cyan-600 dark:text-cyan-400 font-semibold">
                        &rarr; Customer served hot & fresh
                    </p>
                </div>
            </div>

            <!-- STEP 5: INVOICE & PAYMENT -->
            <div class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-3xl p-6 shadow-xs flex flex-col justify-between hover:border-orange-500/40 transition">
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="w-8 h-8 rounded-2xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 font-black text-sm flex items-center justify-center border border-emerald-500/20">
                            5
                        </span>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-mono font-bold bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-700">
                            Status: paid
                        </span>
                    </div>
                    <h4 class="text-base font-bold text-gray-900 dark:text-white">Invoice & Payment</h4>
                    <p class="text-xs text-gray-600 dark:text-gray-400 leading-relaxed">
                        Cashier presents bill with dual USD ($) and Khmer Riel (៛) totals. Customer pays with Cash or scans Bakong KHQR dynamic QR code. System automatically generates printed 58mm/80mm customer invoice.
                    </p>
                </div>
                <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700/60 space-y-2">
                    <div class="flex flex-wrap gap-1.5">
                        <span class="px-2 py-0.5 rounded-lg text-[10px] font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">Bakong KHQR Dynamic</span>
                        <span class="px-2 py-0.5 rounded-lg text-[10px] font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">USD & KHR Dual Rate</span>
                        <span class="px-2 py-0.5 rounded-lg text-[10px] font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">Thermal Tax Receipt</span>
                    </div>
                    <p class="text-[11px] text-emerald-600 dark:text-emerald-400 font-semibold">
                        &rarr; Instant payment ledger & digital receipt
                    </p>
                </div>
            </div>

            <!-- STEP 6: COMPLETE & STOCK UPDATE -->
            <div class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-3xl p-6 shadow-xs flex flex-col justify-between hover:border-orange-500/40 transition">
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="w-8 h-8 rounded-2xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400 font-black text-sm flex items-center justify-center border border-indigo-500/20">
                            6
                        </span>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-mono font-bold bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-700">
                            Status: completed
                        </span>
                    </div>
                    <h4 class="text-base font-bold text-gray-900 dark:text-white">Complete & Analytics</h4>
                    <p class="text-xs text-gray-600 dark:text-gray-400 leading-relaxed">
                        Order is completed. Dining table automatically resets to available for next guests. Recipe ingredients auto-deplete from inventory, and revenue analytics update in real time on the owner's dashboard.
                    </p>
                </div>
                <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700/60 space-y-2">
                    <div class="flex flex-wrap gap-1.5">
                        <span class="px-2 py-0.5 rounded-lg text-[10px] font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">Inventory Depletion</span>
                        <span class="px-2 py-0.5 rounded-lg text-[10px] font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">Instant Table Release</span>
                        <span class="px-2 py-0.5 rounded-lg text-[10px] font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">Daily Sales Sync</span>
                    </div>
                    <p class="text-[11px] text-indigo-600 dark:text-indigo-400 font-semibold">
                        &rarr; Full transaction archived for tax reports
                    </p>
                </div>
            </div>

        </div>
    </div>

    <!-- RECOMMENDED HARDWARE SETUP -->
    <div class="space-y-4">
        <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider">Recommended Hardware Setup</h3>
        <p class="text-xs text-gray-500 dark:text-gray-400">Scale SABAYRA seamlessly across mobile phones, tablets, thermal printers, and desktop PCs</p>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

            <!-- Card 1: Owner / Manager Device -->
            <div class="p-6 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-3xl shadow-xs space-y-3">
                <div class="w-10 h-10 rounded-2xl bg-orange-100 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400 flex items-center justify-center font-bold">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                </div>
                <h4 class="text-base font-bold text-gray-900 dark:text-white">Owner / Manager Device</h4>
                <p class="text-xs text-gray-600 dark:text-gray-400 leading-relaxed">
                    Laptop, Desktop PC, or iPad running Chrome/Safari. Use for product catalog management, staff permissions, inventory recipes, live sales audits, and exportable financial reports.
                </p>
                <div class="text-[11px] text-gray-500 space-y-1 pt-2 border-t border-gray-100 dark:border-gray-700">
                    <p>• <strong>Recommended:</strong> Any modern web browser</p>
                    <p>• <strong>Network:</strong> Any internet connection (Wi-Fi or 4G/5G)</p>
                </div>
            </div>

            <!-- Card 2: Kitchen Display Device -->
            <div class="p-6 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-3xl shadow-xs space-y-3">
                <div class="w-10 h-10 rounded-2xl bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 flex items-center justify-center font-bold">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                </div>
                <h4 class="text-base font-bold text-gray-900 dark:text-white">Kitchen Display (KDS)</h4>
                <p class="text-xs text-gray-600 dark:text-gray-400 leading-relaxed">
                    Dedicated 10-inch or larger Android Tablet, iPad, or Touch Screen mounted on the kitchen or barista counter. Displays real-time tickets, elapsed cook times, and one-tap bump status.
                </p>
                <div class="text-[11px] text-gray-500 space-y-1 pt-2 border-t border-gray-100 dark:border-gray-700">
                    <p>• <strong>Screen:</strong> Fullscreen browser mode (Kiosk)</p>
                    <p>• <strong>Audio:</strong> Speaker enabled for loud order arrival chime</p>
                </div>
            </div>

            <!-- Card 3: POS & Thermal Printer -->
            <div class="p-6 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-3xl shadow-xs space-y-3">
                <div class="w-10 h-10 rounded-2xl bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-bold">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                </div>
                <h4 class="text-base font-bold text-gray-900 dark:text-white">Cashier POS & Thermal Printer</h4>
                <p class="text-xs text-gray-600 dark:text-gray-400 leading-relaxed">
                    Touch POS terminal or iPad paired with 58mm or 80mm ESC/POS thermal receipt printer (LAN/Wi-Fi/USB/Bluetooth). Instant customer receipts and kitchen order tickets.
                </p>
                <div class="text-[11px] text-gray-500 space-y-1 pt-2 border-t border-gray-100 dark:border-gray-700">
                    <p>• <strong>Paper Size:</strong> 80mm standard or 58mm compact roll</p>
                    <p>• <strong>Payment:</strong> Front-facing Bakong KHQR standee / display</p>
                </div>
            </div>

        </div>
    </div>

    <!-- EXPANDABLE OPERATIONAL FAQS (ALPINE.JS) -->
    <div class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-3xl p-6 shadow-xs space-y-4">
        <div>
            <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider">Frequently Asked Questions</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400">Everything your team needs to know about SABAYRA operations</p>
        </div>

        <div class="space-y-3 pt-2">

            <!-- FAQ 1 -->
            <div class="border border-gray-100 dark:border-gray-700/60 rounded-2xl overflow-hidden">
                <button
                    @click="activeFaq = activeFaq === 1 ? null : 1"
                    class="w-full flex items-center justify-between p-4 bg-gray-50/50 dark:bg-gray-750/50 text-left font-bold text-sm text-gray-900 dark:text-white hover:bg-gray-100/50 dark:hover:bg-gray-700 transition"
                >
                    <span>1. Do customers need to install an app to view the menu or order?</span>
                    <svg class="w-4 h-4 text-gray-400 transform transition-transform" :class="{ 'rotate-180': activeFaq === 1 }" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="activeFaq === 1" x-cloak class="p-4 bg-white dark:bg-gray-800 text-xs text-gray-600 dark:text-gray-400 border-t border-gray-100 dark:border-gray-700/60 leading-relaxed">
                    No. SABAYRA uses progressive web technology. When customers point their phone's camera at the table QR code, your shop's menu opens instantly in mobile Safari, Chrome, or any built-in camera browser. No download, registration, or app store login is ever required.
                </div>
            </div>

            <!-- FAQ 2 -->
            <div class="border border-gray-100 dark:border-gray-700/60 rounded-2xl overflow-hidden">
                <button
                    @click="activeFaq = activeFaq === 2 ? null : 2"
                    class="w-full flex items-center justify-between p-4 bg-gray-50/50 dark:bg-gray-750/50 text-left font-bold text-sm text-gray-900 dark:text-white hover:bg-gray-100/50 dark:hover:bg-gray-700 transition"
                >
                    <span>2. Can we use SABAYRA without a physical kitchen printer?</span>
                    <svg class="w-4 h-4 text-gray-400 transform transition-transform" :class="{ 'rotate-180': activeFaq === 2 }" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="activeFaq === 2" x-cloak class="p-4 bg-white dark:bg-gray-800 text-xs text-gray-600 dark:text-gray-400 border-t border-gray-100 dark:border-gray-700/60 leading-relaxed">
                    Yes! You can operate 100% paperless by opening the Kitchen Display System (KDS) on any Android tablet, iPad, or spare laptop in the kitchen. Order cards appear in real time with audio chimes, eliminating paper tickets, lost dockets, and expensive printer paper waste.
                </div>
            </div>

            <!-- FAQ 3 -->
            <div class="border border-gray-100 dark:border-gray-700/60 rounded-2xl overflow-hidden">
                <button
                    @click="activeFaq = activeFaq === 3 ? null : 3"
                    class="w-full flex items-center justify-between p-4 bg-gray-50/50 dark:bg-gray-750/50 text-left font-bold text-sm text-gray-900 dark:text-white hover:bg-gray-100/50 dark:hover:bg-gray-700 transition"
                >
                    <span>3. Does the system support Bakong KHQR payments in USD and KHR?</span>
                    <svg class="w-4 h-4 text-gray-400 transform transition-transform" :class="{ 'rotate-180': activeFaq === 3 }" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="activeFaq === 3" x-cloak class="p-4 bg-white dark:bg-gray-800 text-xs text-gray-600 dark:text-gray-400 border-t border-gray-100 dark:border-gray-700/60 leading-relaxed">
                    Yes! SABAYRA is engineered specifically for Cambodia. The POS and checkout calculate the exact amount in both USD ($) and Khmer Riel (៛) using your store's live exchange rate (e.g. 4,100 KHR = $1 USD). Customers can scan dynamic KHQR via ABA, Wing, Acleda, Prince, or any Bakong-enabled banking app.
                </div>
            </div>

            <!-- FAQ 4 -->
            <div class="border border-gray-100 dark:border-gray-700/60 rounded-2xl overflow-hidden">
                <button
                    @click="activeFaq = activeFaq === 4 ? null : 4"
                    class="w-full flex items-center justify-between p-4 bg-gray-50/50 dark:bg-gray-750/50 text-left font-bold text-sm text-gray-900 dark:text-white hover:bg-gray-100/50 dark:hover:bg-gray-700 transition"
                >
                    <span>4. What happens when an ingredient or item runs out of stock?</span>
                    <svg class="w-4 h-4 text-gray-400 transform transition-transform" :class="{ 'rotate-180': activeFaq === 4 }" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="activeFaq === 4" x-cloak class="p-4 bg-white dark:bg-gray-800 text-xs text-gray-600 dark:text-gray-400 border-t border-gray-100 dark:border-gray-700/60 leading-relaxed">
                    When you link products to recipes in the Inventory section, raw ingredients automatically deplete upon order completion. You can also toggle any item "Unavailable" with one click in the Products catalog or Touch POS. Once marked unavailable, the item instantly disappears or shows "Sold Out" on the live customer menu.
                </div>
            </div>

            <!-- FAQ 5 -->
            <div class="border border-gray-100 dark:border-gray-700/60 rounded-2xl overflow-hidden">
                <button
                    @click="activeFaq = activeFaq === 5 ? null : 5"
                    class="w-full flex items-center justify-between p-4 bg-gray-50/50 dark:bg-gray-750/50 text-left font-bold text-sm text-gray-900 dark:text-white hover:bg-gray-100/50 dark:hover:bg-gray-700 transition"
                >
                    <span>5. Can multiple staff members use the system at the same time?</span>
                    <svg class="w-4 h-4 text-gray-400 transform transition-transform" :class="{ 'rotate-180': activeFaq === 5 }" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="activeFaq === 5" x-cloak class="p-4 bg-white dark:bg-gray-800 text-xs text-gray-600 dark:text-gray-400 border-t border-gray-100 dark:border-gray-700/60 leading-relaxed">
                    Yes. Under Staff & Permissions, you can create accounts for Cashiers, Kitchen Chefs, Waiters, and Managers. Each staff role is strictly scoped: kitchen staff only see the KDS, cashiers can only ring up sales and print receipts, while only Owners and Managers can access financial reports and system settings.
                </div>
            </div>

            <!-- FAQ 6 -->
            <div class="border border-gray-100 dark:border-gray-700/60 rounded-2xl overflow-hidden">
                <button
                    @click="activeFaq = activeFaq === 6 ? null : 6"
                    class="w-full flex items-center justify-between p-4 bg-gray-50/50 dark:bg-gray-750/50 text-left font-bold text-sm text-gray-900 dark:text-white hover:bg-gray-100/50 dark:hover:bg-gray-700 transition"
                >
                    <span>6. Does SABAYRA work offline or during internet interruptions?</span>
                    <svg class="w-4 h-4 text-gray-400 transform transition-transform" :class="{ 'rotate-180': activeFaq === 6 }" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <div x-show="activeFaq === 6" x-cloak class="p-4 bg-white dark:bg-gray-800 text-xs text-gray-600 dark:text-gray-400 border-t border-gray-100 dark:border-gray-700/60 leading-relaxed">
                    SABAYRA is a cloud-first multi-tenant platform. For multi-device coordination (e.g. customer phone &rarr; cashier POS &rarr; kitchen KDS), an internet or local network connection is required. The Touch POS is built with local session recovery, preserving active cart items if the browser page is refreshed or if Wi-Fi momentarily drops.
                </div>
            </div>

        </div>
    </div>

</div>
</x-business-layout>