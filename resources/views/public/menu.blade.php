<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-gray-50 dark:bg-gray-950">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $business->name }} - Digital Menu</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Battambang:wght@400;700&family=Inter:wght@300;400;500;600;700;800;900&family=Kantumruy+Pro:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@400;600;700;800&family=Roboto:wght@400;500;700&family=Siemreap&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @php
        $theme = $business->theme_settings ?? [];
        $preset = $theme['theme_preset'] ?? 'orange';
        $presetColors = [
            'orange' => '#ea580c',
            'coffee' => '#d97706',
            'emerald' => '#059669',
            'luxury' => '#7c3aed',
            'minimal' => '#0f172a',
        ];
        $primaryColor = !empty($theme['primary_color']) ? $theme['primary_color'] : ($presetColors[$preset] ?? '#ea580c');
        $fontFamily = !empty($theme['font']) ? $theme['font'] : "Inter, 'Kantumruy Pro', sans-serif";
        $layout = $theme['layout'] ?? 'modern';
    @endphp

    <style>
        :root {
            --brand-primary: {{ $primaryColor }};
            --brand-font: {!! $fontFamily !!};
        }
        body {
            font-family: var(--brand-font), sans-serif;
        }
        .bg-brand { background-color: var(--brand-primary) !important; }
        .text-brand { color: var(--brand-primary) !important; }
        .border-brand { border-color: var(--brand-primary) !important; }
        .ring-brand { --tw-ring-color: var(--brand-primary) !important; }
    </style>
</head>
<body class="h-full font-sans antialiased text-gray-900 dark:text-gray-100 bg-gray-50 dark:bg-gray-950 pb-28"
      x-data="menuCartApp()">

    <!-- Business Cover & Header Banner -->
    <div class="relative bg-gray-900 text-white">
        @if ($business->cover_url)
            <div class="h-44 sm:h-56 w-full overflow-hidden opacity-40">
                <img src="{{ $business->cover_url }}" alt="" class="w-full h-full object-cover">
            </div>
        @else
            <div class="h-36 sm:h-48 w-full opacity-90" style="background: linear-gradient(135deg, var(--brand-primary), #0f172a);"></div>
        @endif

        <div class="max-w-4xl mx-auto px-4 sm:px-6 relative -mt-16 sm:-mt-20 pb-4">
            <div class="flex items-end space-x-4">
                <div class="w-20 h-20 sm:w-24 sm:h-24 rounded-2xl bg-white dark:bg-gray-800 p-1 shadow-lg shrink-0 border-2 border-white dark:border-gray-700">
                    @if ($business->logo_url)
                        <img src="{{ $business->logo_url }}" alt="" class="w-full h-full rounded-xl object-cover">
                    @else
                        <div class="w-full h-full rounded-xl text-white font-black text-2xl flex items-center justify-center" style="background-color: var(--brand-primary);">
                            {{ strtoupper(substr($business->name, 0, 1)) }}
                        </div>
                    @endif
                </div>

                <div class="grow pb-1">
                    <h1 class="text-xl sm:text-2xl font-black tracking-tight text-white drop-shadow-sm flex items-center">
                        {{ $business->name }}
                    </h1>
                    @if ($business->tagline)
                        <p class="text-xs text-gray-200 mt-0.5 line-clamp-1 drop-shadow-xs">{{ $business->tagline }}</p>
                    @endif
                </div>

                <!-- Language Switcher -->
                <div class="flex items-center text-xs font-bold bg-black/40 backdrop-blur-md rounded-xl p-1 border border-white/10 shrink-0">
                    <a href="{{ route('locale.switch', 'en') }}"
                       :style="app()->getLocale() === 'en' ? 'background-color: var(--brand-primary); color: #fff;' : ''"
                       class="px-2.5 py-1 rounded-lg {{ app()->getLocale() === 'en' ? 'font-bold shadow-xs' : 'text-gray-300 hover:text-white' }}">EN</a>
                    <a href="{{ route('locale.switch', 'km') }}"
                       :style="app()->getLocale() === 'km' ? 'background-color: var(--brand-primary); color: #fff;' : ''"
                       class="px-2.5 py-1 rounded-lg font-khmer {{ app()->getLocale() === 'km' ? 'font-bold shadow-xs' : 'text-gray-300 hover:text-white' }}">ខ្មែរ</a>
                </div>
            </div>

            <!-- About / Business Description Banner -->
            @if ($business->description)
                <div class="mt-3.5 p-3.5 sm:p-4 rounded-2xl bg-black/40 backdrop-blur-md border border-white/15 text-xs sm:text-sm text-gray-200 leading-relaxed shadow-inner">
                    <div class="flex items-start gap-2.5">
                        <svg class="w-4 h-4 text-amber-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="font-normal">{{ $business->description }}</p>
                    </div>
                </div>
            @endif

            <!-- Business Meta Badges & Quick Contact & Social Links -->
            <div class="mt-4 flex flex-wrap items-center gap-2 text-[11px] text-gray-300">
                @if ($tableNumber)
                    <span class="inline-flex items-center px-2.5 py-1 rounded-xl bg-white/15 text-white border border-white/20 font-bold backdrop-blur-xs">
                        <svg class="w-3.5 h-3.5 mr-1" fill="currentColor" viewBox="0 0 20 20"><path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/><path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/></svg>
                        Table #{{ $tableNumber }}
                    </span>
                @endif

                @if ($business->phone)
                    <a href="tel:{{ $business->phone }}" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl bg-white/10 hover:bg-white/20 backdrop-blur-xs text-white font-bold transition">
                        <span>📞</span>
                        <span>{{ $business->phone }}</span>
                    </a>
                @endif

                <!-- Clickable Address to Google Maps -->
                @if ($business->address)
                    @php
                        $fullAddress = trim($business->address . ($business->city && !str_contains($business->address, $business->city) ? ', ' . $business->city : ''));
                        $mapsUrl = (str_starts_with($business->address, 'http://') || str_starts_with($business->address, 'https://'))
                            ? $business->address
                            : 'https://www.google.com/maps/search/?api=1&query=' . urlencode($fullAddress . ', Cambodia');
                    @endphp
                    <a href="{{ $mapsUrl }}" target="_blank" rel="noopener noreferrer"
                       title="View on Google Maps"
                       class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-red-500/20 hover:bg-red-500/30 text-red-300 border border-red-400/30 font-bold transition shadow-xs group">
                        <svg class="w-3.5 h-3.5 text-red-400 group-hover:scale-110 transition shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                        </svg>
                        <span class="truncate max-w-[160px] sm:max-w-xs">{{ $business->address }}</span>
                        <svg class="w-3 h-3 text-red-300/70 group-hover:text-white transition shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                        </svg>
                    </a>
                @endif

                <!-- Opening Hours Button -->
                <button type="button" @click="openingHoursModalOpen = true" class="inline-flex items-center px-2.5 py-1 rounded-xl bg-emerald-500/20 hover:bg-emerald-500/30 text-emerald-300 border border-emerald-400/30 font-bold transition cursor-pointer">
                    🟢 Open Now • Hours ▾
                </button>

                <!-- Social Media & Messaging Links -->
                @php
                    $social = $business->theme_settings['social_links'] ?? [];

                    // Telegram
                    $rawTg = trim($business->telegram_handle ?: ($social['telegram'] ?? ''));
                    $tgUrl = null;
                    if ($rawTg) {
                        $cleanedTg = ltrim(str_replace(['https://t.me/', 'http://t.me/', 't.me/'], '', $rawTg), '@/');
                        $tgUrl = 'https://t.me/' . $cleanedTg;
                    }

                    // Facebook
                    $rawFb = trim($social['facebook'] ?? '');
                    $fbUrl = null;
                    if ($rawFb) {
                        if (str_starts_with($rawFb, 'http://') || str_starts_with($rawFb, 'https://')) {
                            $fbUrl = $rawFb;
                        } elseif (str_starts_with($rawFb, 'facebook.com/') || str_starts_with($rawFb, 'web.facebook.com/')) {
                            $fbUrl = 'https://' . $rawFb;
                        } else {
                            $fbUrl = 'https://facebook.com/' . ltrim($rawFb, '@/');
                        }
                    }

                    // Instagram
                    $rawIg = trim($social['instagram'] ?? '');
                    $igUrl = null;
                    if ($rawIg) {
                        if (str_starts_with($rawIg, 'http://') || str_starts_with($rawIg, 'https://')) {
                            $igUrl = $rawIg;
                        } elseif (str_starts_with($rawIg, 'instagram.com/')) {
                            $igUrl = 'https://' . $rawIg;
                        } else {
                            $igUrl = 'https://instagram.com/' . ltrim($rawIg, '@/');
                        }
                    }

                    // TikTok
                    $rawTt = trim($social['tiktok'] ?? '');
                    $ttUrl = null;
                    if ($rawTt) {
                        if (str_starts_with($rawTt, 'http://') || str_starts_with($rawTt, 'https://')) {
                            $ttUrl = $rawTt;
                        } elseif (str_starts_with($rawTt, 'tiktok.com/')) {
                            $ttUrl = 'https://' . $rawTt;
                        } else {
                            $ttUrl = 'https://tiktok.com/@' . ltrim($rawTt, '@/');
                        }
                    }
                @endphp

                @if ($tgUrl)
                    <a href="{{ $tgUrl }}" target="_blank" rel="noopener noreferrer"
                       class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-sky-500/20 hover:bg-sky-500/30 text-sky-300 border border-sky-400/30 font-bold transition shadow-xs">
                        <svg class="w-3.5 h-3.5 fill-current shrink-0" viewBox="0 0 24 24"><path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.562 8.161c-.18.716-.962 4.084-1.362 5.753-.169.707-.493.944-.798.967-.665.051-1.17-.41-1.815-.833-1.009-.661-1.58-1.072-2.56-1.718-1.133-.746-.399-1.155.247-1.826.169-.175 3.1-2.843 3.156-3.084.007-.03.013-.143-.053-.203-.066-.06-.164-.04-.235-.024-.1.023-1.704 1.084-4.808 3.18-.455.313-.867.466-1.236.458-.407-.009-1.19-.23-1.773-.42-.716-.233-1.285-.357-1.236-.754.026-.207.31-.419.852-.637 3.336-1.452 5.562-2.41 6.678-2.875 3.178-1.321 3.84-1.55 4.27-1.558.095-.002.308.022.446.134.116.095.148.223.161.314.013.091.026.297.015.457z"/></svg>
                        <span>Telegram</span>
                    </a>
                @endif

                @if ($fbUrl)
                    <a href="{{ $fbUrl }}" target="_blank" rel="noopener noreferrer"
                       class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-blue-600/20 hover:bg-blue-600/30 text-blue-300 border border-blue-500/30 font-bold transition shadow-xs">
                        <svg class="w-3.5 h-3.5 fill-current shrink-0" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                        <span>Facebook</span>
                    </a>
                @endif

                @if ($igUrl)
                    <a href="{{ $igUrl }}" target="_blank" rel="noopener noreferrer"
                       class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-gradient-to-r from-pink-500/20 to-purple-500/20 hover:from-pink-500/30 hover:to-purple-500/30 text-pink-300 border border-pink-400/30 font-bold transition shadow-xs">
                        <svg class="w-3.5 h-3.5 fill-current shrink-0" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                        <span>Instagram</span>
                    </a>
                @endif

                @if ($ttUrl)
                    <a href="{{ $ttUrl }}" target="_blank" rel="noopener noreferrer"
                       class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-gray-800/80 hover:bg-gray-800 text-gray-200 border border-gray-600/40 font-bold transition shadow-xs">
                        <svg class="w-3.5 h-3.5 fill-current shrink-0" viewBox="0 0 24 24"><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.97v7.54c0 1.52-.38 3.08-1.2 4.34-1.14 1.78-3.08 2.97-5.18 3.22-1.87.23-3.8-.18-5.38-1.26-1.84-1.25-3.04-3.32-3.23-5.54-.25-2.89 1.25-5.8 3.75-7.22 1.51-.86 3.32-1.18 5.04-.92v4.18c-.89-.25-1.86-.18-2.69.23-1.05.51-1.75 1.59-1.76 2.76-.01 1.09.57 2.14 1.53 2.66.9.5 2.02.55 2.94.13.78-.36 1.35-1.11 1.49-1.96.06-.38.08-.77.08-1.16V.02h-.87z"/></svg>
                        <span>TikTok</span>
                    </a>
                @endif
            </div>
        </div>
    </div>

    <!-- Real-time Dish Search Bar -->
    <div class="max-w-4xl mx-auto px-4 sm:px-6 pt-3 pb-1">
        <div class="relative">
            <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input type="text"
                   x-model="searchQuery"
                   placeholder="{{ app()->getLocale() === 'km' ? 'ស្វែងរកមុខម្ហូប ឬភេសជ្ជៈ... (Search dishes, drinks...)' : 'Search dishes, drinks, or ingredients...' }}"
                   style="--tw-ring-color: var(--brand-primary);"
                   class="w-full pl-10 pr-9 py-2.5 rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 text-sm text-slate-900 dark:text-white placeholder-slate-500 dark:placeholder-slate-400 focus:outline-none focus:ring-2 shadow-xs transition">
            <button type="button" x-show="searchQuery.length > 0" @click="searchQuery = ''" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-700 dark:hover:text-white p-1">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    </div>

    <!-- Sticky Horizontal Category Navigation -->
    <div class="sticky top-0 z-20 bg-white/95 dark:bg-gray-900/95 backdrop-blur-md border-b border-gray-200/80 dark:border-gray-800 shadow-xs">
        <div class="max-w-4xl mx-auto px-4 overflow-x-auto no-scrollbar py-2.5 flex space-x-2">
            <button @click="activeCategory = 'all'"
                    :style="activeCategory === 'all' ? 'background-color: var(--brand-primary); color: #fff;' : ''"
                    :class="activeCategory === 'all' ? 'shadow-xs' : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'"
                    class="px-3.5 py-1.5 rounded-full text-xs font-bold whitespace-nowrap transition cursor-pointer">
                {{ __('menu.all_categories') }}
            </button>
            @foreach ($categories as $cat)
                <button @click="activeCategory = '{{ $cat->id }}'"
                        :style="activeCategory === '{{ $cat->id }}' ? 'background-color: var(--brand-primary); color: #fff;' : ''"
                        :class="activeCategory === '{{ $cat->id }}' ? 'shadow-xs' : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700'"
                        class="px-3.5 py-1.5 rounded-full text-xs font-bold whitespace-nowrap transition cursor-pointer">
                    {{ $cat->localized_name }}
                </button>
            @endforeach
        </div>
    </div>

    <!-- Main Menu Catalog Container -->
    <main class="max-w-4xl mx-auto px-4 sm:px-6 pt-6 space-y-8">
        <!-- Featured Items Section -->
        @if ($featuredProducts->count() > 0)
            <div x-show="activeCategory === 'all' && (!searchQuery || searchQuery.trim() === '')">
                <h2 class="text-base font-extrabold text-gray-900 dark:text-white flex items-center mb-4">
                    <span class="mr-2">⭐</span> {{ __('menu.featured_items') }}
                </h2>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4">
                    @foreach ($featuredProducts as $item)
                        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700/60 overflow-hidden shadow-xs hover:shadow-md transition flex flex-col justify-between cursor-pointer"
                             @click="openProductModal({{ json_encode($item) }})">
                            <div class="aspect-square bg-gray-100 dark:bg-gray-700 relative overflow-hidden">
                                @if ($item->primary_image_url)
                                    <img src="{{ $item->primary_image_url }}" alt="" class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full flex items-center justify-center font-bold text-gray-400 text-2xl">
                                        {{ strtoupper(substr($item->name_en, 0, 1)) }}
                                    </div>
                                @endif
                                @if ($item->discount_price && $item->discount_price < $item->base_price)
                                    <span class="absolute top-2 left-2 bg-red-600 text-white text-[10px] font-black px-2 py-0.5 rounded-full shadow-xs">
                                        SALE
                                    </span>
                                @endif
                            </div>
                            <div class="p-3">
                                <h3 class="font-bold text-xs sm:text-sm text-gray-900 dark:text-white line-clamp-1">{{ $item->localized_name }}</h3>
                                <p class="text-[11px] text-slate-600 dark:text-slate-300 font-medium line-clamp-1 mt-0.5">{{ $item->localized_description }}</p>
                                <div class="mt-2 flex items-baseline justify-between">
                                    <x-khr-price :usd="$item->effective_price" size="sm" />
                                    <button type="button"
                                            style="background-color: var(--brand-primary); color: #fff;"
                                            class="w-7 h-7 rounded-full flex items-center justify-center font-bold text-sm shadow-xs hover:opacity-90 active:scale-95 transition">
                                        +
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Category Sections -->
        @foreach ($categories as $category)
            <div x-show="activeCategory === 'all' || activeCategory === '{{ $category->id }}'" class="space-y-4">
                <div class="flex items-center space-x-2 border-b border-gray-200 dark:border-gray-800 pb-2">
                    <h2 class="text-lg font-black text-gray-900 dark:text-white">{{ $category->localized_name }}</h2>
                    <span class="text-xs font-bold text-gray-600 dark:text-gray-300">({{ $category->products->count() }})</span>
                </div>

                @if ($layout === 'grid')
                    <!-- Layout Option: Visual Grid Showcase -->
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3.5 sm:gap-4">
                        @forelse ($category->products as $product)
                            <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700/60 overflow-hidden shadow-xs hover:shadow-md transition flex flex-col justify-between cursor-pointer"
                                 x-show="matchesSearch(@js(strtolower($product->name_en . ' ' . $product->name_km . ' ' . $product->description_en . ' ' . $product->description_km)))"
                                 @click="openProductModal({{ json_encode($product) }})">
                                <div class="aspect-square bg-gray-100 dark:bg-gray-700 shrink-0 overflow-hidden relative">
                                    @if ($product->primary_image_url)
                                        <img src="{{ $product->primary_image_url }}" alt="" class="w-full h-full object-cover">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center font-black text-gray-300 text-xl">
                                            {{ strtoupper(substr($product->name_en, 0, 1)) }}
                                        </div>
                                    @endif
                                    @if ($product->is_featured)
                                        <span class="absolute top-1 left-1 bg-amber-500 text-white text-[9px] font-bold px-1.5 py-0.2 rounded">★</span>
                                    @endif
                                </div>

                                <div class="p-3 flex flex-col justify-between grow">
                                    <div>
                                        <h3 class="font-bold text-xs sm:text-sm text-gray-900 dark:text-white truncate">{{ $product->localized_name }}</h3>
                                        <p class="text-[11px] text-slate-600 dark:text-slate-300 font-medium line-clamp-1 mt-0.5">{{ $product->localized_description }}</p>
                                    </div>
                                    <div class="mt-2.5 flex items-center justify-between">
                                        <x-khr-price :usd="$product->effective_price" size="sm" />
                                        <button type="button"
                                                style="background-color: var(--brand-primary); color: #fff;"
                                                class="w-7 h-7 rounded-full flex items-center justify-center font-bold text-sm shadow-xs hover:opacity-90 active:scale-95 transition">
                                            +
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="col-span-full text-xs text-gray-600 dark:text-gray-300 font-medium py-4">No items listed in this category.</p>
                        @endforelse
                    </div>
                @elseif ($layout === 'minimal')
                    <!-- Layout Option: Minimal Compact List -->
                    <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700/60 shadow-xs divide-y divide-gray-100 dark:divide-gray-700/60 overflow-hidden">
                        @forelse ($category->products as $product)
                            <div class="p-3 hover:bg-gray-50/50 dark:hover:bg-gray-750 transition flex items-center justify-between gap-3 cursor-pointer"
                                 x-show="matchesSearch(@js(strtolower($product->name_en . ' ' . $product->name_km . ' ' . $product->description_en . ' ' . $product->description_km)))"
                                 @click="openProductModal({{ json_encode($product) }})">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="w-12 h-12 rounded-xl bg-gray-100 dark:bg-gray-700 shrink-0 overflow-hidden">
                                        @if ($product->primary_image_url)
                                            <img src="{{ $product->primary_image_url }}" alt="" class="w-full h-full object-cover">
                                        @else
                                            <div class="w-full h-full flex items-center justify-center font-bold text-gray-400 text-xs">
                                                {{ strtoupper(substr($product->name_en, 0, 1)) }}
                                            </div>
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <h3 class="font-bold text-xs sm:text-sm text-gray-900 dark:text-white truncate">{{ $product->localized_name }}</h3>
                                        <p class="text-[11px] text-slate-600 dark:text-slate-300 font-medium truncate">{{ $product->localized_description }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2.5 shrink-0">
                                    <x-khr-price :usd="$product->effective_price" size="sm" />
                                    <button type="button"
                                            style="background-color: var(--brand-primary); color: #fff;"
                                            class="w-7 h-7 rounded-full flex items-center justify-center font-bold text-sm shadow-xs hover:opacity-90 active:scale-95 transition">
                                        +
                                    </button>
                                </div>
                            </div>
                        @empty
                            <p class="text-xs text-gray-600 dark:text-gray-300 font-medium py-4 px-3">No items listed in this category.</p>
                        @endforelse
                    </div>
                @else
                    <!-- Layout Option: Modern Clean Cards (Default) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 sm:gap-4">
                        @forelse ($category->products as $product)
                            <div class="p-3 sm:p-4 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700/60 shadow-xs hover:shadow-md transition flex items-center space-x-3.5 cursor-pointer"
                                 x-show="matchesSearch(@js(strtolower($product->name_en . ' ' . $product->name_km . ' ' . $product->description_en . ' ' . $product->description_km)))"
                                 @click="openProductModal({{ json_encode($product) }})">
                                <div class="w-20 h-20 sm:w-24 sm:h-24 rounded-xl bg-gray-100 dark:bg-gray-700 shrink-0 overflow-hidden relative">
                                    @if ($product->primary_image_url)
                                        <img src="{{ $product->primary_image_url }}" alt="" class="w-full h-full object-cover">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center font-black text-gray-300 text-xl">
                                            {{ strtoupper(substr($product->name_en, 0, 1)) }}
                                        </div>
                                    @endif
                                    @if ($product->is_featured)
                                        <span class="absolute top-1 left-1 bg-amber-500 text-white text-[9px] font-bold px-1.5 py-0.2 rounded">★</span>
                                    @endif
                                </div>

                                <div class="grow min-w-0">
                                    <h3 class="font-bold text-sm text-gray-900 dark:text-white truncate">{{ $product->localized_name }}</h3>
                                    <p class="text-xs text-slate-600 dark:text-slate-300 font-medium line-clamp-2 mt-0.5">{{ $product->localized_description }}</p>
                                    <div class="mt-2 flex items-center justify-between">
                                        <x-khr-price :usd="$product->effective_price" size="base" />
                                        <button type="button"
                                                style="background-color: var(--brand-primary); color: #fff;"
                                                class="px-3.5 py-1.5 text-xs font-bold rounded-xl transition shadow-xs hover:opacity-90 active:scale-95">
                                            {{ __('menu.add_to_cart') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="col-span-2 text-xs text-gray-600 dark:text-gray-300 font-medium py-4">No items listed in this category.</p>
                        @endforelse
                    </div>
                @endif
            </div>
        @endforeach
    </main>

    <!-- Floating Cart Bar (Customer Mobile-First Action Bar) -->
    <div x-show="cartTotalCount > 0"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="translate-y-full"
         x-transition:enter-end="translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="translate-y-0"
         x-transition:leave-end="translate-y-full"
         class="fixed bottom-4 inset-x-4 max-w-lg mx-auto z-40"
         style="display: none;">
        <button @click="cartDrawerOpen = true"
                style="background-color: var(--brand-primary);"
                class="w-full p-4 text-white rounded-2xl shadow-2xl flex items-center justify-between font-bold text-sm transition transform active:scale-98 hover:opacity-95">
            <div class="flex items-center space-x-2.5">
                <span class="w-7 h-7 rounded-full bg-white text-gray-900 flex items-center justify-center font-black text-xs" x-text="cartTotalCount"></span>
                <span>{{ __('menu.view_cart') }}</span>
            </div>
            <div class="text-right">
                <span x-text="'$' + cartTotalAmount.toFixed(2)"></span>
                <span class="text-xs text-white/80 ml-1" x-text="'(~ ' + Math.round(cartTotalAmount * {{ $business->exchange_rate_khr }}).toLocaleString() + ' ៛)'"></span>
            </div>
        </button>
    </div>

    <!-- Product Customization Modal -->
    <div x-show="productModalOpen" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-black/60 backdrop-blur-xs transition-opacity" @click="productModalOpen = false"></div>

        <div class="flex min-h-full items-end sm:items-center justify-center p-0 sm:p-4">
            <div class="relative w-full max-w-md bg-white dark:bg-gray-900 rounded-t-3xl sm:rounded-3xl overflow-hidden shadow-2xl border border-gray-100 dark:border-gray-800 p-6 space-y-5"
                 @click.away="productModalOpen = false">
                <!-- Close Button -->
                <button @click="productModalOpen = false" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 dark:hover:text-white">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>

                <div class="flex items-center space-x-3.5">
                    <template x-if="selectedProduct?.primary_image_url">
                        <img :src="selectedProduct?.primary_image_url" alt="" class="w-16 h-16 rounded-xl object-cover shrink-0">
                    </template>
                    <div>
                        <h3 class="font-bold text-base text-gray-900 dark:text-white" x-text="selectedProduct?.name_en"></h3>
                        <p class="text-xs text-gray-600 dark:text-gray-300 font-khmer font-medium" x-text="selectedProduct?.name_km"></p>
                        <p class="text-sm font-bold mt-1" style="color: var(--brand-primary);" x-text="'$' + (selectedProduct?.discount_price && selectedProduct?.discount_price > 0 ? selectedProduct?.discount_price : selectedProduct?.base_price)"></p>
                    </div>
                </div>

                <!-- Variants Selection (if available) -->
                <template x-if="selectedProduct?.variants && selectedProduct.variants.length > 0">
                    <div>
                        <label class="block text-xs font-bold text-gray-800 dark:text-gray-200 uppercase tracking-wider mb-2">{{ __('menu.variants') }}</label>
                        <div class="space-y-2">
                            <template x-for="variant in selectedProduct.variants" :key="variant.id">
                                <label class="flex items-center justify-between p-2.5 rounded-xl border border-gray-200 dark:border-gray-700 cursor-pointer text-xs">
                                    <div class="flex items-center space-x-2">
                                        <input type="radio" name="selected_variant" :value="variant.id" x-model="modalSelectedVariantId"
                                               style="accent-color: var(--brand-primary);" class="focus:ring-0">
                                        <span class="font-semibold text-gray-800 dark:text-gray-200" x-text="variant.name_en"></span>
                                    </div>
                                    <span class="text-gray-700 dark:text-gray-200 font-bold" x-text="variant.price_adjustment > 0 ? '+$' + parseFloat(variant.price_adjustment).toFixed(2) : '+$0.00'"></span>
                                </label>
                            </template>
                        </div>
                    </div>
                </template>

                <!-- Add-ons Selection (if available) -->
                <template x-if="selectedProduct?.addons && selectedProduct.addons.length > 0">
                    <div>
                        <label class="block text-xs font-bold text-gray-800 dark:text-gray-200 uppercase tracking-wider mb-2">{{ __('menu.addons') }}</label>
                        <div class="space-y-2">
                            <template x-for="addon in selectedProduct.addons" :key="addon.id">
                                <label class="flex items-center justify-between p-2.5 rounded-xl border border-gray-200 dark:border-gray-700 cursor-pointer text-xs">
                                    <div class="flex items-center space-x-2">
                                        <input type="checkbox" :value="addon.id" x-model="modalSelectedAddonIds"
                                               style="accent-color: var(--brand-primary);" class="rounded focus:ring-0">
                                        <span class="font-semibold text-gray-800 dark:text-gray-200" x-text="addon.name_en"></span>
                                    </div>
                                    <span class="text-gray-700 dark:text-gray-200 font-bold" x-text="'+$' + parseFloat(addon.price).toFixed(2)"></span>
                                </label>
                            </template>
                        </div>
                    </div>
                </template>

                <!-- Quantity Stepper -->
                <div class="flex items-center justify-between pt-2">
                    <span class="text-xs font-bold text-gray-800 dark:text-gray-200 uppercase tracking-wider">Quantity</span>
                    <div class="flex items-center space-x-3 bg-gray-100 dark:bg-gray-800 rounded-xl p-1">
                        <button type="button" @click="if (modalQuantity > 1) modalQuantity--" class="w-8 h-8 rounded-lg bg-white dark:bg-gray-700 text-gray-800 dark:text-white font-bold flex items-center justify-center">-</button>
                        <span class="w-8 text-center font-bold text-sm" x-text="modalQuantity"></span>
                        <button type="button" @click="modalQuantity++" class="w-8 h-8 rounded-lg bg-white dark:bg-gray-700 text-gray-800 dark:text-white font-bold flex items-center justify-center">+</button>
                    </div>
                </div>

                <button type="button" @click="addItemToCart()"
                        style="background-color: var(--brand-primary);"
                        class="w-full py-3 text-white font-bold text-sm rounded-xl shadow-md transition hover:opacity-95">
                    {{ __('menu.add_to_cart') }}
                </button>
            </div>
        </div>
    </div>

    <!-- Cart Slide-Over / Checkout Drawer -->
    <div x-show="cartDrawerOpen" class="relative z-50" style="display: none;" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-black/60 backdrop-blur-xs transition-opacity" @click="cartDrawerOpen = false"></div>

        <div class="fixed inset-y-0 right-0 flex max-w-full pl-10">
            <div class="w-screen max-w-md bg-white dark:bg-gray-900 shadow-2xl flex flex-col justify-between p-6">
                <!-- Top Header -->
                <div>
                    <div class="flex items-center justify-between pb-4 border-b border-gray-200 dark:border-gray-800">
                        <h2 class="text-lg font-black text-gray-900 dark:text-white">{{ __('menu.cart') }}</h2>
                        <button @click="cartDrawerOpen = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-white">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <!-- Items List -->
                    <div class="mt-4 space-y-3 overflow-y-auto max-h-[40vh] divide-y divide-gray-100 dark:divide-gray-800">
                        <template x-for="(item, index) in cart" :key="index">
                            <div class="pt-3 flex items-start justify-between text-xs">
                                <div>
                                    <p class="font-bold text-gray-900 dark:text-white" x-text="item.product_name"></p>
                                    <template x-if="item.variant_name">
                                        <p class="text-[11px] text-gray-600 dark:text-gray-300 font-medium" x-text="'Size: ' + item.variant_name"></p>
                                    </template>
                                    <p class="font-bold mt-0.5" style="color: var(--brand-primary);" x-text="'$' + (item.unit_price * item.quantity).toFixed(2)"></p>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span class="font-bold text-gray-900 dark:text-white" x-text="'x' + item.quantity"></span>
                                    <button type="button" @click="cart.splice(index, 1); saveCart();" class="text-red-500 hover:text-red-700 font-bold ml-2">✕</button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Checkout Form -->
                <form @submit.prevent="submitOrder" class="space-y-4 pt-4 border-t border-gray-200 dark:border-gray-800 text-xs">
                    <div>
                        <label class="block font-bold uppercase tracking-wider text-gray-800 dark:text-gray-200 mb-1">Dining Option</label>
                        <div class="grid grid-cols-3 gap-2">
                            <button type="button" @click="orderType = 'dine_in'"
                                    :style="orderType === 'dine_in' ? 'background-color: var(--brand-primary); color: #fff;' : ''"
                                    :class="orderType === 'dine_in' ? 'font-bold' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300'"
                                    class="py-2 rounded-xl text-center transition">Dine-In</button>
                            <button type="button" @click="orderType = 'takeaway'"
                                    :style="orderType === 'takeaway' ? 'background-color: var(--brand-primary); color: #fff;' : ''"
                                    :class="orderType === 'takeaway' ? 'font-bold' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300'"
                                    class="py-2 rounded-xl text-center transition">Takeaway</button>
                            <button type="button" @click="orderType = 'delivery'"
                                    :style="orderType === 'delivery' ? 'background-color: var(--brand-primary); color: #fff;' : ''"
                                    :class="orderType === 'delivery' ? 'font-bold' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300'"
                                    class="py-2 rounded-xl text-center transition">Delivery</button>
                        </div>
                    </div>

                    <div x-show="orderType === 'dine_in'">
                        <label class="block font-bold uppercase tracking-wider text-gray-800 dark:text-gray-200 mb-1">Table Number</label>
                        <input type="text" x-model="tableNumber" required class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white px-3 py-2 text-xs">
                    </div>

                    <div x-show="orderType === 'delivery'" style="display: none;">
                        <label class="block font-bold uppercase tracking-wider text-gray-800 dark:text-gray-200 mb-1">Delivery Address</label>
                        <input type="text" x-model="deliveryAddress" placeholder="Street, house number, landmarks..." class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white px-3 py-2 text-xs">
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block font-bold uppercase tracking-wider text-gray-800 dark:text-gray-200 mb-1">Your Name *</label>
                            <input type="text" x-model="customerName" required placeholder="Name" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white px-3 py-2 text-xs">
                        </div>
                        <div>
                            <label class="block font-bold uppercase tracking-wider text-gray-800 dark:text-gray-200 mb-1">Phone Number *</label>
                            <input type="text" x-model="customerPhone" required placeholder="012 345 678" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white px-3 py-2 text-xs">
                        </div>
                    </div>

                    <div>
                        <label class="block font-bold uppercase tracking-wider text-gray-800 dark:text-gray-200 mb-1">Payment Method</label>
                        <select x-model="paymentMethod" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white px-3 py-2 text-xs font-medium">
                            <option value="khqr">Bakong KHQR (Scan to Pay)</option>
                            <option value="cash">Cash at Counter</option>
                        </select>
                    </div>

                    <!-- Total Calculation -->
                    <div class="pt-3 border-t border-gray-100 dark:border-gray-800 space-y-1">
                        <div class="flex justify-between font-bold text-sm text-gray-900 dark:text-white">
                            <span>Total (USD)</span>
                            <span class="font-black" style="color: var(--brand-primary);" x-text="'$' + cartTotalAmount.toFixed(2)"></span>
                        </div>
                        <div class="flex justify-between text-xs text-gray-600 dark:text-gray-300 font-medium">
                            <span>Khmer Riel Equivalent</span>
                            <span class="font-bold text-gray-900 dark:text-white" x-text="Math.round(cartTotalAmount * {{ $business->exchange_rate_khr }}).toLocaleString() + ' ៛'"></span>
                        </div>
                    </div>

                    <button type="submit" :disabled="isSubmitting"
                            style="background-color: var(--brand-primary);"
                            class="w-full py-3 text-white font-bold rounded-xl shadow-md transition disabled:opacity-50 hover:opacity-95">
                        <span x-show="!isSubmitting">{{ __('menu.place_order') }}</span>
                        <span x-show="isSubmitting" style="display: none;">Processing Order...</span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Opening Hours Schedule Modal -->
    <div x-show="openingHoursModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="fixed inset-0 bg-black/60 backdrop-blur-xs transition-opacity" @click="openingHoursModalOpen = false"></div>
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div class="relative w-full max-w-sm bg-white dark:bg-gray-900 rounded-3xl p-6 shadow-2xl border border-gray-100 dark:border-gray-800" @click.outside="openingHoursModalOpen = false">
                <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-800">
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                        <span>🕒 Opening Hours</span>
                        <span class="text-xs font-normal text-slate-500">/ ម៉ោងដំណើរការ</span>
                    </h3>
                    <button type="button" @click="openingHoursModalOpen = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-white p-1">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="py-3 space-y-2 text-xs">
                    @php
                        $hours = $business->opening_hours ?? [];
                        $dayMap = [
                            'monday' => 'Monday (ច័ន្ទ)',
                            'tuesday' => 'Tuesday (អង្គារ)',
                            'wednesday' => 'Wednesday (ពុធ)',
                            'thursday' => 'Thursday (ព្រហស្បតិ៍)',
                            'friday' => 'Friday (សុក្រ)',
                            'saturday' => 'Saturday (សៅរ៍)',
                            'sunday' => 'Sunday (អាទិត្យ)',
                        ];
                    @endphp
                    @foreach($dayMap as $dayKey => $dayTitle)
                        @php $d = $hours[$dayKey] ?? ['open' => '07:00', 'close' => '22:00']; @endphp
                        <div class="flex items-center justify-between py-1.5 border-b border-gray-50 dark:border-gray-800/60">
                            <span class="font-bold text-slate-800 dark:text-slate-200">{{ $dayTitle }}</span>
                            <span class="font-mono text-emerald-600 dark:text-emerald-400 font-bold">{{ $d['open'] ?? '07:00' }} - {{ $d['close'] ?? '22:00' }}</span>
                        </div>
                    @endforeach
                </div>

                <div class="pt-3">
                    <button type="button" @click="openingHoursModalOpen = false" class="w-full py-2.5 rounded-xl bg-slate-100 dark:bg-gray-800 text-slate-800 dark:text-slate-200 font-bold text-xs hover:bg-slate-200 transition">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Alpine.js Store Script -->
    <script>
        function menuCartApp() {
            return {
                searchQuery: '',
                openingHoursModalOpen: false,
                activeCategory: 'all',
                cart: JSON.parse(localStorage.getItem('cart_{{ $business->id }}') || '[]'),
                cartDrawerOpen: false,
                productModalOpen: false,
                selectedProduct: null,
                modalSelectedVariantId: null,
                modalSelectedAddonIds: [],
                modalQuantity: 1,
                orderType: '{{ $tableNumber ? "dine_in" : "dine_in" }}',
                tableNumber: '{{ $tableNumber ?? "" }}',
                deliveryAddress: '',
                customerName: localStorage.getItem('customer_name') || '',
                customerPhone: localStorage.getItem('customer_phone') || '',
                paymentMethod: 'khqr',
                isSubmitting: false,

                matchesSearch(haystack) {
                    if (!this.searchQuery || this.searchQuery.trim() === '') return true;
                    return (haystack || '').toLowerCase().includes(this.searchQuery.toLowerCase().trim());
                },

                get cartTotalCount() {
                    return this.cart.reduce((total, item) => total + item.quantity, 0);
                },

                get cartTotalAmount() {
                    return this.cart.reduce((total, item) => total + (item.unit_price * item.quantity), 0);
                },

                saveCart() {
                    localStorage.setItem('cart_{{ $business->id }}', JSON.stringify(this.cart));
                },

                openProductModal(product) {
                    this.selectedProduct = product;
                    this.modalSelectedVariantId = product.variants && product.variants.length > 0 ? product.variants[0].id : null;
                    this.modalSelectedAddonIds = [];
                    this.modalQuantity = 1;
                    this.productModalOpen = true;
                },

                addItemToCart() {
                    if (!this.selectedProduct) return;

                    let basePrice = this.selectedProduct.discount_price && this.selectedProduct.discount_price > 0
                        ? parseFloat(this.selectedProduct.discount_price)
                        : parseFloat(this.selectedProduct.base_price);

                    let variantName = null;
                    if (this.modalSelectedVariantId && this.selectedProduct.variants) {
                        const variant = this.selectedProduct.variants.find(v => v.id === this.modalSelectedVariantId);
                        if (variant) {
                            basePrice += parseFloat(variant.price_adjustment);
                            variantName = variant.name_en;
                        }
                    }

                    // Addons
                    const addons = [...this.modalSelectedAddonIds];

                    this.cart.push({
                        product_id: this.selectedProduct.id,
                        product_name: this.selectedProduct.name_en,
                        variant_id: this.modalSelectedVariantId,
                        variant_name: variantName,
                        addons: addons,
                        unit_price: basePrice,
                        quantity: this.modalQuantity,
                    });

                    this.saveCart();
                    this.productModalOpen = false;
                },

                async submitOrder() {
                    if (this.cart.length === 0) return;
                    this.isSubmitting = true;

                    localStorage.setItem('customer_name', this.customerName);
                    localStorage.setItem('customer_phone', this.customerPhone);

                    try {
                        const response = await fetch('/{{ $business->slug }}/checkout', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            },
                            body: JSON.stringify({
                                customer_name: this.customerName,
                                customer_phone: this.customerPhone,
                                order_type: this.orderType,
                                table_number: this.tableNumber,
                                delivery_address: this.deliveryAddress,
                                payment_method: this.paymentMethod,
                                items: this.cart,
                            })
                        });

                        const data = await response.json();
                        if (response.ok && data.success) {
                            localStorage.removeItem('cart_{{ $business->id }}');
                            window.location.href = data.redirect_url;
                        } else {
                            alert(data.error || 'Failed to submit order.');
                        }
                    } catch (e) {
                        alert('Connection error. Please try again.');
                    } finally {
                        this.isSubmitting = false;
                    }
                }
            };
        }
    </script>
</body>
</html>