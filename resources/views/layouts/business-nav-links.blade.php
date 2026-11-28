@php
    $biz = $currentBusiness ?? \App\Services\Tenant\TenantContext::get();
    $expService = app(\App\Services\Business\CategoryExperienceService::class);
    $can = fn (string $feature): bool => $expService->isModuleEnabled($biz, $feature);
    $term = $expService->terminology($biz);
    $catMeta = $expService->meta($biz);
    $isBookingBiz = $can('service_bookings');
@endphp

<ul role="list" class="flex flex-col flex-1 gap-y-1 pb-10 text-xs">
    @if (Auth::user()?->isSuperAdmin())
        <li class="pb-2">
            <a href="{{ route('admin.dashboard') }}"
               class="flex items-center justify-between rounded-xl p-2.5 text-xs font-black uppercase tracking-wider bg-gradient-to-r from-purple-900/90 to-indigo-900/90 text-purple-200 border border-purple-500/40 hover:from-purple-800 hover:to-indigo-800 transition shadow-md group">
                <span class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-purple-400 animate-pulse"></span>
                    <span>🛡️ Super Admin</span>
                </span>
                <span class="text-xs group-hover:translate-x-0.5 transition">&rarr;</span>
            </a>
        </li>
    @endif

    {{-- ============================================================== --}}
    {{-- 1. OVERVIEW --}}
    {{-- ============================================================== --}}
    <li>
        <a href="{{ route('business.dashboard') }}"
           class="group flex items-center gap-x-3 rounded-xl p-2.5 font-semibold transition {{ request()->routeIs('business.dashboard') ? 'bg-orange-600 text-white shadow-sm font-bold' : 'text-slate-300 hover:bg-gray-800 hover:text-white' }}">
            <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('business.dashboard') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            <span>Business Overview</span>
        </a>
    </li>

    {{-- ============================================================== --}}
    {{-- 2. CATALOG / SERVICES / MENU --}}
    {{-- ============================================================== --}}
    <li class="pt-3">
        <p class="px-2.5 text-[10px] font-black text-slate-300 dark:text-slate-200 uppercase tracking-wider">{{ $term['catalog'] ?? 'Catalog & Menu' }}</p>
    </li>

    <li>
        <a href="{{ route('business.products.index') }}"
           class="group flex items-center gap-x-3 rounded-xl p-2 font-semibold transition {{ request()->routeIs('business.products.index', 'business.products.create', 'business.products.edit', 'business.products.show') ? 'bg-orange-600 text-white shadow-sm font-bold' : 'text-slate-300 hover:bg-gray-800 hover:text-white' }}">
            <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('business.products.*') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
            </svg>
            <span>{{ $term['catalog'] ?? 'Products & Catalog' }}</span>
        </a>
    </li>

    @if ($can('bulk_import'))
        <li>
            <a href="{{ route('business.products.import') }}"
               class="group flex items-center gap-x-3 rounded-xl p-2 font-semibold transition {{ request()->routeIs('business.products.import*') ? 'bg-orange-600 text-white shadow-sm font-bold' : 'text-slate-300 hover:bg-gray-800 hover:text-white' }}">
                <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('business.products.import*') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                </svg>
                <span>Bulk CSV / Barcode Import</span>
            </a>
        </li>
    @endif

    <li>
        <a href="{{ route('business.categories.index') }}"
           class="group flex items-center gap-x-3 rounded-xl p-2 font-semibold transition {{ request()->routeIs('business.categories.*') ? 'bg-orange-600 text-white shadow-sm font-bold' : 'text-slate-300 hover:bg-gray-800 hover:text-white' }}">
            <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('business.categories.*') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
            </svg>
            <span>Categories</span>
        </a>
    </li>

    {{-- ============================================================== --}}
    {{-- 3. OPERATIONS --}}
    {{-- ============================================================== --}}
    <li class="pt-3">
        <p class="px-2.5 text-[10px] font-black text-slate-300 dark:text-slate-200 uppercase tracking-wider">{{ $term['operations'] ?? 'Operations' }}</p>
    </li>

    <li>
        <a href="{{ route('business.orders.index') }}"
           class="group flex items-center gap-x-3 rounded-xl p-2 font-semibold transition {{ request()->routeIs('business.orders.index') || request()->routeIs('business.orders.show') ? 'bg-orange-600 text-white shadow-sm font-bold' : 'text-slate-300 hover:bg-gray-800 hover:text-white' }}">
            <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('business.orders.index') || request()->routeIs('business.orders.show') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
            </svg>
            <span>{{ $term['orders'] ?? 'Orders Management' }}</span>
        </a>
    </li>

    <li>
        <a href="{{ route('business.orders.live') }}"
           class="group flex items-center justify-between rounded-xl p-2 font-semibold transition {{ request()->routeIs('business.orders.live') ? 'bg-orange-600 text-white shadow-sm font-bold' : 'text-slate-300 hover:bg-gray-800 hover:text-white' }}">
            <div class="flex items-center gap-x-3">
                <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('business.orders.live') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                <span>Live Orders</span>
            </div>
            <span class="text-[9px] bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 px-1.5 py-0.5 rounded font-mono font-bold animate-pulse">FEED</span>
        </a>
    </li>

    @if ($isBookingBiz || $can('reservations'))
        <li>
            <a href="{{ route('business.reservations.index') }}"
               class="group flex items-center gap-x-3 rounded-xl p-2 font-semibold transition {{ request()->routeIs('business.reservations.*') ? 'bg-orange-600 text-white shadow-sm font-bold' : 'text-slate-300 hover:bg-gray-800 hover:text-white' }}">
                <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('business.reservations.*') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <span>{{ $isBookingBiz ? 'Service Appointments' : 'Table Reservations' }}</span>
            </a>
        </li>
    @endif

    @if ($can('waitlist'))
        <li>
            <a href="{{ route('business.waitlist.index') }}"
               class="group flex items-center gap-x-3 rounded-xl p-2 font-semibold transition {{ request()->routeIs('business.waitlist.*') ? 'bg-orange-600 text-white shadow-sm font-bold' : 'text-slate-300 hover:bg-gray-800 hover:text-white' }}">
                <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('business.waitlist.*') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>Queue Waitlist</span>
            </a>
        </li>
    @endif

    @if ($can('pos'))
        <li>
            <a href="{{ route('business.pos.index') }}"
               class="group flex items-center gap-x-3 rounded-xl p-2 font-semibold transition {{ request()->routeIs('business.pos.*') ? 'bg-orange-600 text-white shadow-sm font-bold' : 'text-slate-300 hover:bg-gray-800 hover:text-white' }}">
                <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('business.pos.*') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                </svg>
                <span>Touch POS</span>
            </a>
        </li>
    @endif

    @if ($can('kitchen'))
        <li>
            <a href="{{ route('business.kitchen.index') }}" target="_blank"
               class="group flex items-center justify-between rounded-xl p-2 font-semibold transition {{ request()->routeIs('business.kitchen.*') ? 'bg-orange-600 text-white shadow-sm font-bold' : 'text-slate-300 hover:bg-gray-800 hover:text-white' }}">
                <div class="flex items-center gap-x-3">
                    <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('business.kitchen.*') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    <span>Kitchen (KDS)</span>
                </div>
                <span class="text-[9px] bg-red-500/20 text-red-400 border border-red-500/30 px-1.5 py-0.2 rounded font-mono font-bold animate-pulse">LIVE</span>
            </a>
        </li>
    @endif

    @if ($can('tables'))
        <li>
            <a href="{{ route('business.tables.index') }}"
               class="group flex items-center gap-x-3 rounded-xl p-2 font-semibold transition {{ request()->routeIs('business.tables.*') ? 'bg-orange-600 text-white shadow-sm font-bold' : 'text-slate-300 hover:bg-gray-800 hover:text-white' }}">
                <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('business.tables.*') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                </svg>
                <span>Tables & QR Standees</span>
            </a>
        </li>
    @endif

    @if ($can('printers'))
        <li>
            <a href="{{ route('business.printers.index') }}"
               class="group flex items-center gap-x-3 rounded-xl p-2 font-semibold transition {{ request()->routeIs('business.printers.*') ? 'bg-orange-600 text-white shadow-sm font-bold' : 'text-slate-300 hover:bg-gray-800 hover:text-white' }}">
                <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('business.printers.*') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                <span>Printers & Hardware</span>
            </a>
        </li>
    @endif

    {{-- ============================================================== --}}
    {{-- 4. MANAGEMENT & INVENTORY --}}
    {{-- ============================================================== --}}
    <li class="pt-3">
        <p class="px-2.5 text-[10px] font-black text-slate-300 dark:text-slate-200 uppercase tracking-wider">Management</p>
    </li>

    <li>
        <a href="{{ route('business.staff.index') }}"
           class="group flex items-center gap-x-3 rounded-xl p-2 font-semibold transition {{ request()->routeIs('business.staff.*') ? 'bg-orange-600 text-white shadow-sm font-bold' : 'text-slate-300 hover:bg-gray-800 hover:text-white' }}">
            <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('business.staff.*') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            <span>Staff & Permissions</span>
        </a>
    </li>

    @if ($can('inventory'))
        <li>
            <a href="{{ route('business.inventory.index') }}"
               class="group flex items-center gap-x-3 rounded-xl p-2 font-semibold transition {{ request()->routeIs('business.inventory.*') ? 'bg-orange-600 text-white shadow-sm font-bold' : 'text-slate-300 hover:bg-gray-800 hover:text-white' }}">
                <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('business.inventory.*') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
                <span>{{ $can('recipes') ? 'Inventory & Ingredients' : 'Inventory & Stock' }}</span>
            </a>
        </li>
    @endif

    @if ($can('recipes'))
        <li>
            <a href="{{ route('business.recipes.index') }}"
               class="group flex items-center gap-x-3 rounded-xl p-2 font-semibold transition {{ request()->routeIs('business.recipes.*') ? 'bg-orange-600 text-white shadow-sm font-bold' : 'text-slate-300 hover:bg-gray-800 hover:text-white' }}">
                <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('business.recipes.*') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2"/>
                </svg>
                <span>Recipes &amp; BOM</span>
            </a>
        </li>
    @endif

    @if ($can('suppliers') || $can('purchase_orders'))
        <li>
            <a href="{{ route('business.suppliers.index') }}"
               class="group flex items-center gap-x-3 rounded-xl p-2 font-semibold transition {{ request()->routeIs('business.suppliers.*') ? 'bg-orange-600 text-white shadow-sm font-bold' : 'text-slate-300 hover:bg-gray-800 hover:text-white' }}">
                <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('business.suppliers.*') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                </svg>
                <span>Suppliers &amp; PO</span>
            </a>
        </li>
    @endif

    <li>
        <a href="{{ route('business.branches.index') }}"
           class="group flex items-center gap-x-3 rounded-xl p-2 font-semibold transition {{ request()->routeIs('business.branches.*') ? 'bg-orange-600 text-white shadow-sm font-bold' : 'text-slate-300 hover:bg-gray-800 hover:text-white' }}">
            <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('business.branches.*') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
            </svg>
            <span>Branch Outlets</span>
        </a>
    </li>

    @if ($can('delivery_zones'))
        <li>
            <a href="{{ route('business.delivery-zones.index') }}"
               class="group flex items-center gap-x-3 rounded-xl p-2 font-semibold transition {{ request()->routeIs('business.delivery-zones.*') ? 'bg-orange-600 text-white shadow-sm font-bold' : 'text-slate-300 hover:bg-gray-800 hover:text-white' }}">
                <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('business.delivery-zones.*') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                </svg>
                <span>Delivery Zones</span>
            </a>
        </li>
    @endif

    {{-- ============================================================== --}}
    {{-- 4B. CUSTOMERS & MARKETING --}}
    {{-- ============================================================== --}}
    <li class="pt-3">
        <p class="px-2.5 text-[10px] font-black text-slate-300 dark:text-slate-200 uppercase tracking-wider">{{ $term['customer'] ?? 'Customers' }} &amp; Growth</p>
    </li>

    <li>
        <a href="{{ route('business.customers.index') }}"
           class="group flex items-center gap-x-3 rounded-xl p-2 font-semibold transition {{ request()->routeIs('business.customers.*') ? 'bg-orange-600 text-white shadow-sm font-bold' : 'text-slate-300 hover:bg-gray-800 hover:text-white' }}">
            <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('business.customers.*') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197"/>
            </svg>
            <span>{{ $term['customer'] ?? 'Customers' }}</span>
        </a>
    </li>

    <li>
        <a href="{{ route('business.coupons.index') }}"
           class="group flex items-center gap-x-3 rounded-xl p-2 font-semibold transition {{ request()->routeIs('business.coupons.*') ? 'bg-orange-600 text-white shadow-sm font-bold' : 'text-slate-300 hover:bg-gray-800 hover:text-white' }}">
            <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('business.coupons.*') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
            </svg>
            <span>Coupons &amp; Discounts</span>
        </a>
    </li>

    @if ($can('happy_hours'))
        <li>
            <a href="{{ route('business.happy-hours.index') }}"
               class="group flex items-center gap-x-3 rounded-xl p-2 font-semibold transition {{ request()->routeIs('business.happy-hours.*') ? 'bg-orange-600 text-white shadow-sm font-bold' : 'text-slate-300 hover:bg-gray-800 hover:text-white' }}">
                <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('business.happy-hours.*') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>Happy Hours</span>
            </a>
        </li>
    @endif

    <li>
        <a href="{{ route('business.qr.index') }}"
           class="group flex items-center gap-x-3 rounded-xl p-2 font-semibold transition {{ request()->routeIs('business.qr*') ? 'bg-orange-600 text-white shadow-sm font-bold' : 'text-slate-300 hover:bg-gray-800 hover:text-white' }}">
            <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('business.qr*') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
            </svg>
            <span>QR Studio</span>
        </a>
    </li>

    {{-- ============================================================== --}}
    {{-- 5. BUSINESS INTELLIGENCE --}}
    {{-- ============================================================== --}}
    <li class="pt-3">
        <p class="px-2.5 text-[10px] font-black text-slate-300 dark:text-slate-200 uppercase tracking-wider">Business Intelligence</p>
    </li>

    <li>
        <a href="{{ route('business.analytics.index') }}"
           class="group flex items-center gap-x-3 rounded-xl p-2 font-semibold transition {{ request()->routeIs('business.analytics.*') ? 'bg-orange-600 text-white shadow-sm font-bold' : 'text-slate-300 hover:bg-gray-800 hover:text-white' }}">
            <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('business.analytics.*') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/>
            </svg>
            <span>Smart Analytics</span>
        </a>
    </li>

    <li>
        <a href="{{ route('business.reports.index') }}"
           class="group flex items-center gap-x-3 rounded-xl p-2 font-semibold transition {{ request()->routeIs('business.reports.*') ? 'bg-orange-600 text-white shadow-sm font-bold' : 'text-slate-300 hover:bg-gray-800 hover:text-white' }}">
            <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('business.reports.*') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
            <span>Reports & Exports</span>
        </a>
    </li>

    @php
        $aiAllowedAreas = ($biz && Auth::check())
            ? app(\App\Services\AI\AiAccessService::class)->allowedAreas(Auth::user(), $biz)
            : [];
    @endphp
    @if ($aiAllowedAreas['overview'] ?? false)
        <li class="pt-3" x-data="{ aiMenuOpen: {{ request()->routeIs('business.ai.*') ? 'true' : 'false' }} }">
            <button type="button" @click="aiMenuOpen = !aiMenuOpen"
                    class="w-full flex items-center justify-between rounded-xl px-2.5 py-2 font-bold transition {{ request()->routeIs('business.ai.*') ? 'bg-orange-600 text-white shadow-sm' : 'bg-amber-500/10 text-amber-300 border border-amber-500/30 hover:bg-amber-500/20' }}">
                <span class="flex items-center gap-2">
                    <span>✨ AI Center</span>
                </span>
                <svg class="w-3.5 h-3.5 transition-transform" :class="aiMenuOpen ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            <ul x-show="aiMenuOpen" style="{{ request()->routeIs('business.ai.*') ? '' : 'display: none;' }}" class="mt-1.5 pl-2 space-y-1 border-l border-gray-700/80 ml-2.5">
                <li>
                    <a href="{{ route('business.ai.overview') }}"
                       class="block rounded-lg px-2.5 py-1.5 font-semibold transition {{ request()->routeIs('business.ai.overview') ? 'bg-gray-800 text-amber-300 font-bold' : 'text-slate-300 hover:bg-gray-800 hover:text-white' }}">
                        AI Overview
                    </a>
                </li>
                @if ($aiAllowedAreas['assistant'] ?? false)
                    <li>
                        <a href="{{ route('business.ai.assistant') }}"
                           class="block rounded-lg px-2.5 py-1.5 font-semibold transition {{ request()->routeIs('business.ai.assistant*') ? 'bg-gray-800 text-amber-300 font-bold' : 'text-slate-300 hover:bg-gray-800 hover:text-white' }}">
                            AI Business Assistant
                        </a>
                    </li>
                @endif
                @if ($aiAllowedAreas['menu_creator'] ?? false)
                    <li>
                        <a href="{{ route('business.ai.menu-creator') }}"
                           class="block rounded-lg px-2.5 py-1.5 font-semibold transition {{ request()->routeIs('business.ai.menu-creator*') ? 'bg-gray-800 text-amber-300 font-bold' : 'text-slate-300 hover:bg-gray-800 hover:text-white' }}">
                            AI Menu Creator
                        </a>
                    </li>
                @endif
                @if ($aiAllowedAreas['marketing_studio'] ?? false)
                    <li>
                        <a href="{{ route('business.ai.marketing') }}"
                           class="block rounded-lg px-2.5 py-1.5 font-semibold transition {{ request()->routeIs('business.ai.marketing*') ? 'bg-gray-800 text-amber-300 font-bold' : 'text-slate-300 hover:bg-gray-800 hover:text-white' }}">
                            AI Marketing Studio
                        </a>
                    </li>
                @endif
                @if ($aiAllowedAreas['inventory_forecast'] ?? false)
                    <li>
                        <a href="{{ route('business.ai.inventory-forecast') }}"
                           class="block rounded-lg px-2.5 py-1.5 font-semibold transition {{ request()->routeIs('business.ai.inventory-forecast*') ? 'bg-gray-800 text-amber-300 font-bold' : 'text-slate-300 hover:bg-gray-800 hover:text-white' }}">
                            AI Inventory Forecast
                        </a>
                    </li>
                @endif
                @if ($aiAllowedAreas['sales_prediction'] ?? false)
                    <li>
                        <a href="{{ route('business.ai.sales-prediction') }}"
                           class="block rounded-lg px-2.5 py-1.5 font-semibold transition {{ request()->routeIs('business.ai.sales-prediction*') ? 'bg-gray-800 text-amber-300 font-bold' : 'text-slate-300 hover:bg-gray-800 hover:text-white' }}">
                            AI Sales Prediction
                        </a>
                    </li>
                @endif
                @if ($aiAllowedAreas['usage'] ?? false)
                    <li>
                        <a href="{{ route('business.ai.usage') }}"
                           class="block rounded-lg px-2.5 py-1.5 font-semibold transition {{ request()->routeIs('business.ai.usage*') ? 'bg-gray-800 text-amber-300 font-bold' : 'text-slate-300 hover:bg-gray-800 hover:text-white' }}">
                            AI Usage &amp; History
                        </a>
                    </li>
                @endif
                @if ($aiAllowedAreas['settings'] ?? false)
                    <li>
                        <a href="{{ route('business.ai.settings') }}"
                           class="block rounded-lg px-2.5 py-1.5 font-semibold transition {{ request()->routeIs('business.ai.settings*') ? 'bg-gray-800 text-amber-300 font-bold' : 'text-slate-300 hover:bg-gray-800 hover:text-white' }}">
                            AI Settings
                        </a>
                    </li>
                @endif
            </ul>
        </li>
    @endif

    {{-- ============================================================== --}}
    {{-- 6. ACCOUNT & SETTINGS --}}
    {{-- ============================================================== --}}
    <li class="pt-3">
        <p class="px-2.5 text-[10px] font-black text-slate-300 dark:text-slate-200 uppercase tracking-wider">Account & Settings</p>
    </li>

    <li>
        <a href="{{ route('business.settings.index') }}"
           class="group flex items-center gap-x-3 rounded-xl p-2 font-semibold transition {{ request()->routeIs('business.settings.*') ? 'bg-orange-600 text-white shadow-sm font-bold' : 'text-slate-300 hover:bg-gray-800 hover:text-white' }}">
            <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('business.settings.*') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            <span>Business Settings</span>
        </a>
    </li>

    @if ($biz && Auth::user()?->canManageBusinessSettings($biz))
        <li>
            <a href="{{ route('business.settings.index', ['section' => 'notifications']) }}"
               class="group flex items-center gap-x-3 rounded-xl p-2 font-semibold transition {{ request()->fullUrlIs('*section=notifications*') ? 'bg-gray-800 text-orange-400 font-bold' : 'text-slate-300 hover:bg-gray-800 hover:text-white' }}">
                <svg class="w-4 h-4 shrink-0 text-slate-400 group-hover:text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
                <span>Telegram &amp; Alerts</span>
            </a>
        </li>
    @endif

    <li>
        <a href="{{ route('business.security.index') }}"
           class="group flex items-center justify-between rounded-xl p-2 font-semibold transition {{ request()->routeIs('business.security.*') || request()->fullUrlIs('*section=security*') ? 'bg-orange-600 text-white shadow-sm font-bold' : 'text-slate-300 hover:bg-gray-800 hover:text-white' }}">
            <span class="flex items-center gap-x-3">
                <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('business.security.*') || request()->fullUrlIs('*section=security*') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
                <span>Security &amp; 2FA</span>
            </span>
            @if (Auth::user()?->two_factor_enabled)
                <span class="w-2 h-2 rounded-full bg-emerald-400" title="2FA Active"></span>
            @endif
        </a>
    </li>

    <li>
        <a href="{{ route('business.workflow-guide') }}"
           class="group flex items-center gap-x-3 rounded-xl p-2 font-semibold transition {{ request()->routeIs('business.guides.order-flow', 'business.workflow-guide') ? 'bg-orange-600 text-white shadow-sm font-bold' : 'text-slate-300 hover:bg-gray-800 hover:text-white' }}">
            <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('business.guides.order-flow', 'business.workflow-guide') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
            </svg>
            <span>Order Flow Guide</span>
        </a>
    </li>

    <li>
        <a href="{{ route('business.guides.tutorials') }}"
           class="group flex items-center gap-x-3 rounded-xl p-2 font-semibold transition {{ request()->routeIs('business.guides.tutorials') ? 'bg-orange-600 text-white shadow-sm font-bold' : 'text-slate-300 hover:bg-gray-800 hover:text-white' }}">
            <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('business.guides.tutorials') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
            </svg>
            <span>Tutorials & Guides</span>
        </a>
    </li>

    @if ($biz?->slug)
        <li>
            <a href="/{{ $biz->slug }}" target="_blank"
               class="group flex items-center justify-between rounded-xl p-2 font-semibold text-orange-400 hover:bg-gray-800 hover:text-orange-300 transition">
                <span class="flex items-center gap-x-3">
                    <svg class="w-4 h-4 shrink-0 text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                    </svg>
                    <span>View Storefront</span>
                </span>
                <span class="text-xs">&rarr;</span>
            </a>
        </li>
    @endif
</ul>
