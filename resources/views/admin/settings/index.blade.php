<x-admin-layout title="Platform Settings - Super Admin" heading="Platform System Settings">

<div class="max-w-4xl space-y-6">
    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 dark:border-emerald-800/60 dark:bg-emerald-950/30 dark:text-emerald-300">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 dark:border-rose-800/60 dark:bg-rose-950/30 dark:text-rose-300">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="p-6 bg-white border border-slate-200 rounded-2xl shadow-xs dark:bg-gray-800 dark:border-gray-700/60">
        <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider mb-4">Core Platform Configuration</h3>

        <dl class="divide-y divide-slate-100 dark:divide-gray-700/60 text-xs">
            <div class="py-3 flex justify-between">
                <dt class="text-slate-500 dark:text-gray-400 font-medium">Platform Name</dt>
                <dd class="font-bold text-slate-900 dark:text-white">{{ $platform['name'] }}</dd>
            </div>
            <div class="py-3 flex justify-between">
                <dt class="text-slate-500 dark:text-gray-400 font-medium">Tagline</dt>
                <dd class="text-slate-700 dark:text-gray-300">{{ $platform['tagline'] }}</dd>
            </div>
            <div class="py-3 flex justify-between">
                <dt class="text-slate-500 dark:text-gray-400 font-medium">Default Currency</dt>
                <dd class="font-bold text-slate-900 dark:text-white">{{ $platform['default_currency'] }}</dd>
            </div>
            <div class="py-3 flex justify-between">
                <dt class="text-slate-500 dark:text-gray-400 font-medium">Default Exchange Rate</dt>
                <dd class="font-bold text-emerald-600 dark:text-emerald-400">1 USD = {{ number_format($platform['exchange_rate_khr'], 0) }} KHR</dd>
            </div>
            <div class="py-3 flex justify-between">
                <dt class="text-slate-500 dark:text-gray-400 font-medium">Database Host</dt>
                <dd class="font-mono text-slate-700 dark:text-gray-300">{{ $platform['database_host'] ?? 'Local / Pooler' }}</dd>
            </div>
            <div class="py-3 flex justify-between">
                <dt class="text-slate-500 dark:text-gray-400 font-medium">PHP Version</dt>
                <dd class="font-mono text-slate-700 dark:text-gray-300">{{ $platform['php_version'] }}</dd>
            </div>
            <div class="py-3 flex justify-between">
                <dt class="text-slate-500 dark:text-gray-400 font-medium">Laravel Framework</dt>
                <dd class="font-mono text-slate-700 dark:text-gray-300">{{ $platform['laravel_version'] }}</dd>
            </div>
        </dl>
    </div>

    <!-- Platform-Wide Payment Gateway Availability & NBC Bakong Open API -->
    <form method="POST" action="{{ route('admin.settings.update') }}" class="p-6 bg-white border border-slate-200 rounded-2xl shadow-xs dark:bg-gray-800 dark:border-gray-700/60 space-y-5">
        @csrf

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 pb-4 border-b border-slate-100 dark:border-gray-700/60">
            <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">Platform Payment Gateway Controls</h3>
                <p class="text-xs text-slate-500 dark:text-gray-400 mt-0.5">Enable or pause Bakong KHQR and ABA PayWay across all businesses without deleting business preferences.</p>
            </div>
            <div class="flex items-center gap-2">
                <x-badge :color="$gatewaySettings['bakong_enabled'] ? 'green' : 'gray'">
                    Bakong: {{ $gatewaySettings['bakong_enabled'] ? 'Enabled' : 'Disabled' }}
                </x-badge>
                <x-badge :color="$gatewaySettings['aba_payway_enabled'] ? 'blue' : 'gray'">
                    ABA PayWay: {{ $gatewaySettings['aba_payway_enabled'] ? 'Enabled' : 'Disabled' }}
                </x-badge>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <label class="flex items-start gap-3 p-4 rounded-xl border border-slate-200 bg-slate-50/70 dark:border-gray-700 dark:bg-gray-900/50 cursor-pointer">
                <input type="hidden" name="bakong_enabled" value="0">
                <input type="checkbox" name="bakong_enabled" value="1"
                       @checked(old('bakong_enabled', $gatewaySettings['bakong_enabled']))
                       class="mt-0.5 h-4 w-4 rounded border-slate-300 text-amber-500 focus:ring-amber-500">
                <div>
                    <span class="block text-sm font-bold text-slate-900 dark:text-white">Enable Bakong KHQR Platform-Wide</span>
                    <span class="block text-xs text-slate-500 dark:text-gray-400 mt-0.5">
                        When turned off, Bakong checkout is paused for all stores while preserving each store's saved setting.
                    </span>
                </div>
            </label>

            <label class="flex items-start gap-3 p-4 rounded-xl border border-slate-200 bg-slate-50/70 dark:border-gray-700 dark:bg-gray-900/50 cursor-pointer">
                <input type="hidden" name="aba_payway_enabled" value="0">
                <input type="checkbox" name="aba_payway_enabled" value="1"
                       @checked(old('aba_payway_enabled', $gatewaySettings['aba_payway_enabled']))
                       class="mt-0.5 h-4 w-4 rounded border-slate-300 text-amber-500 focus:ring-amber-500">
                <div>
                    <span class="block text-sm font-bold text-slate-900 dark:text-white">Enable ABA PayWay Platform-Wide</span>
                    <span class="block text-xs text-slate-500 dark:text-gray-400 mt-0.5">
                        Controls whether configured stores can expose Pay with ABA at public checkout.
                    </span>
                </div>
            </label>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-gray-300 mb-1.5">
                    NBC Bakong Open API Base URL
                </label>
                <input type="url" name="bakong_api_base_url"
                       value="{{ old('bakong_api_base_url', $gatewaySettings['bakong_api_base_url']) }}"
                       placeholder="https://api-bakong.nbc.gov.kh"
                       class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-amber-500 focus:ring-amber-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                <p class="text-[11px] text-slate-500 dark:text-gray-400 mt-1">Official National Bank of Cambodia Bakong Open API endpoint.</p>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-gray-300 mb-1.5">
                    NBC Bakong Open API Token
                    <span class="ml-1 font-normal normal-case text-slate-500 dark:text-gray-400">
                        ({{ $gatewaySettings['bakong_token_configured'] ? 'Encrypted token stored' : 'Not configured' }})
                    </span>
                </label>
                <input type="password" name="bakong_api_token" autocomplete="new-password"
                       placeholder="{{ $gatewaySettings['bakong_token_configured'] ? 'Leave blank to keep existing encrypted token' : 'Paste official Bakong JWT token' }}"
                       class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-amber-500 focus:ring-amber-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                @if($gatewaySettings['bakong_token_configured'])
                    <label class="inline-flex items-center gap-1.5 mt-1.5 text-[11px] text-rose-600 dark:text-rose-400 cursor-pointer">
                        <input type="checkbox" name="clear_bakong_api_token" value="1" class="rounded border-slate-300 text-rose-600">
                        Remove stored Bakong API token
                    </label>
                @endif
            </div>
        </div>

        <div class="flex items-center justify-end pt-3 border-t border-slate-100 dark:border-gray-700/60">
            <button type="submit"
                    class="rounded-xl bg-[#0F1E3A] px-5 py-2.5 text-sm font-bold text-white hover:bg-[#192F59] transition cursor-pointer">
                Save Platform Gateway Settings
            </button>
        </div>
    </form>
</div>
</x-admin-layout>