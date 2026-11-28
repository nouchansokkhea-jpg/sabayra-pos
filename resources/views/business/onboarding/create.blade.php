<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="min-h-full bg-gray-50 dark:bg-gray-900">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Setup Your Business - SABAYRA Cambodia</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Kantumruy+Pro:wght@400;500;600;700&family=Battambang:wght@400;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
@php
    $catalogData = $categoryCatalog ?? [];
    $catalogJs = \Illuminate\Support\Js::from($catalogData);
    $initialCategory = old('business_type', array_key_first($businessTypes ?? ['restaurant' => []]) ?: 'restaurant');
@endphp
<body class="min-h-full font-sans antialiased text-gray-800 dark:text-gray-100 py-8 px-4 sm:px-6 lg:px-8">
    <div class="w-full max-w-5xl mx-auto bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-3xl shadow-xl p-6 sm:p-10">
        <div class="text-center mb-8 flex flex-col items-center">
            <img src="{{ asset('images/sabayra-icon.png') }}" alt="SABAYRA" class="w-14 h-14 rounded-2xl object-contain shadow-lg shadow-orange-500/20 mb-3" />
            <span class="text-orange-500 text-3xl font-black tracking-tight">SABAYRA</span>
            <h1 class="text-2xl font-extrabold text-gray-900 dark:text-white mt-1">Setup Your Business Workspace</h1>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                One platform — automatically tailored dashboard, POS, inventory, and storefront for your exact industry.
            </p>
        </div>

        @if ($errors->any())
            <div class="mb-6 p-4 rounded-xl bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800 text-xs text-red-700 dark:text-red-300">
                <ul class="list-disc list-inside space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('business.store') }}" class="space-y-7"
              x-data="{
                name: '{{ old('name', '') }}',
                slug: '{{ old('slug', '') }}',
                manuallyEdited: {{ old('slug') ? 'true' : 'false' }},
                catalog: {{ $catalogJs }},
                selectedCategory: '{{ $initialCategory }}',
                get currentCategory() {
                    return this.catalog[this.selectedCategory] || {};
                },
                slugify(val) {
                    if (!val) return '';
                    return val.toLowerCase()
                              .trim()
                              .replace(/[\s_]+/g, '-')
                              .replace(/[^\p{L}\p{M}\p{N}\-]+/gu, '')
                              .replace(/^-+|-+$/g, '');
                }
              }">
            @csrf

            <!-- Step 1: Visual 12-Category Experience Selector -->
            <div class="space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1">
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                        1. Choose Business Category / ជ្រើសរើសប្រភេទអាជីវកម្ម *
                    </label>
                    <span class="text-[11px] font-semibold text-orange-600 dark:text-orange-400">
                        Can be changed later anytime without losing data
                    </span>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-2.5">
                    @foreach ($businessTypes as $key => $type)
                        @php $catMeta = $catalogData[$key] ?? []; @endphp
                        <button type="button"
                                @click="selectedCategory = '{{ $key }}'"
                                :class="selectedCategory === '{{ $key }}'
                                    ? 'border-orange-500 bg-orange-50/80 dark:bg-orange-950/35 ring-2 ring-orange-500/30 shadow-xs'
                                    : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900/40 hover:border-orange-300'"
                                class="text-left p-3 rounded-xl border transition flex flex-col justify-between gap-1.5">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-xs font-extrabold text-gray-900 dark:text-white leading-tight">{{ $type['name_en'] }}</span>
                                <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background-color: {{ $catMeta['primary_color'] ?? '#f97316' }}"></span>
                            </div>
                            <span class="text-[11px] text-gray-500 dark:text-gray-400 leading-tight">{{ $type['name_km'] }}</span>
                            @if(!empty($catMeta['pos_mode']))
                                <span class="inline-flex w-fit px-1.5 py-0.5 rounded bg-slate-100 dark:bg-gray-800 text-[10px] font-bold text-slate-600 dark:text-slate-300 capitalize">
                                    {{ str_replace('_', ' ', $catMeta['pos_mode']) }}
                                </span>
                            @endif
                        </button>
                    @endforeach
                </div>
                <input type="hidden" name="business_type" :value="selectedCategory">

                <!-- Live Tailored Workspace Preview Panel -->
                <div class="p-4 sm:p-5 rounded-2xl bg-slate-900 text-white border border-slate-800 shadow-md space-y-3">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                        <div>
                            <p class="text-[10px] uppercase tracking-widest font-bold text-orange-400">Tailored SABAYRA Experience Preview</p>
                            <h2 class="text-base font-black text-white mt-0.5">
                                <span x-text="currentCategory.name_en || selectedCategory"></span>
                                <span class="text-slate-400 font-normal mx-1">·</span>
                                <span class="text-orange-300 font-bold" x-text="currentCategory.name_km || ''"></span>
                            </h2>
                            <p class="text-xs text-slate-300 mt-0.5" x-text="currentCategory.description_en || ''"></p>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <span class="px-2.5 py-1 rounded-lg bg-slate-800 border border-slate-700 text-[11px] font-bold capitalize"
                                  x-text="'POS: ' + (currentCategory.pos_mode || 'standard').replace('_', ' ')"></span>
                            <span class="px-2.5 py-1 rounded-lg bg-slate-800 border border-slate-700 text-[11px] font-bold capitalize"
                                  x-text="'Public: ' + (currentCategory.storefront_mode || 'catalog') + ' Mode'"></span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2 border-t border-slate-800 text-xs">
                        <div>
                            <span class="text-[10px] uppercase tracking-wider font-bold text-slate-400 block mb-1.5">Included Navigation &amp; Modules</span>
                            <div class="flex flex-wrap gap-1.5">
                                <template x-for="mod in (currentCategory.active_modules || [])" :key="mod">
                                    <span class="px-2 py-0.5 rounded-md bg-orange-500/20 text-orange-200 border border-orange-500/30 text-[11px] font-semibold capitalize"
                                          x-text="mod.replace(/_/g, ' ')"></span>
                                </template>
                            </div>
                        </div>
                        <div>
                            <span class="text-[10px] uppercase tracking-wider font-bold text-slate-400 block mb-1.5">Smart Dashboard KPIs</span>
                            <div class="flex flex-wrap gap-1.5">
                                <template x-for="kpi in (currentCategory.kpis || [])" :key="kpi">
                                    <span class="px-2 py-0.5 rounded-md bg-slate-800 text-slate-200 border border-slate-700 text-[11px] font-semibold capitalize"
                                          x-text="kpi.replace(/_/g, ' ')"></span>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Optional Module Picker for Custom ('other') Businesses -->
                <div x-show="selectedCategory === 'other'" x-cloak
                     class="p-4 rounded-xl bg-slate-50 dark:bg-gray-900/50 border border-slate-200 dark:border-gray-700 space-y-3">
                    <div>
                        <p class="text-xs font-extrabold text-slate-900 dark:text-white">Customize Modules for Your Business</p>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">Pick only the workflows you need right now (you can adjust anytime in Settings).</p>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-xs">
                        @foreach (($configurableModules ?? []) as $modKey)
                            <label class="flex items-center gap-2 p-2 rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 cursor-pointer">
                                <input type="checkbox" name="custom_modules[{{ $modKey }}]" value="1"
                                       {{ in_array($modKey, ['pos', 'inventory'], true) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                                <span class="font-semibold text-gray-800 dark:text-gray-200 capitalize text-[11px]">{{ str_replace('_', ' ', $modKey) }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Step 2: Business Identity & Defaults -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 pt-4 border-t border-gray-100 dark:border-gray-700">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">
                        2. Business Name / ឈ្មោះអាជីវកម្ម *
                    </label>
                    <input type="text" name="name" x-model="name"
                           @input="if (!manuallyEdited) { slug = slugify(name); }"
                           required
                           placeholder="e.g. ផ្ទះបាយខ្មែរ រំដួល, Brown Coffee BKK, Phnom Penh Mart..."
                           class="w-full text-sm rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3.5 py-2.5 focus:border-orange-500 focus:ring-orange-500">
                    <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">
                        គាំទ្រទាំងភាសាខ្មែរ និងអង់គ្លេស (Supports Khmer &amp; English)
                    </p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">
                        Public Storefront Slug / តំណភ្ជាប់ (URL)
                    </label>
                    <div class="flex rounded-xl shadow-xs">
                        <span class="inline-flex items-center px-3 rounded-l-xl border border-r-0 border-gray-200 bg-gray-50 text-gray-500 text-xs dark:bg-gray-700 dark:border-gray-600 dark:text-gray-400">/</span>
                        <input type="text" name="slug" x-model="slug"
                               @input="manuallyEdited = true; slug = $event.target.value.toLowerCase().replace(/[\s_]+/g, '-').replace(/[^\p{L}\p{M}\p{N}\-]+/gu, '');"
                               placeholder="your-business-slug"
                               class="w-full text-xs rounded-r-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2.5 focus:border-orange-500 focus:ring-orange-500 font-mono">
                    </div>
                    <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1.5 flex items-center gap-1">
                        <span>Link:</span>
                        <span class="font-mono text-orange-500 font-bold" x-text="'/' + (slug || slugify(name) || 'your-business')"></span>
                    </p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">Phone Number</label>
                    <input type="text" name="phone" value="{{ old('phone') }}" placeholder="e.g. +855 23 999 111"
                           class="w-full text-sm rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3.5 py-2.5 focus:border-orange-500 focus:ring-orange-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">Telegram Username</label>
                    <input type="text" name="telegram_handle" value="{{ old('telegram_handle') }}" placeholder="@yourbusiness"
                           class="w-full text-sm rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3.5 py-2.5 focus:border-orange-500 focus:ring-orange-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">Default Currency</label>
                    <select name="default_currency" class="w-full text-xs rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2.5 focus:border-orange-500 focus:ring-orange-500">
                        <option value="USD" {{ old('default_currency', 'USD') === 'USD' ? 'selected' : '' }}>USD ($) - US Dollar</option>
                        <option value="KHR" {{ old('default_currency') === 'KHR' ? 'selected' : '' }}>KHR (៛) - Khmer Riel</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">Exchange Rate (1 USD to KHR)</label>
                    <input type="number" step="1" name="exchange_rate_khr" value="{{ old('exchange_rate_khr', 4100) }}" required
                           class="w-full text-sm rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3.5 py-2.5 focus:border-orange-500 focus:ring-orange-500">
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">Address</label>
                    <input type="text" name="address" value="{{ old('address') }}" placeholder="Street number, Sangkat, Khan, City..."
                           class="w-full text-sm rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3.5 py-2.5 focus:border-orange-500 focus:ring-orange-500">
                </div>
            </div>

            <div class="pt-4 flex items-center justify-between border-t border-gray-100 dark:border-gray-700">
                <button type="submit" form="logout-form" class="text-xs font-semibold text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
                    Log Out
                </button>

                <button type="submit" class="px-6 py-3 bg-orange-600 hover:bg-orange-700 text-white font-bold text-sm rounded-xl shadow-md transition">
                    Create Business &amp; Continue &rarr;
                </button>
            </div>
        </form>

        <form id="logout-form" method="POST" action="{{ route('logout') }}" class="hidden">
            @csrf
        </form>
    </div>
</body>
</html>
