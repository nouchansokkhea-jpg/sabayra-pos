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

    @php
        $aiProvidersCatalog = [];
        foreach ((array) ($aiConfig['providers'] ?? []) as $providerKey => $providerMeta) {
            $modelsList = [];
            foreach ((array) ($providerMeta['models'] ?? []) as $modelKey => $modelMeta) {
                $modelsList[] = [
                    'id' => $modelKey,
                    'label' => $modelMeta['label'] ?? $modelKey,
                ];
            }
            $aiProvidersCatalog[$providerKey] = [
                'label' => $providerMeta['label'] ?? ucfirst($providerKey),
                'default_model' => $providerMeta['default_model'] ?? '',
                'models' => $modelsList,
            ];
        }
        $selectedAiProvider = old('ai_default_provider', $aiConfig['default_provider'] ?? 'gemini');
        $selectedAiModel = old('ai_default_model', $aiConfig['default_model'] ?? 'gemini-2.5-flash');
    @endphp

    <!-- SABAYRA AI Center Platform Configuration -->
    <form method="POST" action="{{ route('admin.settings.ai.update') }}"
          x-data="sabayraAiSettingsComponent()"
          class="p-6 bg-white border border-slate-200 rounded-2xl shadow-xs dark:bg-gray-800 dark:border-gray-700/60 space-y-5">
        @csrf

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-4 border-b border-slate-100 dark:border-gray-700/60">
            <div>
                <h3 class="text-sm font-bold text-slate-900 dark:text-white uppercase tracking-wider">SABAYRA AI Center Configuration</h3>
                <p class="text-xs text-slate-500 dark:text-gray-400 mt-0.5">
                    Configure Google Gemini / OpenAI providers, server-encrypted API credentials, rate limits, and monthly credit defaults.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <x-badge :color="$aiConfig['enabled'] ? 'green' : 'gray'">
                    AI Center: {{ $aiConfig['enabled'] ? 'Enabled' : 'Disabled' }}
                </x-badge>
                <x-badge :color="$aiConfig['gemini_key_configured'] ? 'blue' : 'gray'">
                    Gemini Key: {{ $aiConfig['gemini_key_configured'] ? 'Configured' : 'Missing' }}
                </x-badge>
                <x-badge :color="$aiConfig['openai_key_configured'] ? 'blue' : 'gray'">
                    OpenAI Key: {{ $aiConfig['openai_key_configured'] ? 'Configured' : 'Missing' }}
                </x-badge>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <label class="flex items-start gap-3 p-4 rounded-xl border border-slate-200 bg-slate-50/70 dark:border-gray-700 dark:bg-gray-900/50 cursor-pointer">
                <input type="hidden" name="ai_enabled" value="0">
                <input type="checkbox" name="ai_enabled" value="1"
                       @checked(old('ai_enabled', $aiConfig['enabled']))
                       class="mt-0.5 h-4 w-4 rounded border-slate-300 text-amber-500 focus:ring-amber-500">
                <div>
                    <span class="block text-sm font-bold text-slate-900 dark:text-white">Enable SABAYRA AI Center Platform-Wide</span>
                    <span class="block text-xs text-slate-500 dark:text-gray-400 mt-0.5">
                        Master switch for Business Assistant, Menu Creator, Marketing Studio, and Forecasting across all stores.
                    </span>
                </div>
            </label>

            <label class="flex items-start gap-3 p-4 rounded-xl border border-slate-200 bg-slate-50/70 dark:border-gray-700 dark:bg-gray-900/50 cursor-pointer">
                <input type="hidden" name="ai_allow_business_override" value="0">
                <input type="checkbox" name="ai_allow_business_override" value="1"
                       @checked(old('ai_allow_business_override', $aiConfig['allow_business_override']))
                       class="mt-0.5 h-4 w-4 rounded border-slate-300 text-amber-500 focus:ring-amber-500">
                <div>
                    <span class="block text-sm font-bold text-slate-900 dark:text-white">Allow Business Provider Overrides</span>
                    <span class="block text-xs text-slate-500 dark:text-gray-400 mt-0.5">
                        Let business owners choose between configured Gemini/OpenAI models inside their AI Settings.
                    </span>
                </div>
            </label>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-gray-300 mb-1.5">Default AI Provider</label>
                <select name="ai_default_provider" x-model="provider" @change="syncModelForProvider()"
                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-amber-500 focus:ring-amber-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                    <option value="gemini">Google Gemini</option>
                    <option value="openai">OpenAI</option>
                </select>
            </div>

            <div class="sm:col-span-2 lg:col-span-2">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-gray-300 mb-1.5">Default AI Model</label>
                <select name="ai_default_model" x-model="model"
                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-amber-500 focus:ring-amber-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                    <template x-for="item in availableModels()" :key="item.id">
                        <option :value="item.id" x-text="item.label" :selected="item.id === model"></option>
                    </template>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-gray-300 mb-1.5">Temperature (0.0 – 1.5)</label>
                <input type="number" step="0.05" min="0" max="1.5" name="ai_temperature"
                       value="{{ old('ai_temperature', $aiConfig['temperature']) }}"
                       class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-amber-500 focus:ring-amber-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-gray-300 mb-1.5">Max Output Tokens</label>
                <input type="number" min="128" max="8192" name="ai_max_output_tokens"
                       value="{{ old('ai_max_output_tokens', $aiConfig['max_output_tokens']) }}"
                       class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-amber-500 focus:ring-amber-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-gray-300 mb-1.5">Request Timeout (Seconds)</label>
                <input type="number" min="5" max="120" name="ai_timeout_seconds"
                       value="{{ old('ai_timeout_seconds', $aiConfig['timeout_seconds']) }}"
                       class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-amber-500 focus:ring-amber-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-gray-300 mb-1.5">Transient Retries (0 – 3)</label>
                <input type="number" min="0" max="3" name="ai_retry_attempts"
                       value="{{ old('ai_retry_attempts', $aiConfig['retry_attempts']) }}"
                       class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-amber-500 focus:ring-amber-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-gray-300 mb-1.5">Default Monthly Credits (Launch Mode)</label>
                <input type="number" min="0" max="100000" name="ai_default_monthly_credits"
                       value="{{ old('ai_default_monthly_credits', $aiConfig['default_monthly_credits']) }}"
                       class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-amber-500 focus:ring-amber-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-gray-300 mb-1.5">
                    Google Gemini API Key
                    <span class="ml-1 font-normal normal-case text-slate-500 dark:text-gray-400">
                        ({{ $aiConfig['gemini_key_configured'] ? 'Encrypted key stored' : 'Not configured' }})
                    </span>
                </label>
                <input type="password" name="gemini_api_key" x-model="geminiKeyDraft" autocomplete="new-password"
                       placeholder="{{ $aiConfig['gemini_key_configured'] ? 'Leave blank to keep existing encrypted key' : 'Paste AIza... Google Gemini API key' }}"
                       class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-amber-500 focus:ring-amber-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                @if($aiConfig['gemini_key_configured'])
                    <label class="inline-flex items-center gap-1.5 mt-1.5 text-[11px] text-rose-600 dark:text-rose-400 cursor-pointer">
                        <input type="checkbox" name="clear_gemini_api_key" value="1" class="rounded border-slate-300 text-rose-600">
                        Remove stored Gemini API key
                    </label>
                @endif
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-gray-300 mb-1.5">
                    OpenAI API Key
                    <span class="ml-1 font-normal normal-case text-slate-500 dark:text-gray-400">
                        ({{ $aiConfig['openai_key_configured'] ? 'Encrypted key stored' : 'Not configured' }})
                    </span>
                </label>
                <input type="password" name="openai_api_key" x-model="openaiKeyDraft" autocomplete="new-password"
                       placeholder="{{ $aiConfig['openai_key_configured'] ? 'Leave blank to keep existing encrypted key' : 'Paste sk-... OpenAI API key' }}"
                       class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-amber-500 focus:ring-amber-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                @if($aiConfig['openai_key_configured'])
                    <label class="inline-flex items-center gap-1.5 mt-1.5 text-[11px] text-rose-600 dark:text-rose-400 cursor-pointer">
                        <input type="checkbox" name="clear_openai_api_key" value="1" class="rounded border-slate-300 text-rose-600">
                        Remove stored OpenAI API key
                    </label>
                @endif
            </div>
        </div>

        <div x-show="testFeedback" x-cloak
             :class="testOk ? 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-800/60 dark:bg-emerald-950/30 dark:text-emerald-300' : 'border-rose-200 bg-rose-50 text-rose-800 dark:border-rose-800/60 dark:bg-rose-950/30 dark:text-rose-300'"
             class="rounded-xl border px-4 py-3 text-xs font-medium" x-text="testFeedback">
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 pt-3 border-t border-slate-100 dark:border-gray-700/60">
            <button type="button" @click="runConnectionTest()" :disabled="testing"
                    class="rounded-xl border border-amber-500 bg-amber-50 px-4 py-2.5 text-xs font-bold text-amber-800 hover:bg-amber-100 dark:border-amber-500/60 dark:bg-amber-950/30 dark:text-amber-300 transition cursor-pointer disabled:opacity-60">
                <span x-text="testing ? 'Testing AI Provider...' : 'Test AI Connection'"></span>
            </button>

            <button type="submit"
                    class="rounded-xl bg-[#0F1E3A] px-5 py-2.5 text-sm font-bold text-white hover:bg-[#192F59] transition cursor-pointer">
                Save AI Configuration
            </button>
        </div>
    </form>
</div>

<script>
    window.sabayraAiCatalog = {{ \Illuminate\Support\Js::from($aiProvidersCatalog) }};
    function sabayraAiSettingsComponent() {
        return {
            provider: {{ \Illuminate\Support\Js::from($selectedAiProvider) }},
            model: {{ \Illuminate\Support\Js::from($selectedAiModel) }},
            geminiKeyDraft: '',
            openaiKeyDraft: '',
            testing: false,
            testOk: false,
            testFeedback: '',
            availableModels() {
                const entry = window.sabayraAiCatalog[this.provider];
                return entry && Array.isArray(entry.models) ? entry.models : [];
            },
            syncModelForProvider() {
                const models = this.availableModels();
                if (!models.some((m) => m.id === this.model)) {
                    const entry = window.sabayraAiCatalog[this.provider];
                    this.model = (entry && entry.default_model) || (models[0] ? models[0].id : '');
                }
            },
            async runConnectionTest() {
                this.testing = true;
                this.testFeedback = '';
                const draftKey = this.provider === 'openai' ? this.openaiKeyDraft : this.geminiKeyDraft;
                try {
                    const response = await fetch({{ \Illuminate\Support\Js::from(route('admin.settings.ai.test')) }}, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                        },
                        body: JSON.stringify({
                            provider: this.provider,
                            model: this.model,
                            api_key: draftKey || null,
                        }),
                    });
                    const data = await response.json();
                    this.testOk = Boolean(data.ok);
                    this.testFeedback = data.message || (response.ok ? 'Connection verified.' : 'Connection test failed.');
                } catch (error) {
                    this.testOk = false;
                    this.testFeedback = 'Network error while testing AI connection.';
                } finally {
                    this.testing = false;
                }
            },
        };
    }
</script>
</x-admin-layout>