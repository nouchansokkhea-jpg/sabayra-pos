<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark h-full bg-gray-950 text-gray-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Merchant Workspace' }} - {{ $currentBusiness->name ?? 'SABAYRA' }}</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}" />

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Kantumruy+Pro:wght@400;500;600;700&family=Battambang:wght@400;700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans antialiased text-gray-800 dark:text-gray-100" x-data="{ sidebarOpen: false }">
    <div class="min-h-full">
        <!-- Off-canvas menu for mobile -->
        <div x-show="sidebarOpen" class="relative z-50 lg:hidden" role="dialog" aria-modal="true" style="display: none;">
            <div x-show="sidebarOpen"
                 x-transition:enter="transition-opacity ease-linear duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition-opacity ease-linear duration-300"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-gray-900/80"></div>

            <div class="fixed inset-0 flex">
                <div x-show="sidebarOpen"
                     x-transition:enter="transition ease-in-out duration-300 transform"
                     x-transition:enter-start="-translate-x-full"
                     x-transition:enter-end="translate-x-0"
                     x-transition:leave="transition ease-in-out duration-300 transform"
                     x-transition:leave-start="translate-x-0"
                     x-transition:leave-end="-translate-x-full"
                     class="relative mr-16 flex w-full max-w-xs flex-1">
                    <div class="flex flex-col px-6 pb-4 overflow-y-auto bg-gray-900 ring-1 ring-white/10 w-full">
                        <div class="flex items-center h-16 shrink-0 justify-between">
                            <a href="{{ route('business.dashboard') }}" class="flex items-center space-x-2.5">
                                <img src="{{ asset('images/sabayra-icon.png') }}" alt="SABAYRA" class="w-8 h-8 rounded-lg object-contain shrink-0" />
                                <div>
                                    <span class="text-orange-500 text-xl font-black tracking-tight leading-none block">SABAYRA</span>
                                    <span class="text-[9px] text-orange-400 font-mono font-bold">MERCHANT POS</span>
                                </div>
                            </a>
                            <button @click="sidebarOpen = false" class="text-gray-400 hover:text-white">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                        <nav class="flex flex-col flex-1 mt-4">
                            @include('layouts.business-nav-links')
                        </nav>
                    </div>
                </div>
            </div>
        </div>

        <!-- Static sidebar for desktop -->
        <div class="hidden lg:fixed lg:inset-y-0 lg:z-40 lg:flex lg:w-64 lg:flex-col">
            <div class="flex flex-col px-6 pb-4 overflow-y-auto bg-gray-900 border-r border-gray-800 grow">
                <!-- Platform Brand & Business Header -->
                <div class="flex items-center h-16 shrink-0 justify-between border-b border-gray-800/80">
                    <a href="{{ route('business.dashboard') }}" class="flex items-center space-x-2.5">
                        <img src="{{ asset('images/sabayra-icon.png') }}" alt="SABAYRA" class="w-8 h-8 rounded-lg object-contain shrink-0" />
                        <div>
                            <span class="text-orange-500 text-xl font-black tracking-tight leading-none block">SABAYRA</span>
                            <span class="text-[9px] text-orange-400 font-mono font-bold">POS & SAAS</span>
                        </div>
                    </a>
                </div>

                <!-- Active Business Switcher Dropdown -->
                @if ($currentBusiness)
                    <div class="relative mt-4" x-data="{ switcherOpen: false }">
                        <button @click="switcherOpen = !switcherOpen"
                                class="w-full flex items-center justify-between p-2.5 rounded-xl bg-gray-800/80 hover:bg-gray-800 border border-gray-700/60 text-left transition group">
                            <div class="flex items-center space-x-2.5 truncate">
                                <div class="w-7 h-7 rounded-lg bg-orange-600 text-white font-bold flex items-center justify-center text-xs shrink-0 shadow-xs">
                                    {{ strtoupper(substr($currentBusiness->name, 0, 1)) }}
                                </div>
                                <div class="truncate">
                                    <p class="text-xs font-bold text-white truncate">{{ $currentBusiness->name }}</p>
                                    <p class="text-[10px] text-slate-300 font-medium truncate capitalize">{{ str_replace('_', ' ', $currentBusiness->business_type) }}</p>
                                </div>
                            </div>
                            <svg class="w-4 h-4 text-slate-400 group-hover:text-white shrink-0 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"/>
                            </svg>
                        </button>

                        <div x-show="switcherOpen" @click.away="switcherOpen = false" style="display: none;"
                             class="absolute left-0 right-0 w-full mt-1.5 bg-gray-800 border border-gray-700 rounded-xl shadow-xl py-1 z-50">
                            <p class="px-3 py-1.5 text-[10px] font-bold text-slate-300 uppercase tracking-wider">Switch Business</p>
                            @foreach ($userBusinesses as $biz)
                                <form method="POST" action="{{ route('business.switch', $biz) }}">
                                    @csrf
                                    <button type="submit" class="w-full text-left px-3 py-2 text-xs flex items-center justify-between hover:bg-gray-700/70 {{ $biz->id === $currentBusiness->id ? 'text-orange-400 font-bold bg-gray-750' : 'text-gray-200' }}">
                                        <span class="truncate">{{ $biz->name }}</span>
                                        @if ($biz->id === $currentBusiness->id)
                                            <span class="w-1.5 h-1.5 rounded-full bg-orange-400"></span>
                                        @endif
                                    </button>
                                </form>
                            @endforeach

                            <div class="border-t border-gray-700 mt-1 pt-1">
                                <a href="{{ route('business.create') }}" class="flex items-center px-3 py-2 text-xs font-semibold text-orange-400 hover:bg-gray-700/70">
                                    <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                    </svg>
                                    + Add New Business
                                </a>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Navigation Links -->
                <nav class="flex flex-col flex-1 mt-4">
                    @include('layouts.business-nav-links')
                </nav>

                <!-- Live Digital Menu Preview Pill -->
                @if ($currentBusiness)
                    <div class="pt-4 border-t border-gray-800">
                        <a href="/{{ $currentBusiness->slug }}" target="_blank"
                           class="flex items-center justify-between px-3 py-2 text-xs font-semibold rounded-xl bg-orange-600/15 text-orange-400 hover:bg-orange-600/25 border border-orange-500/30 transition">
                            <span class="flex items-center space-x-2 truncate">
                                <span class="relative flex h-2 w-2">
                                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                  <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                                </span>
                                <span>{{ __('app.view_menu') }}</span>
                            </span>
                            <svg class="w-3.5 h-3.5 ml-1 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                            </svg>
                        </a>
                    </div>
                @endif
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="lg:pl-64 flex flex-col flex-1 min-h-screen">
            <!-- Top Navigation Bar -->
            <header class="sticky top-0 z-30 flex items-center justify-between h-16 px-4 bg-white border-b border-gray-200 dark:bg-gray-800 dark:border-gray-700 sm:px-6 lg:px-8 shadow-xs">
                <div class="flex items-center space-x-4">
                    <button @click="sidebarOpen = true" class="text-slate-600 hover:text-slate-900 lg:hidden dark:text-slate-300 dark:hover:text-white">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <h1 class="text-lg font-bold text-slate-900 dark:text-white truncate">
                        {{ $heading ?? 'Dashboard' }}
                    </h1>
                </div>

                <!-- Right Topbar Items -->
                <div class="flex items-center space-x-3 sm:space-x-4">
                    <!-- Staff Role Indicator -->
                    @if ($currentBusiness && Auth::check())
                        @php $userRole = Auth::user()->roleInBusiness($currentBusiness); @endphp
                        <span class="hidden sm:inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold {{ ($userRole === 'owner') ? 'bg-amber-500/15 text-amber-400 border border-amber-500/30' : 'bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-slate-200' }} capitalize">
                            Role: {{ $userRole ? ucfirst($userRole) : 'Member' }}
                        </span>
                    @endif

                    <!-- Language Selector -->
                    <div class="flex items-center text-xs font-bold border border-slate-200 rounded-lg dark:border-slate-700 overflow-hidden">
                        <a href="{{ route('locale.switch', 'en') }}" class="px-2.5 py-1.5 transition {{ app()->getLocale() === 'en' ? 'bg-orange-600 text-white font-bold' : 'text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-700' }}">EN</a>
                        <a href="{{ route('locale.switch', 'km') }}" class="px-2.5 py-1.5 transition {{ app()->getLocale() === 'km' ? 'bg-orange-600 text-white font-bold' : 'text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-700' }}">ខ្មែរ</a>
                    </div>

                    <!-- Super Admin Switcher Link -->
                    @if (Auth::user()?->isSuperAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-white bg-purple-700 hover:bg-purple-800 rounded-lg shadow-sm transition">
                            <span class="w-1.5 h-1.5 rounded-full bg-purple-300 animate-pulse"></span>
                            <span>🛡️ Super Admin</span>
                        </a>
                    @endif

                    <!-- In-App Notifications Bell -->
                    @if ($currentBusiness && Auth::check() && \Illuminate\Support\Facades\Schema::hasTable('notifications'))
                        @php
                            $businessNotifs = Auth::user()->notifications()
                                ->latest()
                                ->take(35)
                                ->get()
                                ->filter(fn ($n) => ($n->data['business_id'] ?? null) === $currentBusiness->id)
                                ->take(8);
                            $unreadNotifsCount = $businessNotifs->whereNull('read_at')->count();
                        @endphp
                        <div class="relative" x-data="{ notifOpen: false }">
                            <button @click="notifOpen = !notifOpen"
                                    type="button"
                                    aria-label="In-app notifications"
                                    class="relative p-2 rounded-lg border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/70 transition">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                </svg>
                                @if ($unreadNotifsCount > 0)
                                    <span class="absolute -top-1 -right-1 min-w-[1.1rem] h-[1.1rem] px-1 rounded-full bg-orange-600 text-white text-[10px] font-black flex items-center justify-center">
                                        {{ $unreadNotifsCount > 9 ? '9+' : $unreadNotifsCount }}
                                    </span>
                                @endif
                            </button>

                            <div x-show="notifOpen" @click.away="notifOpen = false" style="display: none;"
                                 class="absolute right-0 mt-2 w-80 sm:w-96 rounded-2xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-xl z-50 overflow-hidden">
                                <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                                    <div>
                                        <p class="text-xs font-black text-slate-900 dark:text-white">Notifications</p>
                                        <p class="text-[11px] text-slate-500 dark:text-slate-400">{{ $currentBusiness->name }}</p>
                                    </div>
                                    @if ($unreadNotifsCount > 0)
                                        <form method="POST" action="{{ route('business.notifications.read-all') }}">
                                            @csrf
                                            <button type="submit" class="text-[11px] font-bold text-orange-600 dark:text-orange-400 hover:underline">
                                                Mark all read
                                            </button>
                                        </form>
                                    @endif
                                </div>

                                <div class="max-h-80 overflow-y-auto divide-y divide-gray-100 dark:divide-gray-700/60">
                                    @forelse ($businessNotifs as $item)
                                        <div class="p-3.5 text-xs {{ is_null($item->read_at) ? 'bg-orange-50/50 dark:bg-orange-950/15' : '' }}">
                                            <div class="flex items-start justify-between gap-2">
                                                <div class="min-w-0">
                                                    <p class="font-bold text-slate-900 dark:text-white truncate">{{ $item->data['title'] ?? 'Business Alert' }}</p>
                                                    <p class="text-[11px] text-slate-600 dark:text-slate-300 mt-0.5 line-clamp-2">{{ $item->data['message'] ?? '' }}</p>
                                                    <p class="text-[10px] text-slate-400 mt-1">{{ $item->created_at?->diffForHumans() }}</p>
                                                </div>
                                                @if (is_null($item->read_at))
                                                    <form method="POST" action="{{ route('business.notifications.read', $item->id) }}">
                                                        @csrf
                                                        <button type="submit" class="text-[10px] font-bold text-orange-600 dark:text-orange-400 hover:underline shrink-0">
                                                            Read
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </div>
                                    @empty
                                        <p class="px-4 py-8 text-center text-xs text-slate-500 dark:text-slate-400">
                                            No notifications yet for this workspace.
                                        </p>
                                    @endforelse
                                </div>

                                <div class="px-4 py-2.5 bg-slate-50 dark:bg-gray-900/50 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between">
                                    <a href="{{ route('business.settings.index', ['section' => 'notifications']) }}" class="text-[11px] font-bold text-slate-700 dark:text-slate-200 hover:text-orange-500">
                                        Notification Settings &rarr;
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- User Profile Dropdown -->
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" class="flex items-center space-x-2 text-sm focus:outline-none">
                            <div class="w-8 h-8 rounded-full bg-orange-600 text-white font-black flex items-center justify-center text-xs shadow-xs">
                                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                            </div>
                            <span class="hidden md:inline-block font-bold text-slate-800 dark:text-slate-100">{{ Auth::user()->name }}</span>
                        </button>

                        <div x-show="open" @click.away="open = false" style="display: none;"
                             class="absolute right-0 w-48 mt-2 origin-top-right bg-white border border-gray-200 rounded-xl shadow-lg dark:bg-gray-800 dark:border-gray-700 py-1 z-50">
                            <div class="px-4 py-2 border-b border-gray-100 dark:border-gray-700">
                                <p class="text-xs text-gray-600 dark:text-gray-300 font-medium">Signed in as</p>
                                <p class="text-sm font-bold truncate text-gray-900 dark:text-white">{{ Auth::user()->email }}</p>
                            </div>
                            <a href="{{ route('business.settings.index', ['section' => 'account']) }}" class="block px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 dark:text-gray-200 dark:hover:bg-gray-700">
                                Account &amp; Security
                            </a>
                            <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-xs font-medium text-gray-500 hover:bg-gray-50 dark:text-gray-400 dark:hover:bg-gray-700">
                                Classic Profile View
                            </a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full text-left px-4 py-2 text-sm font-bold text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/20">
                                    {{ __('app.logout') }}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Header Slot -->
            @if (isset($header))
                <div class="px-4 sm:px-6 lg:px-8 pt-6">
                    {{ $header }}
                </div>
            @endif

            <!-- Notifications -->
            <div class="px-4 sm:px-6 lg:px-8 mt-4">
                @if (session('success') || session('status'))
                    <div class="p-4 mb-4 text-sm text-emerald-800 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/50 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-emerald-600 dark:text-emerald-400 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <span>{{ session('success') ?? session('status') }}</span>
                    </div>
                @endif
                @if (session('error'))
                    <div class="p-4 mb-4 text-sm text-red-800 rounded-xl bg-red-50 dark:bg-red-950/40 dark:text-red-300 border border-red-200 dark:border-red-800/50 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-red-600 dark:text-red-400 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        <span>{{ session('error') }}</span>
                    </div>
                @endif
            </div>

            <!-- Page Body -->
            <main class="flex-1 px-4 sm:px-6 lg:px-8 py-6">
                {{ $slot }}
            </main>
        </div>
    </div>
</body>
</html>
