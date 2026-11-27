<x-business-layout title="Business AI Settings" heading="AI Center — Business Settings">
    <div class="max-w-5xl mx-auto space-y-6">
        @include('business.ai._nav')

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

        <form method="POST" action="{{ route('business.ai.settings.update') }}"
              class="rounded-2xl bg-white border border-slate-200 p-6 shadow-xs dark:bg-gray-800 dark:border-gray-700/60 space-y-6">
            @csrf
            @method('PUT')

            <div class="pb-4 border-b border-slate-100 dark:border-gray-700/60">
                <h3 class="text-sm font-bold uppercase tracking-wider text-slate-900 dark:text-white">Language, Brand Voice & AI Controls</h3>
                <p class="text-xs text-slate-500 dark:text-gray-400 mt-1">
                    Customize how SABAYRA AI speaks for <strong>{{ $business->name }}</strong> across Khmer & English workflows.
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-gray-300 mb-1.5">Preferred Language</label>
                    <select name="preferred_language"
                            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-amber-500 focus:ring-amber-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                        <option value="auto" @selected(old('preferred_language', $featureSettings->preferred_language) === 'auto')>Auto-Detect (Khmer / English / Mixed)</option>
                        <option value="km" @selected(old('preferred_language', $featureSettings->preferred_language) === 'km')>ភាសាខ្មែរ (Khmer First)</option>
                        <option value="en" @selected(old('preferred_language', $featureSettings->preferred_language) === 'en')>English</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-gray-300 mb-1.5">Brand Copy Tone</label>
                    <select name="preferred_tone"
                            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-amber-500 focus:ring-amber-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                        @foreach(['friendly' => 'Friendly & Warm', 'professional' => 'Professional & Clear', 'energetic' => 'Energetic & Promo-Ready', 'premium' => 'Premium Hospitality', 'concise' => 'Short & Direct'] as $val => $label)
                            <option value="{{ $val }}" @selected(old('preferred_tone', $featureSettings->preferred_tone) === $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-gray-300 mb-1.5">Creativity (Temperature 0.0–1.5)</label>
                    <input type="number" step="0.05" min="0" max="1.5" name="temperature"
                           value="{{ old('temperature', $featureSettings->temperature) }}"
                           class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-amber-500 focus:ring-amber-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                </div>
            </div>

            <div class="pt-4 border-t border-slate-100 dark:border-gray-700/60">
                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-gray-300 mb-3">Enable Modules for Staff</h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    @foreach([
                        ['name' => 'assistant_enabled', 'label' => 'AI Business Assistant', 'desc' => 'Conversational analytics helper'],
                        ['name' => 'menu_creator_enabled', 'label' => 'AI Menu Creator', 'desc' => 'Bilingual menu copy & pricing'],
                        ['name' => 'marketing_studio_enabled', 'label' => 'AI Marketing Studio', 'desc' => 'Facebook, TikTok & Telegram posts'],
                        ['name' => 'forecasting_enabled', 'label' => 'AI Inventory & Sales Prediction', 'desc' => 'Stock runway & demand forecasts'],
                        ['name' => 'insights_enabled', 'label' => 'AI Business Insights', 'desc' => 'Actionable anomaly & margin alerts'],
                    ] as $toggle)
                        <label class="flex items-start gap-3 p-3.5 rounded-xl border border-slate-200 bg-slate-50/60 dark:border-gray-700 dark:bg-gray-900/50 cursor-pointer">
                            <input type="checkbox" name="{{ $toggle['name'] }}" value="1"
                                   @checked(old($toggle['name'], $featureSettings->{$toggle['name']}))
                                   class="mt-0.5 h-4 w-4 rounded border-slate-300 text-orange-600 focus:ring-orange-500">
                            <div>
                                <span class="block text-xs font-bold text-slate-900 dark:text-white">{{ $toggle['label'] }}</span>
                                <span class="block text-[11px] text-slate-500 dark:text-gray-400 mt-0.5">{{ $toggle['desc'] }}</span>
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>

            @if($platformConfig['allow_business_override'] ?? false)
                @php
                    $overrideProviders = config('ai.providers', []);
                    $currentOverrideProvider = old('provider_override', $featureSettings->provider_override ?? '');
                    $currentOverrideModel = old('model_override', $featureSettings->model_override ?? '');
                @endphp
                <div class="pt-4 border-t border-slate-100 dark:border-gray-700/60 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-gray-300 mb-1.5">Provider Override (Optional)</label>
                        <select name="provider_override"
                                class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                            <option value="">Use Platform Default ({{ ucfirst($platformConfig['default_provider']) }})</option>
                            <option value="gemini" @selected($currentOverrideProvider === 'gemini')>Google Gemini</option>
                            <option value="openai" @selected($currentOverrideProvider === 'openai')>OpenAI</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-gray-300 mb-1.5">Model Override (Optional)</label>
                        <select name="model_override"
                                class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                            <option value="">Use Platform Default ({{ $platformConfig['default_model'] }})</option>
                            @foreach($overrideProviders as $provKey => $provMeta)
                                @foreach(($provMeta['models'] ?? []) as $modKey => $modMeta)
                                    <option value="{{ $modKey }}" @selected($currentOverrideModel === $modKey)>
                                        {{ ucfirst($provKey) }} · {{ $modMeta['label'] ?? $modKey }}
                                    </option>
                                @endforeach
                            @endforeach
                        </select>
                    </div>
                </div>
            @else
                <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-xs text-slate-600 dark:border-gray-700 dark:bg-gray-900/50 dark:text-gray-300">
                    AI provider routing ({{ ucfirst($platformConfig['default_provider'] ?? 'gemini') }} · {{ $platformConfig['default_model'] ?? 'gemini-2.5-flash' }}) and API credentials are securely managed by SABAYRA Platform Administration.
                </div>
            @endif

            <div class="flex items-center justify-end pt-3 border-t border-slate-100 dark:border-gray-700/60">
                <button type="submit"
                        class="rounded-xl bg-[#0F1E3A] px-5 py-2.5 text-sm font-bold text-white hover:bg-[#192F59] transition cursor-pointer">
                    Save Business AI Settings
                </button>
            </div>
        </form>
    </div>
</x-business-layout>
