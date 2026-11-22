<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-gray-50 dark:bg-gray-950">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $business->name }} - Digital Menu</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Kantumruy+Pro:wght@400;500;600;700&family=Battambang:wght@400;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @php
        $theme = $business->theme_settings ?? [];
        $primaryColor = $theme['primary_color'] ?? '#f97316';
    @endphp

    <style>
        :root {
            --brand-primary: {{ $primaryColor }};
        }
        .bg-brand { background-color: var(--brand-primary); }
        .text-brand { color: var(--brand-primary); }
        .border-brand { border-color: var(--brand-primary); }
        .ring-brand { --tw-ring-color: var(--brand-primary); }
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
            <div class="h-36 sm:h-48 w-full bg-linear-to-r from-orange-600 to-amber-700 opacity-90"></div>
        @endif

        <div class="max-w-4xl mx-auto px-4 sm:px-6 relative -mt-16 sm:-mt-20 pb-4">
            <div class="flex items-end space-x-4">
                <div class="w-20 h-20 sm:w-24 sm:h-24 rounded-2xl bg-white dark:bg-gray-800 p-1 shadow-lg shrink-0 border-2 border-white dark:border-gray-700">
                    @if ($business->logo_url)
                        <img src="{{ $business->logo_url }}" alt="" class="w-full h-full rounded-xl object-cover">
                    @else
                        <div class="w-full h-full rounded-xl bg-orange-600 text-white font-black text-2xl flex items-center justify-center">
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
                    <a href="{{ route('locale.switch', 'en') }}" class="px-2 py-1 rounded-lg {{ app()->getLocale() === 'en' ? 'bg-orange-600 text-white font-bold' : 'text-gray-300 hover:text-white' }}">EN</a>
                    <a href="{{ route('locale.switch', 'km') }}" class="px-2 py-1 rounded-lg {{ app()->getLocale() === 'km' ? 'bg-orange-600 text-white font-khmer font-bold' : 'text-gray-300 font-khmer hover:text-white' }}">ខ្មែរ</a>
                </div>
            </div>

            <!-- Business Meta Badges & Quick Contact -->
            <div class="mt-4 flex flex-wrap items-center gap-2 text-[11px] text-gray-300">
                @if ($tableNumber)
                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-orange-500/20 text-orange-300 border border-orange-400/30 font-bold">
                        <svg class="w-3.5 h-3.5 mr-1" fill="currentColor" viewBox="0 0 20 20"><path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"/><path fill-rule="evenodd" d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z" clip-rule="evenodd"/></svg>
                        Table #{{ $tableNumber }}
                    </span>
                @endif
                @if ($business->phone)
                    <a href="tel:{{ $business->phone }}" class="inline-flex items-center px-2.5 py-1 rounded-lg bg-white/10 hover:bg-white/20 backdrop-blur-xs text-white font-bold transition">
                        📞 {{ $business->phone }}
                    </a>
                @endif
                @php
                    $tg = ltrim($business->telegram_handle ?: ($business->theme_settings['social_links']['telegram'] ?? ''), '@');
                @endphp
                @if ($tg)
                    <a href="https://t.me/{{ $tg }}" target="_blank" class="inline-flex items-center px-2.5 py-1 rounded-lg bg-blue-500/20 hover:bg-blue-500/30 text-blue-300 border border-blue-400/30 font-bold transition">
                        💬 Telegram
                    </a>
                @endif
                @if ($business->address)
                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-white/10 backdrop-blur-xs truncate max-w-xs">
                        📍 {{ $business->address }}
                    </span>
                @endif
                <button type="button" @click="openingHoursModalOpen = true" class="inline-flex items-center px-2.5 py-1 rounded-lg bg-emerald-500/20 hover:bg-emerald-500/30 text-emerald-300 border border-emerald-400/30 font-bold transition cursor-pointer">
                    🟢 Open Now • Hours ▾
                </button>
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
                   class="w-full pl-10 pr-9 py-2.5 rounded-xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 text-sm text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-orange-500 shadow-xs transition">
            <button type="button" x-show="searchQuery.length > 0" @click="searchQuery = ''" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    </div>

    <!-- Sticky Horizontal Category Navigation -->
    <div class="sticky top-0 z-20 bg-white/95 dark:bg-gray-900/95 backdrop-blur-md border-b border-gray-200/80 dark:border-gray-800 shadow-xs">
        <div class="max-w-4xl mx-auto px-4 overflow-x-auto no-scrollbar py-2.5 flex space-x-2">
            <button @click="activeCategory = 'all'"
                    :class="activeCategory === 'all' ? 'bg-orange-600 text-white shadow-xs' : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300 hover:bg-gray-200'"
                    class="px-3.5 py-1.5 rounded-full text-xs font-bold whitespace-nowrap transition">
                {{ __('menu.all_categories') }}
            </button>
            @foreach ($categories as $cat)
                <button @click="activeCategory = '{{ $cat->id }}'"
                        :class="activeCategory === '{{ $cat->id }}' ? 'bg-orange-600 text-white shadow-xs' : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300 hover:bg-gray-200'"
                        class="px-3.5 py-1.5 rounded-full text-xs font-bold whitespace-nowrap transition">
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
                                    <button type="button" class="w-7 h-7 rounded-full bg-orange-600 text-white flex items-center justify-center font-bold text-sm shadow-xs hover:bg-orange-700">
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
                    <span class="text-xs font-semibold text-gray-400">({{ $category->products->count() }})</span>
                </div>

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
                                    <button type="button" class="px-3 py-1 bg-orange-50 text-orange-600 hover:bg-orange-600 hover:text-white dark:bg-orange-950/40 dark:text-orange-400 dark:hover:bg-orange-600 dark:hover:text-white text-xs font-bold rounded-lg transition shadow-xs">
                                        {{ __('menu.add_to_cart') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="col-span-2 text-xs text-gray-400 py-4">No items listed in this category.</p>
                    @endforelse
                </div>
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
                class="w-full p-4 bg-orange-600 hover:bg-orange-700 text-white rounded-2xl shadow-2xl flex items-center justify-between font-bold text-sm transition transform active:scale-98">
            <div class="flex items-center space-x-2.5">
                <span class="w-7 h-7 rounded-full bg-white text-orange-600 flex items-center justify-center font-black text-xs" x-text="cartTotalCount"></span>
                <span>{{ __('menu.view_cart') }}</span>
            </div>
            <div class="text-right">
                <span x-text="'$' + cartTotalAmount.toFixed(2)"></span>
                <span class="text-xs text-orange-200 ml-1" x-text="'(~ ' + Math.round(cartTotalAmount * {{ $business->exchange_rate_khr }}).toLocaleString() + ' ៛)'"></span>
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
                        <p class="text-xs text-gray-500 font-khmer" x-text="selectedProduct?.name_km"></p>
                        <p class="text-sm font-bold text-orange-600 mt-1" x-text="'$' + (selectedProduct?.discount_price && selectedProduct?.discount_price > 0 ? selectedProduct?.discount_price : selectedProduct?.base_price)"></p>
                    </div>
                </div>

                <!-- Variants Selection (if available) -->
                <template x-if="selectedProduct?.variants && selectedProduct.variants.length > 0">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">{{ __('menu.variants') }}</label>
                        <div class="space-y-2">
                            <template x-for="variant in selectedProduct.variants" :key="variant.id">
                                <label class="flex items-center justify-between p-2.5 rounded-xl border border-gray-200 dark:border-gray-700 cursor-pointer text-xs">
                                    <div class="flex items-center space-x-2">
                                        <input type="radio" name="selected_variant" :value="variant.id" x-model="modalSelectedVariantId" class="text-orange-600 focus:ring-orange-500">
                                        <span class="font-semibold text-gray-800 dark:text-gray-200" x-text="variant.name_en"></span>
                                    </div>
                                    <span class="text-gray-500 font-bold" x-text="variant.price_adjustment > 0 ? '+$' + parseFloat(variant.price_adjustment).toFixed(2) : '+$0.00'"></span>
                                </label>
                            </template>
                        </div>
                    </div>
                </template>

                <!-- Add-ons Selection (if available) -->
                <template x-if="selectedProduct?.addons && selectedProduct.addons.length > 0">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">{{ __('menu.addons') }}</label>
                        <div class="space-y-2">
                            <template x-for="addon in selectedProduct.addons" :key="addon.id">
                                <label class="flex items-center justify-between p-2.5 rounded-xl border border-gray-200 dark:border-gray-700 cursor-pointer text-xs">
                                    <div class="flex items-center space-x-2">
                                        <input type="checkbox" :value="addon.id" x-model="modalSelectedAddonIds" class="rounded text-orange-600 focus:ring-orange-500">
                                        <span class="font-semibold text-gray-800 dark:text-gray-200" x-text="addon.name_en"></span>
                                    </div>
                                    <span class="text-gray-500 font-bold" x-text="'+$' + parseFloat(addon.price).toFixed(2)"></span>
                                </label>
                            </template>
                        </div>
                    </div>
                </template>

                <!-- Quantity Stepper -->
                <div class="flex items-center justify-between pt-2">
                    <span class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Quantity</span>
                    <div class="flex items-center space-x-3 bg-gray-100 dark:bg-gray-800 rounded-xl p-1">
                        <button type="button" @click="if (modalQuantity > 1) modalQuantity--" class="w-8 h-8 rounded-lg bg-white dark:bg-gray-700 text-gray-800 dark:text-white font-bold flex items-center justify-center">-</button>
                        <span class="w-8 text-center font-bold text-sm" x-text="modalQuantity"></span>
                        <button type="button" @click="modalQuantity++" class="w-8 h-8 rounded-lg bg-white dark:bg-gray-700 text-gray-800 dark:text-white font-bold flex items-center justify-center">+</button>
                    </div>
                </div>

                <button type="button" @click="addItemToCart()"
                        class="w-full py-3 bg-orange-600 hover:bg-orange-700 text-white font-bold text-sm rounded-xl shadow-md transition">
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
                                        <p class="text-[11px] text-gray-500 font-medium" x-text="'Size: ' + item.variant_name"></p>
                                    </template>
                                    <p class="text-orange-600 font-bold mt-0.5" x-text="'$' + (item.unit_price * item.quantity).toFixed(2)"></p>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span class="font-bold" x-text="'x' + item.quantity"></span>
                                    <button type="button" @click="cart.splice(index, 1); saveCart();" class="text-red-500 font-bold ml-2">✕</button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Checkout Form -->
                <form @submit.prevent="submitOrder" class="space-y-4 pt-4 border-t border-gray-200 dark:border-gray-800 text-xs">
                    <div>
                        <label class="block font-bold uppercase tracking-wider text-gray-600 dark:text-gray-400 mb-1">Dining Option</label>
                        <div class="grid grid-cols-3 gap-2">
                            <button type="button" @click="orderType = 'dine_in'" :class="orderType === 'dine_in' ? 'bg-orange-600 text-white font-bold' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300'" class="py-2 rounded-xl text-center">Dine-In</button>
                            <button type="button" @click="orderType = 'takeaway'" :class="orderType === 'takeaway' ? 'bg-orange-600 text-white font-bold' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300'" class="py-2 rounded-xl text-center">Takeaway</button>
                            <button type="button" @click="orderType = 'delivery'" :class="orderType === 'delivery' ? 'bg-orange-600 text-white font-bold' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300'" class="py-2 rounded-xl text-center">Delivery</button>
                        </div>
                    </div>

                    <div x-show="orderType === 'dine_in'">
                        <label class="block font-semibold mb-1">Table Number</label>
                        <input type="text" x-model="tableNumber" required class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-800 px-3 py-2 text-xs">
                    </div>

                    <div x-show="orderType === 'delivery'" style="display: none;">
                        <label class="block font-semibold mb-1">Delivery Address</label>
                        <input type="text" x-model="deliveryAddress" placeholder="Street, house number, landmarks..." class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-800 px-3 py-2 text-xs">
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block font-semibold mb-1">Your Name *</label>
                            <input type="text" x-model="customerName" required placeholder="Name" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-800 px-3 py-2 text-xs">
                        </div>
                        <div>
                            <label class="block font-semibold mb-1">Phone Number *</label>
                            <input type="text" x-model="customerPhone" required placeholder="012 345 678" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-800 px-3 py-2 text-xs">
                        </div>
                    </div>

                    <div>
                        <label class="block font-semibold mb-1">Payment Method</label>
                        <select x-model="paymentMethod" class="w-full rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-800 px-3 py-2 text-xs">
                            <option value="khqr">Bakong KHQR (Scan to Pay)</option>
                            <option value="cash">Cash at Counter</option>
                        </select>
                    </div>

                    <!-- Total Calculation -->
                    <div class="pt-3 border-t border-gray-100 dark:border-gray-800 space-y-1">
                        <div class="flex justify-between font-bold text-sm">
                            <span>Total (USD)</span>
                            <span class="text-orange-600" x-text="'$' + cartTotalAmount.toFixed(2)"></span>
                        </div>
                        <div class="flex justify-between text-[11px] text-gray-500">
                            <span>Khmer Riel Equivalent</span>
                            <span class="font-bold" x-text="Math.round(cartTotalAmount * {{ $business->exchange_rate_khr }}).toLocaleString() + ' ៛'"></span>
                        </div>
                    </div>

                    <button type="submit" :disabled="isSubmitting"
                            class="w-full py-3 bg-orange-600 hover:bg-orange-700 text-white font-bold rounded-xl shadow-md transition disabled:opacity-50">
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
