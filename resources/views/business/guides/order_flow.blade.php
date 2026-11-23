<x-business-layout title="Order Lifecycle Flow Guide" heading="Order Flow & Fulfillment Guide">
<div class="space-y-6">

    <!-- Header Description Card -->
    <div class="p-6 bg-gradient-to-r from-orange-500/10 via-amber-500/5 to-transparent border border-orange-500/20 rounded-2xl">
        <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
            <svg class="w-5 h-5 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
            <span>Interactive Operational Flow Architecture</span>
        </h3>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-2xl">
            Step-by-step workflow guide tailored specifically for {{ ucfirst(str_replace('_', ' ', $business->category ?? 'retail')) }} operations in Cambodia, covering table QR, Bakong KHQR checkout, kitchen display, and inventory depletion.
        </p>
    </div>

    <!-- 4-Stage Workflow Diagram -->
    <div class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-2xl p-6 shadow-xs">
        <h4 class="text-xs font-black uppercase text-slate-400 tracking-wider mb-4">Complete 4-Stage Lifecycle</h4>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 relative">
            <!-- Step 1 -->
            <div class="p-5 rounded-2xl bg-amber-50/70 dark:bg-amber-950/20 border border-amber-200/60 dark:border-amber-800/40 relative">
                <div class="w-8 h-8 rounded-full bg-amber-500 text-white font-bold flex items-center justify-center text-xs mb-3 shadow">
                    1
                </div>
                <h5 class="font-bold text-gray-900 dark:text-white text-sm mb-1">Order Placement</h5>
                <p class="text-xs text-slate-600 dark:text-slate-400">
                    Customer scans Table QR standee, browses storefront, or cashier keys items into Touch POS.
                </p>
                <div class="mt-3 pt-2 border-t border-amber-200/60 dark:border-amber-800/40 text-[11px] font-semibold text-amber-700 dark:text-amber-300">
                    Status: <span class="font-mono uppercase">pending / unpaid</span>
                </div>
            </div>

            <!-- Step 2 -->
            <div class="p-5 rounded-2xl bg-blue-50/70 dark:bg-blue-950/20 border border-blue-200/60 dark:border-blue-800/40 relative">
                <div class="w-8 h-8 rounded-full bg-blue-500 text-white font-bold flex items-center justify-center text-xs mb-3 shadow">
                    2
                </div>
                <h5 class="font-bold text-gray-900 dark:text-white text-sm mb-1">Payment & Verification</h5>
                <p class="text-xs text-slate-600 dark:text-slate-400">
                    Cashier accepts Cash (USD/KHR) or customer scans Bakong KHQR dynamic QR. Instant payment logging.
                </p>
                <div class="mt-3 pt-2 border-t border-blue-200/60 dark:border-blue-800/40 text-[11px] font-semibold text-blue-700 dark:text-blue-300">
                    Status: <span class="font-mono uppercase">confirmed / paid</span>
                </div>
            </div>

            <!-- Step 3 -->
            <div class="p-5 rounded-2xl bg-purple-50/70 dark:bg-purple-950/20 border border-purple-200/60 dark:border-purple-800/40 relative">
                <div class="w-8 h-8 rounded-full bg-purple-500 text-white font-bold flex items-center justify-center text-xs mb-3 shadow">
                    3
                </div>
                <h5 class="font-bold text-gray-900 dark:text-white text-sm mb-1">Kitchen / Assembly</h5>
                <p class="text-xs text-slate-600 dark:text-slate-400">
                    Order docket displays on Kitchen Display System (KDS) or prints to ESC/POS thermal printer.
                </p>
                <div class="mt-3 pt-2 border-t border-purple-200/60 dark:border-purple-800/40 text-[11px] font-semibold text-purple-700 dark:text-purple-300">
                    Status: <span class="font-mono uppercase">preparing &rarr; ready</span>
                </div>
            </div>

            <!-- Step 4 -->
            <div class="p-5 rounded-2xl bg-emerald-50/70 dark:bg-emerald-950/20 border border-emerald-200/60 dark:border-emerald-800/40 relative">
                <div class="w-8 h-8 rounded-full bg-emerald-500 text-white font-bold flex items-center justify-center text-xs mb-3 shadow">
                    4
                </div>
                <h5 class="font-bold text-gray-900 dark:text-white text-sm mb-1">Fulfillment & Stock</h5>
                <p class="text-xs text-slate-600 dark:text-slate-400">
                    Served to table or handed to customer. Recipe ingredients auto-deduct from stock upon completion.
                </p>
                <div class="mt-3 pt-2 border-t border-emerald-200/60 dark:border-emerald-800/40 text-[11px] font-semibold text-emerald-700 dark:text-emerald-300">
                    Status: <span class="font-mono uppercase">completed</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Role-Based SOP Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-2xl p-6 shadow-xs">
            <div class="flex items-center gap-2 mb-3">
                <div class="w-3 h-3 rounded-full bg-orange-500"></div>
                <h4 class="text-sm font-bold text-gray-900 dark:text-white">Cashier Responsibilities</h4>
            </div>
            <ul class="text-xs text-slate-600 dark:text-slate-400 space-y-2 list-disc list-inside">
                <li>Open and verify daily cash drawer session in POS.</li>
                <li>Accept KHQR and Cash payments in USD and KHR.</li>
                <li>Print 58mm/80mm receipts for customers.</li>
                <li>Close POS session and reconcile cash differences at end of shift.</li>
            </ul>
        </div>

        <div class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-2xl p-6 shadow-xs">
            <div class="flex items-center gap-2 mb-3">
                <div class="w-3 h-3 rounded-full bg-purple-500"></div>
                <h4 class="text-sm font-bold text-gray-900 dark:text-white">Kitchen & Barista Staff</h4>
            </div>
            <ul class="text-xs text-slate-600 dark:text-slate-400 space-y-2 list-disc list-inside">
                <li>Monitor live incoming tickets on KDS screen.</li>
                <li>Bump ticket to "Preparing" when cooking begins to track preparation speed.</li>
                <li>Mark "Ready" to signal floor staff or delivery runner.</li>
            </ul>
        </div>

        <div class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-2xl p-6 shadow-xs">
            <div class="flex items-center gap-2 mb-3">
                <div class="w-3 h-3 rounded-full bg-emerald-500"></div>
                <h4 class="text-sm font-bold text-gray-900 dark:text-white">Store Owner & Manager</h4>
            </div>
            <ul class="text-xs text-slate-600 dark:text-slate-400 space-y-2 list-disc list-inside">
                <li>Review real-time live order stream and queue health.</li>
                <li>Monitor daily revenue metrics and top-selling items.</li>
                <li>Manage recipe ingredients, branches, and staff role permissions.</li>
            </ul>
        </div>
    </div>

</div>
</x-business-layout>
