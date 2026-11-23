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
           class="group flex items-center gap-x-3 rounded-xl p-2 font-semibold transition {{ request()->routeIs('business.products.*') ? 'bg-orange-600 text-white shadow-sm font-bold' : 'text-slate-300 hover:bg-gray-800 hover:text-white' }}">
            <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('business.products.*') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
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

    {{-- ============================================================== --}}
    {{-- 3. OPERATIONS --}}
    {{-- ============================================================== --}}
    <li class="pt-3">
        <p class="px-2.5 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Operations</p>
    </li>

    <li>
        <a href="{{ route('business.orders.index') }}"
           class="group flex items-center gap-x-3 rounded-xl p-2 font-semibold transition {{ request()->routeIs('business.orders.*') ? 'bg-orange-600 text-white shadow-sm font-bold' : 'text-slate-300 hover:bg-gray-800 hover:text-white' }}">
            <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('business.orders.*') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
            </svg>
            <span>Orders Management</span>
        </a>
    </li>

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

    @if ($can('inventory'))
        <li>
            <a href="{{ route('business.inventory.index') }}"
               class="group flex items-center gap-x-3 rounded-xl p-2 font-semibold transition {{ request()->routeIs('business.inventory.*') ? 'bg-orange-600 text-white shadow-sm font-bold' : 'text-slate-300 hover:bg-gray-800 hover:text-white' }}">
                <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('business.inventory.*') ? 'text-white' : 'text-slate-400 group-hover:text-white' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
                <span>Inventory & Ingredients</span>
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

    {{-- ============================================================== --}}
    {{-- 5. BUSINESS INTELLIGENCE --}}
    {{-- ============================================================== --}}
    <li class="pt-3">
        <p class="px-2.5 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Business Intelligence</p>
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

    {{-- ============================================================== --}}
    {{-- 6. ACCOUNT & SETTINGS --}}
    {{-- ============================================================== --}}
    <li class="pt-3">
        <p class="px-2.5 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Account & Settings</p>
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
