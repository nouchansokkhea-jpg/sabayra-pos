<ul role="list" class="flex flex-col flex-1 gap-y-1">
    <li>
        <a href="{{ route('admin.dashboard') }}"
           class="group flex gap-x-3 rounded-xl p-2.5 text-sm font-semibold transition {{ request()->routeIs('admin.dashboard') ? 'bg-orange-600 text-white shadow-sm font-bold' : 'text-slate-200 hover:bg-gray-800 hover:text-white' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            {{ __('app.dashboard') }}
        </a>
    </li>

    <li class="pt-3">
        <p class="px-2.5 text-xs font-bold text-slate-300 uppercase tracking-wider">Platform Core</p>
    </li>

    <li>
        <a href="{{ route('admin.businesses.index') }}"
           class="group flex gap-x-3 rounded-xl p-2.5 text-sm font-semibold transition {{ request()->routeIs('admin.businesses.*') ? 'bg-orange-600 text-white shadow-sm font-bold' : 'text-slate-200 hover:bg-gray-800 hover:text-white' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
            </svg>
            All Businesses
        </a>
    </li>

    <li>
        <a href="{{ route('admin.users.index') }}"
           class="group flex gap-x-3 rounded-xl p-2.5 text-sm font-semibold transition {{ request()->routeIs('admin.users.*') ? 'bg-orange-600 text-white shadow-sm font-bold' : 'text-slate-200 hover:bg-gray-800 hover:text-white' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
            </svg>
            User Accounts
        </a>
    </li>

    <li>
        <a href="{{ route('admin.categories.index') }}"
           class="group flex gap-x-3 rounded-xl p-2.5 text-sm font-semibold transition {{ request()->routeIs('admin.categories.*') ? 'bg-orange-600 text-white shadow-sm font-bold' : 'text-slate-200 hover:bg-gray-800 hover:text-white' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
            </svg>
            Business Sectors
        </a>
    </li>

    <li class="pt-3">
        <p class="px-2.5 text-xs font-bold text-slate-300 uppercase tracking-wider">Billing & Revenue</p>
    </li>

    <li>
        <a href="{{ route('admin.subscriptions.index') }}"
           class="group flex gap-x-3 rounded-xl p-2.5 text-sm font-semibold transition {{ request()->routeIs('admin.subscriptions.*') ? 'bg-orange-600 text-white shadow-sm font-bold' : 'text-slate-200 hover:bg-gray-800 hover:text-white' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
            </svg>
            Subscriptions
        </a>
    </li>

    <li>
        <a href="{{ route('admin.plans.index') }}"
           class="group flex gap-x-3 rounded-xl p-2.5 text-sm font-semibold transition {{ request()->routeIs('admin.plans.*') ? 'bg-orange-600 text-white shadow-sm font-bold' : 'text-slate-200 hover:bg-gray-800 hover:text-white' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
            </svg>
            Pricing Plans
        </a>
    </li>

    <li>
        <a href="{{ route('admin.payments.index') }}"
           class="group flex gap-x-3 rounded-xl p-2.5 text-sm font-semibold transition {{ request()->routeIs('admin.payments.*') ? 'bg-orange-600 text-white shadow-sm font-bold' : 'text-slate-200 hover:bg-gray-800 hover:text-white' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            Payments & Invoices
        </a>
    </li>

    <li>
        <a href="{{ route('admin.analytics.index') }}"
           class="group flex gap-x-3 rounded-xl p-2.5 text-sm font-semibold transition {{ request()->routeIs('admin.analytics.*') ? 'bg-orange-600 text-white shadow-sm font-bold' : 'text-slate-200 hover:bg-gray-800 hover:text-white' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
            </svg>
            Revenue Analytics
        </a>
    </li>

    <li class="pt-3">
        <p class="px-2.5 text-xs font-bold text-slate-300 uppercase tracking-wider">Operations & Support</p>
    </li>

    <li>
        <a href="{{ route('admin.support.index') }}"
           class="group flex gap-x-3 rounded-xl p-2.5 text-sm font-semibold transition {{ request()->routeIs('admin.support.*') ? 'bg-orange-600 text-white shadow-sm font-bold' : 'text-slate-200 hover:bg-gray-800 hover:text-white' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/>
            </svg>
            Support Management
        </a>
    </li>

    <li>
        <a href="{{ route('admin.activity-logs.index') }}"
           class="group flex gap-x-3 rounded-xl p-2.5 text-sm font-semibold transition {{ request()->routeIs('admin.activity-logs.*') ? 'bg-orange-600 text-white shadow-sm font-bold' : 'text-slate-200 hover:bg-gray-800 hover:text-white' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            System Activity Logs
        </a>
    </li>

    <li>
        <a href="{{ route('admin.settings.index') }}"
           class="group flex gap-x-3 rounded-xl p-2.5 text-sm font-semibold transition {{ request()->routeIs('admin.settings.*') ? 'bg-orange-600 text-white shadow-sm font-bold' : 'text-slate-200 hover:bg-gray-800 hover:text-white' }}">
            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            Platform Settings
        </a>
    </li>
</ul>
