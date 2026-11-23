@php
    $biz = $currentBusiness ?? \App\Services\Tenant\TenantContext::get();
    $can = fn($feature) => \App\Services\Business\BusinessFeatureService::canAccess($biz, $feature);
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
    {{-- 2. CATALOG & MENU --}}
    {{-- ============================================================== --}}
    <li class="pt-3">
        <p class="px-2.5 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Catalog & Menu</p>
    </li>

    <li>
        <a href="{{ route('business.products.index') }}"
           class="group flex items-center gap-x-3 rounded-xl p-2 font-semibold transition {{ request()->routeIs('business.products.index', 'business.products.create', 'business.products.edit') ? 'bg-orange-600 text-white shadow-sm font-bold' : 'text-slate-300 hover:bg-gray-800 hover:text-white' }}">
            <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('business.products.index') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
            </svg>
            <span>Products & Catalog</span>
        </a>
    </li>

    <li>
        <a href="{{ route('business.categories.index') }}"
           class="group flex items-center gap-x-3 rounded-xl p-2 font-semibold transition {{ request()->routeIs('business.categories.*') ? 'bg-orange-600 text-white shadow-sm font-bold' : 'text-slate-300 hover:bg-gray-800 hover:text-white' }}">
            <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('business.categories.*') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
            </svg>
            <span>Categories</span>
        </a>
    </li>

    <li>
        <a href="{{ route('business.storefront.templates') }}"
           class="group flex items-center gap-x-3 rounded-xl p-2 font-semibold transition {{ request()->routeIs('business.storefront.templates') ? 'bg-orange-600 text-white shadow-sm font-bold' : 'text-slate-300 hover:bg-gray-800 hover:text-white' }}">
            <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('business.storefront.templates') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
            </svg>
            <span>Storefront Templates</span>
        </a>
    </li>

    <li>
        <a href="{{ route('business.translations.index') }}"
           class="group flex items-center gap-x-3 rounded-xl p-2 font-semibold transition {{ request()->routeIs('business.translations.*') ? 'bg-orange-600 text-white shadow-sm font-bold' : 'text-slate-300 hover:bg-gray-800 hover:text-white' }}">
            <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('business.translations.*') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/>
            </svg>
            <span>Translation Manager</span>
        </a>
    </li>

    <li>
        <a href="{{ route('business.ai.menu-builder') }}"
           class="group flex items-center justify-between rounded-xl p-2 font-semibold transition {{ request()->routeIs('business.ai.menu-builder') ? 'bg-orange-600 text-white shadow-sm font-bold' : 'text-slate-300 hover:bg-gray-800 hover:text-white' }}">
            <div class="flex items-center gap-x-3">
                <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('business.ai.menu-builder') ? 'text-white' : 'text-orange-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                <span>AI Menu Builder</span>
            </div>
            <span class="text-[9px] bg-orange-500/20 text-orange-400 px-1.5 py-0.2 rounded font-bold">AI</span>
        </a>
    </li>

    <li>
        <a href="{{ route('business.products.import') }}"
           class="group flex items-center gap-x-3 rounded-xl p-2 font-semibold transition {{ request()->routeIs('business.products.import*') ? 'bg-orange-600 text-white shadow-sm font-bold' : 'text-slate-300 hover:bg-gray-800 hover:text-white' }}">
            <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('business.products.import*') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
            </svg>
            <span>Bulk Product Import</span>
        </a>
    </li>

    {{-- ============================================================== --}}
    {{-- 3. OPERATIONS --}}
    {{-- ============================================================== --}}
    <li class="pt-3">
        <p class="px-2.5 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Operations</p>
    </li>

    <li>
        <a href="{{ route('business.orders.index') }}"
           class="group flex items-center gap-x-3 rounded-xl p-2 font-semibold transition {{ request()->routeIs('business.orders.index', 'business.orders.show') ? 'bg-orange-600 text-white shadow-sm font-bold' : 'text-slate-300 hover:bg-gray-800 hover:text-white' }}">
            <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('business.orders.index') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
            </svg>
            <span>Orders</span>
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
            <span class="text-[9px] bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 px-1.5 py-0.2 rounded font-bold animate-pulse">FEED</span>
        </a>
    </li>

    @if ($can('reservations'))
        <li>
            <a href="{{ route('business.reservations.index') }}"
               class="group flex items-center gap-x-3 rounded-xl p-2 font-semibold transition {{ request()->routeIs('business.reservations.*') ? 'bg-orange-600 text-white shadow-sm font-bold' : 'text-slate-300 hover:bg-gray-800 hover:text-white' }}">
                <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('business.reservations.*') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <span>Reservations</span>
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
                <span>Customer Waitlist</span>
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
                <span>Tables Management</span>
            </a>
        </li>
    @endif

    @if ($can('kitchen'))
        <li>
            <a href="{{ route('business.kitchen.index') }}" target="_blank"
               class="group flex items-center justify-between rounded-xl p-2 font-semibold transition {{ request()->routeIs('business.kitchen.*') ? 'bg-orange-600 text-white shadow-sm font-bold' : 'text-slate-300 hover:bg-gray-800 hover:text-white' }}">
                <div class="flex items-center gap-x-3">
                    <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('business.kitchen.*') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                    </svg>
                    <span>Kitchen Display (KDS)</span>
                </div>
                <span class="text-[9px] bg-red-500/20 text-red-400 border border-red-500/30 px-1.5 py-0.2 rounded font-mono font-bold animate-pulse">LIVE</span>
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

    @if ($can('printers'))
        <li>
            <a href="{{ route('business.printers.index') }}"
               class="group flex items-center gap-x-3 rounded-xl p-2 font-semibold transition {{ request()->routeIs('business.printers.*') ? 'bg-orange-600 text-white shadow-sm font-bold' : 'text-slate-300 hover:bg-gray-800 hover:text-white' }}">
                <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('business.printers.*') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                <span>Printer Management</span>
            </a>
        </li>
    @endif

    {{-- ============================================================== --}}
    {{-- 4. MANAGEMENT --}}
    {{-- ============================================================== --}}
    <li class="pt-3">
        <p class="px-2.5 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Management</p>
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

    <li>
        <a href="{{ route('business.inventory.index') }}"
           class="group flex items-center gap-x-3 rounded-xl p-2 font-semibold transition {{ request()->routeIs('business.inventory.*') ? 'bg-orange-600 text-white shadow-sm font-bold' : 'text-slate-300 hover:bg-gray-800 hover:text-white' }}">
            <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('business.inventory.*') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
            </svg>
            <span>Inventory Management</span>
        </a>
    </li>

    @if ($can('recipes'))
        <li>
            <a href="{{ route('business.recipes.index') }}"
               class="group flex items-center gap-x-3 rounded-xl p-2 font-semibold transition {{ request()->routeIs('business.recipes.*') ? 'bg-orange-600 text-white shadow-sm font-bold' : 'text-slate-300 hover:bg-gray-800 hover:text-white' }}">
                <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('business.recipes.*') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <span>Recipes & Ingredients</span>
            </a>
        </li>
    @endif

    @if ($can('suppliers'))
        <li>
            <a href="{{ route('business.suppliers.index') }}"
               class="group flex items-center gap-x-3 rounded-xl p-2 font-semibold transition {{ request()->routeIs('business.suppliers.*') ? 'bg-orange-600 text-white shadow-sm font-bold' : 'text-slate-300 hover:bg-gray-800 hover:text-white' }}">
                <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('business.suppliers.*') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/>
                </svg>
                <span>Suppliers & POs</span>
            </a>
        </li>
    @endif

    <li>
        <a href="{{ route('business.branches.index') }}"
           class="group flex items-center gap-x-3 rounded-xl p-2 font-semibold transition {{ request()->routeIs('business.branches.*') ? 'bg-orange-600 text-white shadow-sm font-bold' : 'text-slate-300 hover:bg-gray-800 hover:text-white' }}">
            <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('business.branches.*') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
            </svg>
            <span>Branch Management</span>
        </a>
    </li>

    @if ($can('delivery_zones'))
        <li>
            <a href="{{ route('business.delivery-zones.index') }}"
               class="group flex items-center gap-x-3 rounded-xl p-2 font-semibold transition {{ request()->routeIs('business.delivery-zones.*') ? 'bg-orange-600 text-white shadow-sm font-bold' : 'text-slate-300 hover:bg-gray-800 hover:text-white' }}">
                <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('business.delivery-zones.*') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <span>Delivery Zones</span>
            </a>
        </li>
    @endif

    {{-- ============================================================== --}}
    {{-- 5. CUSTOMER & MARKETING --}}
    {{-- ============================================================== --}}
    <li class="pt-3">
        <p class="px-2.5 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Customer & Marketing</p>
    </li>

    <li>
        <a href="{{ route('business.customers.index') }}"
           class="group flex items-center gap-x-3 rounded-xl p-2 font-semibold transition {{ request()->routeIs('business.customers.*') ? 'bg-orange-600 text-white shadow-sm font-bold' : 'text-slate-300 hover:bg-gray-800 hover:text-white' }}">
            <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('business.customers.*') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
            </svg>
            <span>Customer Management</span>
        </a>
    </li>

    <li>
        <a href="{{ route('business.reviews.index') }}"
           class="group flex items-center gap-x-3 rounded-xl p-2 font-semibold transition {{ request()->routeIs('business.reviews.*') ? 'bg-orange-600 text-white shadow-sm font-bold' : 'text-slate-300 hover:bg-gray-800 hover:text-white' }}">
            <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('business.reviews.*') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
            </svg>
            <span>Customer Reviews</span>
        </a>
    </li>

    <li>
        <a href="{{ route('business.coupons.index') }}"
           class="group flex items-center gap-x-3 rounded-xl p-2 font-semibold transition {{ request()->routeIs('business.coupons.*') ? 'bg-orange-600 text-white shadow-sm font-bold' : 'text-slate-300 hover:bg-gray-800 hover:text-white' }}">
            <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('business.coupons.*') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
            </svg>
            <span>Coupons & Discounts</span>
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
        <a href="{{ route('business.loyalty.index') }}"
           class="group flex items-center gap-x-3 rounded-xl p-2 font-semibold transition {{ request()->routeIs('business.loyalty.*') ? 'bg-orange-600 text-white shadow-sm font-bold' : 'text-slate-300 hover:bg-gray-800 hover:text-white' }}">
            <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('business.loyalty.*') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span>Loyalty Program</span>
        </a>
    </li>

    <li>
        <a href="{{ route('business.referrals.index') }}"
           class="group flex items-center gap-x-3 rounded-xl p-2 font-semibold transition {{ request()->routeIs('business.referrals.*') ? 'bg-orange-600 text-white shadow-sm font-bold' : 'text-slate-300 hover:bg-gray-800 hover:text-white' }}">
            <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('business.referrals.*') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
            </svg>
            <span>Referral Program</span>
        </a>
    </li>

    {{-- ============================================================== --}}
    {{-- 6. BUSINESS INTELLIGENCE --}}
    {{-- ============================================================== --}}
    <li class="pt-3">
        <p class="px-2.5 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Business Intelligence</p>
    </li>

    <li>
        <a href="{{ route('business.analytics.index') }}"
           class="group flex items-center gap-x-3 rounded-xl p-2 font-semibold transition {{ request()->routeIs('business.analytics.*') ? 'bg-orange-600 text-white shadow-sm font-bold' : 'text-slate-300 hover:bg-gray-800 hover:text-white' }}">
            <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('business.analytics.*') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
            <span>Analytics</span>
        </a>
    </li>

    <li>
        <a href="{{ route('business.reports.index') }}"
           class="group flex items-center gap-x-3 rounded-xl p-2 font-semibold transition {{ request()->routeIs('business.reports.*') ? 'bg-orange-600 text-white shadow-sm font-bold' : 'text-slate-300 hover:bg-gray-800 hover:text-white' }}">
            <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('business.reports.*') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <span>Reports & Exports</span>
        </a>
    </li>

    <li>
        <a href="{{ route('business.ai.insights') }}"
           class="group flex items-center justify-between rounded-xl p-2 font-semibold transition {{ request()->routeIs('business.ai.insights') ? 'bg-orange-600 text-white shadow-sm font-bold' : 'text-slate-300 hover:bg-gray-800 hover:text-white' }}">
            <div class="flex items-center gap-x-3">
                <svg class="w-4 h-4 shrink-0 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                </svg>
                <span>SABAYRA AI Insights</span>
            </div>
            <span class="text-[9px] bg-amber-500/20 text-amber-400 px-1.5 py-0.2 rounded font-bold">AI</span>
        </a>
    </li>

    <li>
        <a href="{{ route('business.ai.menu-audit') }}"
           class="group flex items-center justify-between rounded-xl p-2 font-semibold transition {{ request()->routeIs('business.ai.menu-audit') ? 'bg-orange-600 text-white shadow-sm font-bold' : 'text-slate-300 hover:bg-gray-800 hover:text-white' }}">
            <div class="flex items-center gap-x-3">
                <svg class="w-4 h-4 shrink-0 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>Menu Audit</span>
            </div>
            <span class="text-[9px] bg-emerald-500/20 text-emerald-400 px-1.5 py-0.2 rounded font-bold">AI</span>
        </a>
    </li>

    <li>
        <a href="{{ route('business.ai.sentiment') }}"
           class="group flex items-center justify-between rounded-xl p-2 font-semibold transition {{ request()->routeIs('business.ai.sentiment') ? 'bg-orange-600 text-white shadow-sm font-bold' : 'text-slate-300 hover:bg-gray-800 hover:text-white' }}">
            <div class="flex items-center gap-x-3">
                <svg class="w-4 h-4 shrink-0 text-cyan-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span>Sentiment Radar</span>
            </div>
            <span class="text-[9px] bg-cyan-500/20 text-cyan-400 px-1.5 py-0.2 rounded font-bold">AI</span>
        </a>
    </li>

    <li>
        <a href="{{ route('business.ai.upsell-pairings') }}"
           class="group flex items-center justify-between rounded-xl p-2 font-semibold transition {{ request()->routeIs('business.ai.upsell-pairings') ? 'bg-orange-600 text-white shadow-sm font-bold' : 'text-slate-300 hover:bg-gray-800 hover:text-white' }}">
            <div class="flex items-center gap-x-3">
                <svg class="w-4 h-4 shrink-0 text-purple-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                </svg>
                <span>AI Upsell Pairings</span>
            </div>
            <span class="text-[9px] bg-purple-500/20 text-purple-400 px-1.5 py-0.2 rounded font-bold">AI</span>
        </a>
    </li>

    {{-- ============================================================== --}}
    {{-- 7. QR & ADVANCED FEATURES --}}
    {{-- ============================================================== --}}
    <li class="pt-3">
        <p class="px-2.5 text-[10px] font-bold text-slate-400 uppercase tracking-wider">QR & Advanced</p>
    </li>

    <li>
        <a href="{{ route('business.qr.index') }}"
           class="group flex items-center gap-x-3 rounded-xl p-2 font-semibold transition {{ request()->routeIs('business.qr.*') ? 'bg-orange-600 text-white shadow-sm font-bold' : 'text-slate-300 hover:bg-gray-800 hover:text-white' }}">
            <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('business.qr.*') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
            </svg>
            <span>QR Code Management</span>
        </a>
    </li>

    @if ($can('ar_cards'))
        <li>
            <a href="{{ route('business.ar.cards') }}"
               class="group flex items-center gap-x-3 rounded-xl p-2 font-semibold transition {{ request()->routeIs('business.ar.cards') ? 'bg-orange-600 text-white shadow-sm font-bold' : 'text-slate-300 hover:bg-gray-800 hover:text-white' }}">
                <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('business.ar.cards') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                <span>AR Table Cards</span>
            </a>
        </li>
    @endif

    <li>
        <a href="{{ route('business.ar.scan-assistant') }}"
           class="group flex items-center gap-x-3 rounded-xl p-2 font-semibold transition {{ request()->routeIs('business.ar.scan-assistant') ? 'bg-orange-600 text-white shadow-sm font-bold' : 'text-slate-300 hover:bg-gray-800 hover:text-white' }}">
            <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('business.ar.scan-assistant') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-2 1m2-1l-2-1m2 1v2.5M14 4l-2-1-2 1M4 7l2-1M4 7l2 1M4 7v2.5M12 21l-2-1m2 1l2-1m-2 1v-2.5M6 18l-2-1v-2.5M18 18l2-1v-2.5"/>
            </svg>
            <span>3D Scan Assistant</span>
        </a>
    </li>

    {{-- ============================================================== --}}
    {{-- 8. LEARNING & SUPPORT --}}
    {{-- ============================================================== --}}
    <li class="pt-3">
        <p class="px-2.5 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Learning & Support</p>
    </li>

    <li>
        <a href="{{ route('business.guides.order-flow') }}"
           class="group flex items-center gap-x-3 rounded-xl p-2 font-semibold transition {{ request()->routeIs('business.guides.order-flow') ? 'bg-orange-600 text-white shadow-sm font-bold' : 'text-slate-300 hover:bg-gray-800 hover:text-white' }}">
            <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('business.guides.order-flow') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
            </svg>
            <span>Order Flow Guide</span>
        </a>
    </li>

    <li>
        <a href="{{ route('business.tutorials.index') }}"
           class="group flex items-center gap-x-3 rounded-xl p-2 font-semibold transition {{ request()->routeIs('business.tutorials.*') ? 'bg-orange-600 text-white shadow-sm font-bold' : 'text-slate-300 hover:bg-gray-800 hover:text-white' }}">
            <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('business.tutorials.*') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
            </svg>
            <span>Tutorials</span>
        </a>
    </li>

    <li>
        <a href="{{ route('business.help.index') }}"
           class="group flex items-center gap-x-3 rounded-xl p-2 font-semibold transition {{ request()->routeIs('business.help.*') ? 'bg-orange-600 text-white shadow-sm font-bold' : 'text-slate-300 hover:bg-gray-800 hover:text-white' }}">
            <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('business.help.*') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/>
            </svg>
            <span>Help Center</span>
        </a>
    </li>

    {{-- ============================================================== --}}
    {{-- 9. ACCOUNT --}}
    {{-- ============================================================== --}}
    <li class="pt-3">
        <p class="px-2.5 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Account</p>
    </li>

    <li>
        <a href="{{ route('business.subscription.index') }}"
           class="group flex items-center gap-x-3 rounded-xl p-2 font-semibold transition {{ request()->routeIs('business.subscription.*') ? 'bg-orange-600 text-white shadow-sm font-bold' : 'text-slate-300 hover:bg-gray-800 hover:text-white' }}">
            <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('business.subscription.*') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
            </svg>
            <span>Subscription & Billing</span>
        </a>
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

    <li>
        <a href="{{ route('business.security.index') }}"
           class="group flex items-center gap-x-3 rounded-xl p-2 font-semibold transition {{ request()->routeIs('business.security.*') ? 'bg-orange-600 text-white shadow-sm font-bold' : 'text-slate-300 hover:bg-gray-800 hover:text-white' }}">
            <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('business.security.*') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
            <span>Security Center</span>
        </a>
    </li>

    @if ($biz)
        <li>
            <a href="/{{ $biz->slug }}" target="_blank"
               class="group flex items-center justify-between rounded-xl p-2 font-semibold text-emerald-400 hover:bg-gray-800 hover:text-emerald-300 transition">
                <div class="flex items-center gap-x-3">
                    <svg class="w-4 h-4 shrink-0 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                    </svg>
                    <span>View Public Storefront</span>
                </div>
                <span class="text-xs">&nearr;</span>
            </a>
        </li>
    @endif
</ul>
