<x-admin-layout title="Platform Settings - Super Admin" heading="Platform System Settings">

<div class="max-w-3xl space-y-6">
    <div class="p-6 bg-white border border-gray-100 rounded-2xl shadow-xs dark:bg-gray-800 dark:border-gray-700/60">
        <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider mb-4">Core Platform Configuration</h3>

        <dl class="divide-y divide-gray-100 dark:divide-gray-700/60 text-xs">
            <div class="py-3 flex justify-between">
                <dt class="text-gray-500 font-medium">Platform Name</dt>
                <dd class="font-bold text-gray-900 dark:text-white">{{ $platform['name'] }}</dd>
            </div>
            <div class="py-3 flex justify-between">
                <dt class="text-gray-500 font-medium">Tagline</dt>
                <dd class="text-gray-700 dark:text-gray-300">{{ $platform['tagline'] }}</dd>
            </div>
            <div class="py-3 flex justify-between">
                <dt class="text-gray-500 font-medium">Default Currency</dt>
                <dd class="font-bold text-gray-900 dark:text-white">{{ $platform['default_currency'] }}</dd>
            </div>
            <div class="py-3 flex justify-between">
                <dt class="text-gray-500 font-medium">Default Exchange Rate</dt>
                <dd class="font-bold text-emerald-600 dark:text-emerald-400">1 USD = {{ number_format($platform['exchange_rate_khr'], 0) }} KHR</dd>
            </div>
            <div class="py-3 flex justify-between">
                <dt class="text-gray-500 font-medium">Database Host</dt>
                <dd class="font-mono text-gray-700 dark:text-gray-300">{{ $platform['database_host'] ?? 'Local / Pooler' }}</dd>
            </div>
            <div class="py-3 flex justify-between">
                <dt class="text-gray-500 font-medium">PHP Version</dt>
                <dd class="font-mono text-gray-700 dark:text-gray-300">{{ $platform['php_version'] }}</dd>
            </div>
            <div class="py-3 flex justify-between">
                <dt class="text-gray-500 font-medium">Laravel Framework</dt>
                <dd class="font-mono text-gray-700 dark:text-gray-300">{{ $platform['laravel_version'] }}</dd>
            </div>
        </dl>
    </div>

    <!-- Cambodian Payment Integrations Notice -->
    <div class="p-6 bg-white border border-gray-100 rounded-2xl shadow-xs dark:bg-gray-800 dark:border-gray-700/60">
        <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider mb-2">Payment Gateways Status</h3>
        <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">Official integrations supported for Cambodian merchants</p>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
            <div class="p-3 border border-emerald-100 bg-emerald-50/50 rounded-xl dark:bg-emerald-950/20 dark:border-emerald-800/40">
                <div class="flex items-center justify-between">
                    <span class="font-bold text-emerald-900 dark:text-emerald-300">Bakong KHQR</span>
                    <x-badge color="green">Ready</x-badge>
                </div>
                <p class="text-[11px] text-emerald-700 dark:text-emerald-400 mt-1">Direct scan-to-pay generation with USD and KHR dual QR payloads.</p>
            </div>

            <div class="p-3 border border-blue-100 bg-blue-50/50 rounded-xl dark:bg-blue-950/20 dark:border-blue-800/40">
                <div class="flex items-center justify-between">
                    <span class="font-bold text-blue-900 dark:text-blue-300">ABA PayWay</span>
                    <x-badge color="blue">Configurable</x-badge>
                </div>
                <p class="text-[11px] text-blue-700 dark:text-blue-400 mt-1">Merchant API credentials can be plugged in per business or platform-wide.</p>
            </div>
        </div>
    </div>
</div>
</x-admin-layout>
