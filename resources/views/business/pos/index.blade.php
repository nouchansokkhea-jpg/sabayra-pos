<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-900">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $business->name }} - {{ __('Touch POS Terminal') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Kantumruy+Pro:wght@400;500;600;700&family=Battambang:wght@400;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="h-full overflow-hidden select-none bg-slate-900 text-slate-100 font-sans"
      x-data="posApp()"
      x-cloak>

    @if(!$activeSession)
        <!-- Screen: No Active Shift -> Open Register Shift Modal -->
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 backdrop-blur-md p-4">
            <div class="w-full max-w-md bg-slate-900 border border-slate-800 rounded-3xl p-6 sm:p-8 shadow-2xl text-center space-y-6">
                <div class="w-16 h-16 mx-auto rounded-2xl bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 flex items-center justify-center">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>

                <div>
                    <h2 class="text-2xl font-black text-white tracking-tight">{{ __('Open Register Shift') }}</h2>
                    <p class="text-sm text-slate-400 mt-1">
                        {{ __('Enter opening drawer cash to start serving customers.') }}
                    </p>
                </div>

                <form method="POST" action="{{ route('business.pos.open-session') }}" class="space-y-4 text-left">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">{{ __('Cashier') }}</label>
                        <div class="w-full bg-slate-800 border border-slate-700/80 rounded-xl px-4 py-2.5 text-sm font-semibold text-slate-200">
                            {{ auth()->user()->name }} ({{ auth()->user()->email }})
                        </div>
                    </div>

                    @if($branches->isNotEmpty())
                        <div>
                            <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">{{ __('Branch') }}</label>
                            <select name="branch_id" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-4 py-2.5 text-sm text-slate-200 focus:ring-emerald-500 focus:border-emerald-500">
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">{{ __('Opening Float Cash (USD $)') }} *</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 font-bold">$</span>
                            <input type="number" step="0.01" name="opening_cash" value="50.00" min="0" required
                                   class="w-full bg-slate-800 border border-slate-700 rounded-xl pl-8 pr-4 py-3 text-lg font-bold text-white focus:ring-emerald-500 focus:border-emerald-500">
                        </div>
                    </div>

                    <div class="pt-2 flex items-center gap-3">
                        <a href="{{ route('business.dashboard') }}" class="w-1/3 py-3 text-center bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold rounded-xl text-sm transition">
                            {{ __('Cancel') }}
                        </a>
                        <button type="submit" class="w-2/3 py-3 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-xl text-sm transition shadow-lg shadow-emerald-900/40">
                            {{ __('Start Shift') }} &rarr;
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Main Touch POS Application Grid -->
    <div class="h-screen flex flex-col overflow-hidden">
        <!-- Top Terminal Nav Bar -->
        <header class="h-14 bg-slate-950 border-b border-slate-800 px-4 flex items-center justify-between shrink-0">
            <div class="flex items-center space-x-4">
                <a href="{{ route('business.dashboard') }}" class="p-2 rounded-xl bg-slate-900 text-slate-400 hover:text-white hover:bg-slate-800 transition" title="{{ __('Back to Dashboard') }}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                </a>
                <div class="flex items-center space-x-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    <h1 class="font-extrabold text-base text-white tracking-tight flex items-center gap-2">
                        <span>{{ $business->name }}</span>
                        <span class="text-xs px-2 py-0.5 rounded bg-emerald-500/20 text-emerald-400 font-mono font-bold">POS</span>
                    </h1>
                </div>
            </div>

            <div class="flex items-center space-x-3 text-xs">
                @if($activeSession)
                    <div class="hidden sm:flex items-center space-x-2 text-slate-400 bg-slate-900 px-3 py-1.5 rounded-xl border border-slate-800">
                        <span>Cashier: <strong class="text-slate-200">{{ auth()->user()->name }}</strong></span>
                        <span>•</span>
                        <span>Shift Float: <strong class="text-emerald-400 font-mono">${{ number_format($activeSession->opening_cash, 2) }}</strong></span>
                    </div>

                    <button type="button" @click="showCloseSessionModal = true" class="px-3 py-1.5 bg-rose-600/20 text-rose-300 border border-rose-500/30 hover:bg-rose-600 hover:text-white rounded-xl font-bold transition">
                        {{ __('End Shift') }}
                    </button>
                @endif
            </div>
        </header>

        <!-- Main Workspace (Catalog on Left, Cart Register on Right) -->
        <div class="flex-1 flex overflow-hidden">
            <!-- Left / Catalog Area -->
            <div class="flex-1 flex flex-col bg-slate-900 overflow-hidden border-r border-slate-800">
                <!-- Search & Category Filters Bar -->
                <div class="p-3 bg-slate-950/60 border-b border-slate-800/80 space-y-2">
                    <div class="relative">
                        <svg class="w-4 h-4 absolute left-3.5 top-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input type="text" x-model="searchQuery" placeholder="{{ __('Search products by name, code or SKU...') }}"
                               class="w-full bg-slate-900 border border-slate-800 rounded-xl pl-10 pr-4 py-2 text-sm text-slate-200 placeholder-slate-500 focus:ring-emerald-500 focus:border-emerald-500">
                    </div>

                    <!-- Category Quick Select Pills -->
                    <div class="flex items-center space-x-2 overflow-x-auto no-scrollbar py-1">
                        <button type="button" @click="selectedCategory = 'all'"
                                :class="selectedCategory === 'all' ? 'bg-emerald-600 text-white shadow-md' : 'bg-slate-800/80 text-slate-400 hover:bg-slate-800 hover:text-slate-200'"
                                class="px-3.5 py-1.5 rounded-xl text-xs font-bold whitespace-nowrap transition">
                            All Items
                        </button>
                        @foreach($categories as $category)
                            <button type="button" @click="selectedCategory = '{{ $category->id }}'"
                                    :class="selectedCategory === '{{ $category->id }}' ? 'bg-emerald-600 text-white shadow-md' : 'bg-slate-800/80 text-slate-400 hover:bg-slate-800 hover:text-slate-200'"
                                    class="px-3.5 py-1.5 rounded-xl text-xs font-bold whitespace-nowrap transition">
                                {{ $category->localized_name }}
                            </button>
                        @endforeach
                    </div>
                </div>

                <!-- Product Grid (Touch Buttons) -->
                <div class="flex-1 overflow-y-auto p-4">
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-5 gap-3">
                        @foreach($categories as $category)
                            @foreach($category->products as $item)
                                <div x-show="(selectedCategory === 'all' || selectedCategory === '{{ $category->id }}') && (!searchQuery || '{{ strtolower($item->name_en) }}'.includes(searchQuery.toLowerCase()) || '{{ strtolower($item->name_km ?? '') }}'.includes(searchQuery.toLowerCase()))"
                                     @click="addItem({{ json_encode($item) }})"
                                     class="bg-slate-800/80 hover:bg-slate-800 active:scale-95 border border-slate-700/60 hover:border-emerald-500/50 rounded-2xl p-3 flex flex-col justify-between cursor-pointer transition shadow-sm group">
                                    <div>
                                        <div class="aspect-video w-full rounded-xl bg-slate-900 overflow-hidden mb-2 relative flex items-center justify-center">
                                            @if($item->primary_image_url)
                                                <img src="{{ $item->primary_image_url }}" alt="" class="w-full h-full object-cover">
                                            @else
                                                <span class="font-bold text-slate-600 text-xl">{{ strtoupper(substr($item->name_en, 0, 1)) }}</span>
                                            @endif
                                            @if($item->variants->isNotEmpty() || $item->addons->isNotEmpty())
                                                <span class="absolute top-1.5 right-1.5 bg-emerald-500/90 text-white text-[9px] font-bold px-1.5 py-0.5 rounded shadow">
                                                    Options
                                                </span>
                                            @endif
                                        </div>
                                        <h4 class="font-bold text-xs text-slate-200 line-clamp-2 leading-tight group-hover:text-white">
                                            {{ $item->name_en }}
                                        </h4>
                                        @if($item->name_km)
                                            <p class="text-[10px] text-slate-400 font-khmer mt-0.5 truncate">{{ $item->name_km }}</p>
                                        @endif
                                    </div>

                                    <div class="mt-2 pt-2 border-t border-slate-700/50 flex items-baseline justify-between">
                                        <span class="font-mono font-black text-sm text-emerald-400">
                                            ${{ number_format($item->effective_price, 2) }}
                                        </span>
                                        <span class="font-mono text-[10px] text-slate-400">
                                            {{ number_format($business->khrEquivalent($item->effective_price)) }}៛
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Right / Cart Register Area -->
            <div class="w-96 lg:w-[420px] bg-slate-950 flex flex-col shrink-0">
                <!-- Cart Header: Order Type & Table Selector -->
                <div class="p-3 border-b border-slate-800 space-y-2">
                    <div class="grid grid-cols-3 gap-1 bg-slate-900 p-1 rounded-xl">
                        <button type="button" @click="orderType = 'dine_in'"
                                :class="orderType === 'dine_in' ? 'bg-emerald-600 text-white font-bold' : 'text-slate-400 hover:text-white font-medium'"
                                class="py-1.5 rounded-lg text-xs transition">
                            🍽️ Dine-In
                        </button>
                        <button type="button" @click="orderType = 'takeaway'"
                                :class="orderType === 'takeaway' ? 'bg-emerald-600 text-white font-bold' : 'text-slate-400 hover:text-white font-medium'"
                                class="py-1.5 rounded-lg text-xs transition">
                            🛍️ Takeaway
                        </button>
                        <button type="button" @click="orderType = 'delivery'"
                                :class="orderType === 'delivery' ? 'bg-emerald-600 text-white font-bold' : 'text-slate-400 hover:text-white font-medium'"
                                class="py-1.5 rounded-lg text-xs transition">
                            🛵 Delivery
                        </button>
                    </div>

                    <div x-show="orderType === 'dine_in'" class="flex items-center gap-2">
                        <label class="text-xs text-slate-400 shrink-0">Table:</label>
                        <select x-model="selectedTable" class="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-1.5 text-xs text-slate-200 focus:ring-emerald-500">
                            <option value="">Select Table...</option>
                            @foreach($tables as $t)
                                <option value="{{ $t->table_number }}">Table #{{ $t->table_number }} ({{ $t->name ?? 'Standard' }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Cart Items List -->
                <div class="flex-1 overflow-y-auto p-3 divide-y divide-slate-800/80">
                    <template x-if="cart.length === 0">
                        <div class="h-full flex flex-col items-center justify-center text-center text-slate-500 py-12">
                            <svg class="w-12 h-12 text-slate-700 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                            <p class="text-sm font-semibold">Cart is empty</p>
                            <p class="text-xs text-slate-600 mt-1">Tap products to build order</p>
                        </div>
                    </template>

                    <template x-for="(item, index) in cart" :key="index">
                        <div class="py-2.5 flex items-start justify-between gap-3">
                            <div class="flex-1 min-w-0">
                                <h5 class="text-xs font-bold text-slate-200 truncate" x-text="item.name"></h5>
                                <template x-if="item.variant_name">
                                    <span class="text-[10px] text-slate-400" x-text="'(' + item.variant_name + ')'"></span>
                                </template>
                                <template x-if="item.addons && item.addons.length > 0">
                                    <div class="text-[10px] text-slate-500" x-text="item.addons.map(a => '+' + a.name).join(', ')"></div>
                                </template>
                                <div class="text-[11px] font-mono text-emerald-400 mt-0.5" x-text="'$' + Number(item.unit_price).toFixed(2)"></div>
                            </div>

                            <div class="flex items-center space-x-2 shrink-0">
                                <div class="flex items-center bg-slate-900 border border-slate-800 rounded-lg">
                                    <button type="button" @click="decreaseQty(index)" class="px-2 py-0.5 text-slate-400 hover:text-white font-bold text-xs">-</button>
                                    <span class="px-2 py-0.5 text-xs font-bold text-white font-mono" x-text="item.quantity"></span>
                                    <button type="button" @click="increaseQty(index)" class="px-2 py-0.5 text-slate-400 hover:text-white font-bold text-xs">+</button>
                                </div>
                                <div class="w-16 text-right font-mono font-bold text-xs text-white" x-text="'$' + (item.unit_price * item.quantity).toFixed(2)"></div>
                                <button type="button" @click="removeItem(index)" class="text-slate-600 hover:text-rose-400 p-1">
                                    &times;
                                </button>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Cart Summary & Settlement Footer -->
                <div class="p-3 bg-slate-900 border-t border-slate-800 space-y-2">
                    <div class="space-y-1 text-xs">
                        <div class="flex justify-between text-slate-400">
                            <span>Subtotal:</span>
                            <span class="font-mono text-slate-200" x-text="'$' + subtotal().toFixed(2)"></span>
                        </div>
                        <div class="flex justify-between text-slate-400">
                            <span>Tax ({{ $business->tax_percent }}%):</span>
                            <span class="font-mono text-slate-200" x-text="'$' + tax().toFixed(2)"></span>
                        </div>
                        <div class="flex justify-between text-base font-black text-white pt-1 border-t border-slate-800">
                            <span>TOTAL:</span>
                            <div class="text-right">
                                <div class="font-mono text-emerald-400 text-lg" x-text="'$' + total().toFixed(2)"></div>
                                <div class="text-xs font-mono text-slate-400" x-text="totalKhr() + ' ៛'"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Settlement Action Button -->
                    <button type="button"
                            :disabled="cart.length === 0 || isCheckingOut"
                            @click="openPaymentModal()"
                            class="w-full py-3.5 bg-emerald-600 hover:bg-emerald-500 disabled:opacity-40 disabled:pointer-events-none text-white font-extrabold rounded-2xl text-base transition shadow-lg shadow-emerald-950 flex items-center justify-center gap-2">
                        <span>{{ __('Charge') }}</span>
                        <span x-text="'$' + total().toFixed(2)"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Variant / Addon Customizer -->
    <div x-show="showCustomizerModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4" style="display: none;">
        <div class="w-full max-w-md bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-2xl space-y-4">
            <div class="flex justify-between items-center pb-3 border-b border-slate-800">
                <h3 class="font-bold text-base text-white" x-text="customizingItem?.name_en"></h3>
                <button type="button" @click="showCustomizerModal = false" class="text-slate-400 hover:text-white">&times;</button>
            </div>

            <!-- Variants (Radio) -->
            <template x-if="customizingItem?.variants && customizingItem.variants.length > 0">
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Select Variant</label>
                    <div class="grid grid-cols-2 gap-2">
                        <template x-for="v in customizingItem.variants" :key="v.id">
                            <button type="button" @click="selectedVariant = v"
                                    :class="selectedVariant?.id === v.id ? 'bg-emerald-600 text-white border-emerald-500' : 'bg-slate-800 text-slate-300 border-slate-700'"
                                    class="p-2.5 rounded-xl border text-left text-xs font-bold transition">
                                <div x-text="v.name_en"></div>
                                <div class="text-[10px] opacity-80" x-text="'+$' + Number(v.price_adjustment).toFixed(2)"></div>
                            </button>
                        </template>
                    </div>
                </div>
            </template>

            <!-- Add-ons (Checkbox) -->
            <template x-if="customizingItem?.addons && customizingItem.addons.length > 0">
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Add-ons</label>
                    <div class="space-y-1.5 max-h-40 overflow-y-auto">
                        <template x-for="a in customizingItem.addons" :key="a.id">
                            <label class="flex items-center justify-between p-2 rounded-xl bg-slate-800 border border-slate-700/60 cursor-pointer text-xs">
                                <span class="text-slate-200" x-text="a.name_en"></span>
                                <div class="flex items-center gap-2">
                                    <span class="font-mono text-emerald-400" x-text="'+$' + Number(a.price).toFixed(2)"></span>
                                    <input type="checkbox" :value="a.id" @change="toggleAddon(a)" class="rounded bg-slate-900 border-slate-700 text-emerald-600 focus:ring-emerald-500">
                                </div>
                            </label>
                        </template>
                    </div>
                </div>
            </template>

            <button type="button" @click="confirmCustomization()" class="w-full py-3 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-xl text-sm transition">
                Add to Cart
            </button>
        </div>
    </div>

    <!-- Modal: Multi-Payment Settlement & Change Calculator -->
    <div x-show="showPaymentModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4" style="display: none;">
        <div class="w-full max-w-lg bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-2xl space-y-4">
            <div class="flex justify-between items-center pb-3 border-b border-slate-800">
                <h3 class="font-black text-lg text-white">Select Payment & Settle</h3>
                <button type="button" @click="showPaymentModal = false" class="text-slate-400 hover:text-white">&times;</button>
            </div>

            <!-- Amount Due Banner -->
            <div class="p-4 rounded-2xl bg-slate-950 border border-slate-800 flex justify-between items-center">
                <span class="text-xs font-bold text-slate-400 uppercase">Total Due:</span>
                <div class="text-right">
                    <span class="font-mono font-black text-2xl text-emerald-400" x-text="'$' + total().toFixed(2)"></span>
                    <div class="text-xs font-mono text-slate-400" x-text="totalKhr() + ' ៛'"></div>
                </div>
            </div>

            <!-- Payment Methods Tabs -->
            <div class="grid grid-cols-3 gap-2">
                <button type="button" @click="paymentMethod = 'cash'"
                        :class="paymentMethod === 'cash' ? 'bg-emerald-600 text-white' : 'bg-slate-800 text-slate-400 hover:text-white'"
                        class="py-2.5 rounded-xl text-xs font-bold transition">
                    💵 Cash
                </button>
                <button type="button" @click="paymentMethod = 'khqr'"
                        :class="paymentMethod === 'khqr' ? 'bg-red-600 text-white' : 'bg-slate-800 text-slate-400 hover:text-white'"
                        class="py-2.5 rounded-xl text-xs font-bold transition">
                    🇰🇭 Bakong KHQR
                </button>
                <button type="button" @click="paymentMethod = 'card'"
                        :class="paymentMethod === 'card' ? 'bg-blue-600 text-white' : 'bg-slate-800 text-slate-400 hover:text-white'"
                        class="py-2.5 rounded-xl text-xs font-bold transition">
                    💳 Card
                </button>
            </div>

            <!-- Cash Tendered & Change Helper -->
            <div x-show="paymentMethod === 'cash'" class="space-y-3">
                <div class="grid grid-cols-4 gap-2">
                    <button type="button" @click="setTendered(total())" class="py-2 bg-slate-800 hover:bg-slate-700 text-xs font-bold text-slate-200 rounded-xl">Exact Cash</button>
                    <button type="button" @click="setTendered(10)" class="py-2 bg-slate-800 hover:bg-slate-700 text-xs font-bold text-slate-200 rounded-xl">$10</button>
                    <button type="button" @click="setTendered(20)" class="py-2 bg-slate-800 hover:bg-slate-700 text-xs font-bold text-slate-200 rounded-xl">$20</button>
                    <button type="button" @click="setTendered(50)" class="py-2 bg-slate-800 hover:bg-slate-700 text-xs font-bold text-slate-200 rounded-xl">$50</button>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Cash Tendered ($)</label>
                    <input type="number" step="0.01" x-model.number="amountTendered" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2 text-lg font-mono font-bold text-white focus:ring-emerald-500">
                </div>

                <!-- Change Display -->
                <div class="p-3 bg-slate-950 rounded-xl border border-slate-800 flex justify-between items-center">
                    <span class="text-xs font-bold text-slate-400 uppercase">Change Due:</span>
                    <div class="text-right">
                        <span class="font-mono font-bold text-lg text-amber-400" x-text="'$' + Math.max(0, amountTendered - total()).toFixed(2)"></span>
                        <div class="text-xs font-mono text-slate-400" x-text="formatKhr(Math.max(0, amountTendered - total()) * {{ $business->exchange_rate_khr }}) + ' ៛'"></div>
                    </div>
                </div>
            </div>

            <!-- Submit Final Charge -->
            <button type="button" @click="submitOrder()" :disabled="isCheckingOut || (paymentMethod === 'cash' && amountTendered < total())"
                    class="w-full py-3.5 bg-emerald-600 hover:bg-emerald-500 disabled:opacity-40 text-white font-extrabold rounded-2xl text-base transition shadow-lg">
                <span x-show="!isCheckingOut">Complete Sale & Print Receipt</span>
                <span x-show="isCheckingOut">Processing...</span>
            </button>
        </div>
    </div>

    <!-- Modal: Close Register Shift -->
    @if($activeSession)
        <div x-show="showCloseSessionModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4" style="display: none;">
            <div class="w-full max-w-md bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-2xl space-y-4">
                <div class="flex justify-between items-center pb-3 border-b border-slate-800">
                    <h3 class="font-bold text-base text-white">{{ __('Close POS Register Shift') }}</h3>
                    <button type="button" @click="showCloseSessionModal = false" class="text-slate-400 hover:text-white">&times;</button>
                </div>

                <div class="space-y-2 text-xs text-slate-400">
                    <div class="flex justify-between">
                        <span>Opening Cash:</span>
                        <span class="font-mono font-bold text-slate-200">${{ number_format($activeSession->opening_cash, 2) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span>Total Cash Transactions:</span>
                        <span class="font-mono font-bold text-emerald-400">
                            ${{ number_format($activeSession->transactions()->where('payment_method', 'cash')->sum('amount'), 2) }}
                        </span>
                    </div>
                </div>

                <form method="POST" action="{{ route('business.pos.close-session', $activeSession) }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase mb-1">{{ __('Actual Cash in Drawer ($)') }} *</label>
                        <input type="number" step="0.01" name="closing_cash" required min="0" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-base font-mono font-bold text-white focus:ring-emerald-500">
                    </div>

                    <div class="flex items-center gap-3">
                        <button type="button" @click="showCloseSessionModal = false" class="w-1/3 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold rounded-xl text-xs transition">
                            {{ __('Cancel') }}
                        </button>
                        <button type="submit" class="w-2/3 py-2.5 bg-rose-600 hover:bg-rose-500 text-white font-bold rounded-xl text-xs transition">
                            {{ __('Reconcile & Close Shift') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <script>
        function posApp() {
            return {
                searchQuery: '',
                selectedCategory: 'all',
                orderType: 'dine_in',
                selectedTable: '',
                cart: [],
                showCustomizerModal: false,
                customizingItem: null,
                selectedVariant: null,
                selectedAddons: [],
                showPaymentModal: false,
                showCloseSessionModal: false,
                paymentMethod: 'cash',
                amountTendered: 0.00,
                isCheckingOut: false,

                addItem(product) {
                    if ((product.variants && product.variants.length > 0) || (product.addons && product.addons.length > 0)) {
                        this.customizingItem = product;
                        this.selectedVariant = product.variants ? product.variants[0] : null;
                        this.selectedAddons = [];
                        this.showCustomizerModal = true;
                    } else {
                        this.addToCartDirect(product, null, []);
                    }
                },

                toggleAddon(addon) {
                    const idx = this.selectedAddons.findIndex(a => a.id === addon.id);
                    if (idx > -1) {
                        this.selectedAddons.splice(idx, 1);
                    } else {
                        this.selectedAddons.push(addon);
                    }
                },

                confirmCustomization() {
                    this.addToCartDirect(this.customizingItem, this.selectedVariant, this.selectedAddons);
                    this.showCustomizerModal = false;
                },

                addToCartDirect(product, variant, addons) {
                    let unitPrice = Number(product.base_price);
                    if (variant) unitPrice += Number(variant.price_adjustment);
                    let addonCost = 0;
                    if (addons && addons.length > 0) {
                        addons.forEach(a => addonCost += Number(a.price));
                    }
                    unitPrice += addonCost;

                    const existingIndex = this.cart.findIndex(i =>
                        i.product_id === product.id &&
                        i.variant_id === (variant ? variant.id : null) &&
                        JSON.stringify(i.addons?.map(a => a.id).sort()) === JSON.stringify(addons?.map(a => a.id).sort())
                    );

                    if (existingIndex > -1) {
                        this.cart[existingIndex].quantity += 1;
                    } else {
                        this.cart.push({
                            product_id: product.id,
                            name: product.name_en,
                            variant_id: variant ? variant.id : null,
                            variant_name: variant ? variant.name_en : null,
                            addons: addons ? [...addons] : [],
                            unit_price: unitPrice,
                            quantity: 1,
                        });
                    }
                },

                increaseQty(idx) {
                    this.cart[idx].quantity += 1;
                },

                decreaseQty(idx) {
                    if (this.cart[idx].quantity > 1) {
                        this.cart[idx].quantity -= 1;
                    } else {
                        this.cart.splice(idx, 1);
                    }
                },

                removeItem(idx) {
                    this.cart.splice(idx, 1);
                },

                subtotal() {
                    return this.cart.reduce((sum, item) => sum + (item.unit_price * item.quantity), 0);
                },

                tax() {
                    return Number((this.subtotal() * ({{ $business->tax_percent }} / 100)).toFixed(2));
                },

                total() {
                    return Number((this.subtotal() + this.tax()).toFixed(2));
                },

                totalKhr() {
                    const khr = Math.round((this.total() * {{ $business->exchange_rate_khr }}) / 100) * 100;
                    return new Intl.NumberFormat().format(khr);
                },

                formatKhr(val) {
                    const rounded = Math.round(val / 100) * 100;
                    return new Intl.NumberFormat().format(rounded);
                },

                openPaymentModal() {
                    this.amountTendered = this.total();
                    this.showPaymentModal = true;
                },

                setTendered(val) {
                    this.amountTendered = Number(val);
                },

                async submitOrder() {
                    this.isCheckingOut = true;
                    try {
                        const payload = {
                            order_type: this.orderType,
                            table_number: this.orderType === 'dine_in' ? this.selectedTable : null,
                            payment_method: this.paymentMethod,
                            amount_tendered: this.amountTendered,
                            items: this.cart.map(i => ({
                                product_id: i.product_id,
                                variant_id: i.variant_id,
                                addons: i.addons ? i.addons.map(a => a.id) : [],
                                quantity: i.quantity,
                            })),
                        };

                        const response = await fetch("{{ route('business.pos.checkout') }}", {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            },
                            body: JSON.stringify(payload)
                        });

                        const data = await response.json();
                        if (response.ok && data.success) {
                            this.cart = [];
                            this.showPaymentModal = false;
                            // Open receipt in new tab/window for 80mm printing
                            window.open(data.receipt_url + '?print=true', '_blank', 'width=350,height=600');
                            alert('Order settled successfully! Receipt printed.');
                        } else {
                            alert(data.error || 'Failed to process POS checkout.');
                        }
                    } catch (e) {
                        alert('Connection error. Please try again.');
                    } finally {
                        this.isCheckingOut = false;
                    }
                }
            };
        }
    </script>
</body>
</html>
