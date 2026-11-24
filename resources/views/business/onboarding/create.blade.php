<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-gray-50 dark:bg-gray-900">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Create Your Business - SABAYRA Cambodia</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Kantumruy+Pro:wght@400;500;600;700&family=Battambang:wght@400;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans antialiased text-gray-800 dark:text-gray-100 flex items-center justify-center p-4 sm:p-6 lg:p-8">
    <div class="w-full max-w-2xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-3xl shadow-xl p-8 sm:p-10">
        <div class="text-center mb-8 flex flex-col items-center">
            <img src="{{ asset('images/sabayra-icon.png') }}" alt="SABAYRA" class="w-16 h-16 rounded-2xl object-contain shadow-lg shadow-orange-500/20 mb-3" />
            <span class="text-orange-500 text-3xl font-black tracking-tight">SABAYRA</span>
            <h1 class="text-2xl font-extrabold text-gray-900 dark:text-white mt-1">Setup Your Business Workspace</h1>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Start your digital menu, POS, and business management in minutes.</p>
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

        <form method="POST" action="{{ route('business.store') }}" class="space-y-6"
              x-data="{
                name: '{{ old('name', '') }}',
                slug: '{{ old('slug', '') }}',
                manuallyEdited: {{ old('slug') ? 'true' : 'false' }},
                slugify(val) {
                    return val.toLowerCase()
                              .trim()
                              .replace(/[^a-z0-9]+/g, '-')
                              .replace(/^-+|-+$/g, '');
                }
              }">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">
                        Business Name / ឈ្មោះអាជីវកម្ម *
                    </label>
                    <input type="text" name="name" x-model="name"
                           @input="if (!manuallyEdited) { let s = slugify(name); if (s.length > 0) { slug = s; } }"
                           required
                           placeholder="e.g. ផ្ទះបាយខ្មែរ រំដួល, Cafe Amazon, Romdoul Kitchen..."
                           class="w-full text-sm rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3.5 py-2.5 focus:border-orange-500 focus:ring-orange-500">
                    <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">
                        គាំទ្រទាំងភាសាខ្មែរ និងអង់គ្លេស (Supports Khmer &amp; English)
                    </p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">
                        Public Menu Slug / តំណភ្ជាប់ម៉ឺនុយ (URL)
                    </label>
                    <div class="flex rounded-xl shadow-xs">
                        <span class="inline-flex items-center px-3 rounded-l-xl border border-r-0 border-gray-200 bg-gray-50 text-gray-500 text-xs dark:bg-gray-700 dark:border-gray-600 dark:text-gray-400">/</span>
                        <input type="text" name="slug" x-model="slug"
                               @input="manuallyEdited = true; slug = $event.target.value.toLowerCase().replace(/[^a-z0-9-]+/g, '-');"
                               placeholder="romdoul-kitchen"
                               class="w-full text-xs rounded-r-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2.5 focus:border-orange-500 focus:ring-orange-500 font-mono">
                    </div>
                    <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1.5 flex items-center gap-1">
                        <span>Link:</span>
                        <span class="font-mono text-orange-500 font-bold" x-text="'/' + (slug || 'shop-auto')"></span>
                    </p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">Business Category *</label>
                    <select name="business_type" required class="w-full text-xs rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2.5 focus:border-orange-500 focus:ring-orange-500">
                        @foreach ($businessTypes as $key => $type)
                            <option value="{{ $key }}" {{ old('business_type') === $key ? 'selected' : '' }}>
                                {{ $type['name_en'] }} ({{ $type['name_km'] }})
                            </option>
                        @endforeach
                    </select>
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

                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">Address</label>
                    <input type="text" name="address" value="{{ old('address') }}" placeholder="Street number, Sangkat, Khan, City..."
                           class="w-full text-sm rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3.5 py-2.5 focus:border-orange-500 focus:ring-orange-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">Default Currency</label>
                    <select name="default_currency" class="w-full text-xs rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2.5 focus:border-orange-500 focus:ring-orange-500">
                        <option value="USD">USD ($) - US Dollar</option>
                        <option value="KHR">KHR (៛) - Khmer Riel</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">Exchange Rate (1 USD to KHR)</label>
                    <input type="number" step="1" name="exchange_rate_khr" value="{{ old('exchange_rate_khr', 4100) }}" required
                           class="w-full text-sm rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3.5 py-2.5 focus:border-orange-500 focus:ring-orange-500">
                </div>
            </div>

            <div class="pt-4 flex items-center justify-between border-t border-gray-100 dark:border-gray-700">
                <form method="POST" action="{{ route('logout') }}" class="inline">
                    @csrf
                    <button type="submit" class="text-xs font-semibold text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
                        Log Out
                    </button>
                </form>

                <button type="submit" class="px-6 py-3 bg-orange-600 hover:bg-orange-700 text-white font-bold text-sm rounded-xl shadow-md transition">
                    Create Business & Continue &rarr;
                </button>
            </div>
        </form>
    </div>
</body>
</html>
