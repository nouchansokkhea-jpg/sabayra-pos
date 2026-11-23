<x-business-layout title="Video Tutorials & Walkthroughs" heading="Tutorials & Knowledge Center">
<div class="space-y-6">

    <div class="p-6 bg-gradient-to-r from-orange-500/10 via-amber-500/5 to-transparent border border-orange-500/20 rounded-2xl">
        <h3 class="text-base font-bold text-gray-900 dark:text-white">
            Masterclasses & Setup Walkthroughs
        </h3>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-2xl">
            Learn step-by-step how to configure your SABAYRA merchant portal, hardware printers, Bakong KHQR checkout, and multi-branch inventory.
        </p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Card 1: Menu & Catalog -->
        <div class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-2xl p-6 shadow-xs flex flex-col justify-between">
            <div>
                <div class="w-10 h-10 rounded-xl bg-orange-100 dark:bg-orange-950/40 text-orange-600 flex items-center justify-center mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                </div>
                <span class="text-[11px] font-bold text-orange-600 uppercase tracking-wider">Tutorial 1 • 4 min</span>
                <h4 class="text-base font-bold text-gray-900 dark:text-white mt-1 mb-2">Setting Up Products & Categories</h4>
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    Learn how to add products with high-resolution photos, variant pricing, add-ons, and dual USD/KHR display.
                </p>
            </div>
            <div class="mt-6 pt-4 border-t border-gray-100 dark:border-gray-700">
                <a href="{{ route('business.products.index') }}" class="text-xs font-semibold text-orange-600 hover:text-orange-500 flex items-center gap-1">
                    Open Catalog &rarr;
                </a>
            </div>
        </div>

        <!-- Card 2: Touch POS & KHQR -->
        <div class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-2xl p-6 shadow-xs flex flex-col justify-between">
            <div>
                <div class="w-10 h-10 rounded-xl bg-blue-100 dark:bg-blue-950/40 text-blue-600 flex items-center justify-center mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                </div>
                <span class="text-[11px] font-bold text-blue-600 uppercase tracking-wider">Tutorial 2 • 6 min</span>
                <h4 class="text-base font-bold text-gray-900 dark:text-white mt-1 mb-2">Touch POS & Bakong KHQR</h4>
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    Master fast checkout with barcode scanning, cashier shifts, split bills, and instant KHQR payment codes.
                </p>
            </div>
            <div class="mt-6 pt-4 border-t border-gray-100 dark:border-gray-700">
                <a href="{{ route('business.pos.index') }}" class="text-xs font-semibold text-blue-600 hover:text-blue-500 flex items-center gap-1">
                    Launch Touch POS &rarr;
                </a>
            </div>
        </div>

        <!-- Card 3: Kitchen Display System -->
        <div class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-2xl p-6 shadow-xs flex flex-col justify-between">
            <div>
                <div class="w-10 h-10 rounded-xl bg-purple-100 dark:bg-purple-950/40 text-purple-600 flex items-center justify-center mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                </div>
                <span class="text-[11px] font-bold text-purple-600 uppercase tracking-wider">Tutorial 3 • 5 min</span>
                <h4 class="text-base font-bold text-gray-900 dark:text-white mt-1 mb-2">Kitchen Display System (KDS)</h4>
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    Setup a tablet or monitor in the kitchen with real-time sound chimes, timers, and bump buttons.
                </p>
            </div>
            <div class="mt-6 pt-4 border-t border-gray-100 dark:border-gray-700">
                <a href="{{ route('business.kitchen.index') }}" target="_blank" class="text-xs font-semibold text-purple-600 hover:text-purple-500 flex items-center gap-1">
                    Open KDS Terminal &rarr;
                </a>
            </div>
        </div>

        <!-- Card 4: Inventory & Stock Control -->
        <div class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-2xl p-6 shadow-xs flex flex-col justify-between">
            <div>
                <div class="w-10 h-10 rounded-xl bg-emerald-100 dark:bg-emerald-950/40 text-emerald-600 flex items-center justify-center mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </div>
                <span class="text-[11px] font-bold text-emerald-600 uppercase tracking-wider">Tutorial 4 • 7 min</span>
                <h4 class="text-base font-bold text-gray-900 dark:text-white mt-1 mb-2">Stock Control & Reordering</h4>
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    Track stock levels, minimum reorder thresholds, low stock alerts, and automated stock deductions.
                </p>
            </div>
            <div class="mt-6 pt-4 border-t border-gray-100 dark:border-gray-700">
                <a href="{{ route('business.inventory.index') }}" class="text-xs font-semibold text-emerald-600 hover:text-emerald-500 flex items-center gap-1">
                    Manage Stock &rarr;
                </a>
            </div>
        </div>

        <!-- Card 5: Table QR & Standees -->
        <div class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-2xl p-6 shadow-xs flex flex-col justify-between">
            <div>
                <div class="w-10 h-10 rounded-xl bg-amber-100 dark:bg-amber-950/40 text-amber-600 flex items-center justify-center mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                </div>
                <span class="text-[11px] font-bold text-amber-600 uppercase tracking-wider">Tutorial 5 • 4 min</span>
                <h4 class="text-base font-bold text-gray-900 dark:text-white mt-1 mb-2">Printing Table QR Standees</h4>
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    Generate high-res PNG and PDF standees branded with your logo for dine-in tables and counter ordering.
                </p>
            </div>
            <div class="mt-6 pt-4 border-t border-gray-100 dark:border-gray-700">
                <a href="{{ route('business.tables.index') }}" class="text-xs font-semibold text-amber-600 hover:text-amber-500 flex items-center gap-1">
                    Download Standees &rarr;
                </a>
            </div>
        </div>

        <!-- Card 6: Multi-Branch & Staff Roles -->
        <div class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-2xl p-6 shadow-xs flex flex-col justify-between">
            <div>
                <div class="w-10 h-10 rounded-xl bg-indigo-100 dark:bg-indigo-950/40 text-indigo-600 flex items-center justify-center mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                </div>
                <span class="text-[11px] font-bold text-indigo-600 uppercase tracking-wider">Tutorial 6 • 8 min</span>
                <h4 class="text-base font-bold text-gray-900 dark:text-white mt-1 mb-2">Multi-Branch Outlets & Staff</h4>
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    Assign cashiers, waiters, and kitchen staff to specific locations with isolated inventory and revenue tracking.
                </p>
            </div>
            <div class="mt-6 pt-4 border-t border-gray-100 dark:border-gray-700">
                <a href="{{ route('business.branches.index') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-500 flex items-center gap-1">
                    Manage Branches &rarr;
                </a>
            </div>
        </div>
    </div>

</div>
</x-business-layout>
