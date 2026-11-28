@php
    $moduleLabels = [
        'pos' => ['label' => 'Touch POS Terminal', 'desc' => 'Fast cashier register, barcode & receipt printing'],
        'inventory' => ['label' => 'Stock & Inventory Control', 'desc' => 'Track stock quantities, low-stock alerts & logs'],
        'barcode' => ['label' => 'Barcode & SKU Scanning', 'desc' => 'Instant scanner lookup in POS & product catalog'],
        'suppliers' => ['label' => 'Suppliers Directory', 'desc' => 'Vendor contacts, lead times & cost records'],
        'purchase_orders' => ['label' => 'Purchase Orders & Receiving', 'desc' => 'Restock workflows & stock receiving'],
        'delivery_zones' => ['label' => 'Delivery Zones & Fees', 'desc' => 'Custom Khmer district/Sangkat delivery pricing'],
        'service_bookings' => ['label' => 'Service Appointment Bookings', 'desc' => 'Online customer time-slot & staff scheduling'],
        'reservations' => ['label' => 'Table Reservations & Waitlist', 'desc' => 'Floor seating bookings and live queue waitlist'],
        'happy_hours' => ['label' => 'Happy Hours & Time Promos', 'desc' => 'Scheduled time-window promotional pricing'],
        'tables' => ['label' => 'Floor & Table QR Map', 'desc' => 'Dine-in tables and scan-to-order QR codes'],
        'kitchen' => ['label' => 'Kitchen / Prep Display (KDS)', 'desc' => 'Real-time preparation queue board'],
        'recipes' => ['label' => 'Recipes & Ingredient BOM', 'desc' => 'Auto-deduct ingredients when items sell'],
    ];

    $catalogJs = \Illuminate\Support\Js::from($categoryCatalog);
    $currentKey = $categoryMeta['key'] ?? ($business->business_type ?? 'restaurant');
@endphp

<div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700/70 rounded-2xl p-6 shadow-xs"
     x-data="{
        catalog: {{ $catalogJs }},
        currentCategory: '{{ $currentKey }}',
        selectedCategory: '{{ old('business_type', $currentKey) }}',
        get currentCfg() { return this.catalog[this.currentCategory] || {}; },
        get selectedCfg() { return this.catalog[this.selectedCategory] || {}; },
        get gainedModules() {
            const cur = this.currentCfg.active_modules || [];
            const next = this.selectedCfg.active_modules || [];
            return next.filter(m => !cur.includes(m));
        },
        get hiddenModules() {
            const cur = this.currentCfg.active_modules || [];
            const next = this.selectedCfg.active_modules || [];
            return cur.filter(m => !next.includes(m));
        }
     }">
    <div class="pb-4 mb-6 border-b border-gray-100 dark:border-gray-700 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h3 class="text-base font-black text-slate-900 dark:text-white flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-orange-500"></span>
                <span>Industry Experience &amp; Module Configuration</span>
            </h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Tailor your dashboard KPIs, sidebar workflows, POS interface, and customer storefront to your business category — zero data is ever deleted on switch.
            </p>
        </div>
        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-extrabold bg-orange-50 dark:bg-orange-950/50 text-orange-600 dark:text-orange-300 border border-orange-200 dark:border-orange-800/60">
            <span>{{ $categoryMeta['name_en'] }}</span>
            <span class="text-orange-400">·</span>
            <span>{{ $categoryMeta['name_km'] }}</span>
        </span>
    </div>

    <!-- Active Experience Overview Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-7">
        <div class="p-4 rounded-xl bg-slate-50 dark:bg-gray-900/50 border border-slate-200 dark:border-gray-700">
            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">POS Experience</p>
            <p class="text-sm font-black text-slate-900 dark:text-white mt-1 capitalize">
                {{ str_replace('_', ' ', (string) ($categoryConfig['pos']['mode'] ?? 'standard')) }}
            </p>
            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                Default order: {{ str_replace('_', ' ', (string) ($categoryConfig['pos']['default_order_type'] ?? 'takeaway')) }}
            </p>
        </div>
        <div class="p-4 rounded-xl bg-slate-50 dark:bg-gray-900/50 border border-slate-200 dark:border-gray-700">
            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Public Storefront</p>
            <p class="text-sm font-black text-slate-900 dark:text-white mt-1 capitalize">
                {{ str_replace('_', ' ', (string) ($categoryConfig['storefront']['mode'] ?? 'catalog')) }} Mode
            </p>
            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                {{ $categoryConfig['storefront']['cta_en'] ?? 'Order Now' }}
            </p>
        </div>
        <div class="p-4 rounded-xl bg-slate-50 dark:bg-gray-900/50 border border-slate-200 dark:border-gray-700">
            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Report &amp; AI Context</p>
            <p class="text-sm font-black text-slate-900 dark:text-white mt-1 capitalize">
                {{ str_replace('_', ' ', (string) ($categoryConfig['reports']['primary_focus'] ?? 'sales_overview')) }}
            </p>
            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                Terminology: {{ $categoryConfig['terminology']['item_plural'] ?? 'Products' }}
            </p>
        </div>
    </div>

    <form method="POST" action="{{ route('business.settings.experience.update') }}" class="space-y-6">
        @csrf
        @method('PUT')

        <!-- Category Switcher Grid -->
        <div>
            <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-3">
                Select Business Category / ជ្រើសរើសប្រភេទអាជីវកម្ម
            </label>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2.5">
                @foreach ($categoryCatalog as $key => $cat)
                    <button type="button"
                            @click="selectedCategory = '{{ $key }}'"
                            :class="selectedCategory === '{{ $key }}'
                                ? 'border-orange-500 bg-orange-50/70 dark:bg-orange-950/30 ring-2 ring-orange-500/30'
                                : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900/40 hover:border-orange-300'"
                            class="text-left p-3 rounded-xl border transition flex items-start gap-2.5">
                        <span class="mt-0.5 w-2.5 h-2.5 rounded-full shrink-0" style="background-color: {{ $cat['primary_color'] }}"></span>
                        <div class="min-w-0">
                            <p class="text-xs font-extrabold text-slate-900 dark:text-white truncate">{{ $cat['name_en'] }}</p>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 truncate">{{ $cat['name_km'] }}</p>
                        </div>
                    </button>
                @endforeach
            </div>
            <input type="hidden" name="business_type" :value="selectedCategory">
            @error('business_type') <p class="text-xs text-rose-500 mt-1">{{ $message }}</p> @enderror
        </div>

        <!-- Guided Diff Preview when Category Changes -->
        <div x-show="selectedCategory !== currentCategory" x-cloak
             class="p-4 rounded-xl bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800/70 space-y-3">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <p class="text-xs font-black text-amber-900 dark:text-amber-200">
                        Previewing Category Switch:
                        <span x-text="currentCfg.name_en"></span> &rarr; <span x-text="selectedCfg.name_en"></span>
                    </p>
                    <p class="text-[11px] text-amber-800 dark:text-amber-300 mt-0.5">
                        Safe Data Guarantee: Existing products, orders, customers, invoices, and financial history remain untouched.
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                <div class="p-2.5 rounded-lg bg-white/80 dark:bg-gray-900/60 border border-emerald-200 dark:border-emerald-900/50">
                    <span class="font-bold text-emerald-700 dark:text-emerald-400 block mb-1">Modules Activated</span>
                    <template x-if="gainedModules.length === 0">
                        <span class="text-[11px] text-slate-500">No new modules added.</span>
                    </template>
                    <div class="flex flex-wrap gap-1">
                        <template x-for="mod in gainedModules" :key="mod">
                            <span class="px-2 py-0.5 rounded bg-emerald-100 dark:bg-emerald-900/50 text-emerald-800 dark:text-emerald-200 text-[10px] font-bold" x-text="'+ ' + mod.replace('_', ' ')"></span>
                        </template>
                    </div>
                </div>
                <div class="p-2.5 rounded-lg bg-white/80 dark:bg-gray-900/60 border border-slate-200 dark:border-gray-700">
                    <span class="font-bold text-slate-700 dark:text-slate-300 block mb-1">Modules Hidden (Data Kept)</span>
                    <template x-if="hiddenModules.length === 0">
                        <span class="text-[11px] text-slate-500">All current modules stay active.</span>
                    </template>
                    <div class="flex flex-wrap gap-1">
                        <template x-for="mod in hiddenModules" :key="mod">
                            <span class="px-2 py-0.5 rounded bg-slate-100 dark:bg-gray-800 text-slate-600 dark:text-slate-300 text-[10px] font-bold" x-text="'- ' + mod.replace('_', ' ')"></span>
                        </template>
                    </div>
                </div>
            </div>

            <label class="flex items-center gap-2 text-xs font-semibold text-amber-900 dark:text-amber-200 cursor-pointer">
                <input type="checkbox" name="apply_category_theme" value="1" class="rounded border-amber-400 text-orange-600 focus:ring-orange-500">
                <span>Apply recommended storefront accent color &amp; layout preset for <span class="font-bold" x-text="selectedCfg.name_en"></span></span>
            </label>
        </div>

        <!-- Optional Module Customization Overrides -->
        <div class="pt-5 border-t border-gray-100 dark:border-gray-700 space-y-3">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                <div>
                    <h4 class="text-xs font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider">
                        Custom Module Overrides
                    </h4>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">
                        Enable or turn off optional modules for hybrid workflows (especially flexible for Custom / Other businesses).
                    </p>
                </div>
                <label class="inline-flex items-center gap-2 text-xs font-semibold text-slate-600 dark:text-slate-300 cursor-pointer">
                    <input type="checkbox" name="reset_module_overrides" value="1" class="rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                    <span>Reset to category defaults</span>
                </label>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                @foreach ($configurableModules as $moduleKey)
                    @php
                        $metaInfo = $moduleLabels[$moduleKey] ?? ['label' => ucfirst(str_replace('_', ' ', $moduleKey)), 'desc' => ''];
                        $isChecked = array_key_exists($moduleKey, $customModuleOverrides)
                            ? (bool) $customModuleOverrides[$moduleKey]
                            : (bool) ($categoryConfig['features'][$moduleKey] ?? false);
                    @endphp
                    <label class="flex items-start gap-2.5 p-3 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50/60 dark:bg-gray-900/40 cursor-pointer hover:border-orange-300 transition">
                        <input type="checkbox" name="custom_modules[{{ $moduleKey }}]" value="1"
                               {{ $isChecked ? 'checked' : '' }}
                               class="mt-0.5 rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                        <div class="min-w-0">
                            <span class="block text-xs font-bold text-slate-900 dark:text-white">{{ $metaInfo['label'] }}</span>
                            @if($metaInfo['desc'])
                                <span class="block text-[11px] text-slate-500 dark:text-slate-400 leading-snug">{{ $metaInfo['desc'] }}</span>
                            @endif
                        </div>
                    </label>
                @endforeach
            </div>
        </div>

        <div class="flex items-center justify-end pt-5 border-t border-gray-100 dark:border-gray-700">
            <button type="submit" class="px-6 py-2.5 bg-orange-600 hover:bg-orange-700 text-white font-bold text-xs rounded-xl shadow-xs transition active:scale-95">
                Save Category &amp; Modules
            </button>
        </div>
    </form>
</div>
