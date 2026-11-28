<x-business-layout title="Settings & Security" heading="Settings & Security">
@php
    $navItems = [];
    if ($canManageBusinessSettings) {
        $navItems[] = [
            'id' => 'general',
            'label' => 'General Settings',
            'subtitle' => 'Storefront, slug & theme',
            'badge' => null,
            'group' => 'Workspace Settings',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>',
        ];
        $navItems[] = [
            'id' => 'business',
            'label' => 'Business Information',
            'subtitle' => 'Profile, logo, hours & social',
            'badge' => null,
            'group' => 'Workspace Settings',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>',
        ];
        $navItems[] = [
            'id' => 'experience',
            'label' => 'Category & Modules',
            'subtitle' => 'Industry workflow & features',
            'badge' => $categoryMeta['name_en'] ?? ucfirst(str_replace('_', ' ', (string) $business->business_type)),
            'group' => 'Workspace Settings',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>',
        ];
        $navItems[] = [
            'id' => 'payment',
            'label' => 'Payment & Tax',
            'subtitle' => 'Currency, Bakong KHQR & VAT',
            'badge' => $business->default_currency,
            'group' => 'Workspace Settings',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
        ];
        $navItems[] = [
            'id' => 'notifications',
            'label' => 'Notifications',
            'subtitle' => 'Telegram & alert channels',
            'badge' => ($telegramConnection?->is_active) ? 'Connected' : 'Not Linked',
            'badgeColor' => ($telegramConnection?->is_active)
                ? 'bg-emerald-500/15 text-emerald-400 border-emerald-500/30'
                : 'bg-slate-100 dark:bg-gray-900 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-gray-700',
            'group' => 'Workspace Settings',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>',
        ];
    }

    $navItems[] = [
        'id' => 'security',
        'label' => 'Security',
        'subtitle' => '2FA, login activity & sessions',
        'badge' => $twoFactorEnabled ? '2FA On' : ($twoFactorPendingSetup ? 'Setup' : '2FA Off'),
        'badgeColor' => $twoFactorEnabled ? 'bg-emerald-500/15 text-emerald-400 border-emerald-500/30' : 'bg-amber-500/15 text-amber-400 border-amber-500/30',
        'group' => 'Personal & Security',
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>',
    ];
    $navItems[] = [
        'id' => 'account',
        'label' => 'Account Settings',
        'subtitle' => 'Profile, language & password',
        'badge' => strtoupper($user->preferred_locale ?? app()->getLocale()),
        'group' => 'Personal & Security',
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>',
    ];
@endphp

<div class="max-w-7xl mx-auto space-y-6"
     x-data="{
         activeSection: '{{ $activeSection }}',
         switchSection(sectionId) {
             this.activeSection = sectionId;
             const url = new URL(window.location.href);
             url.searchParams.set('section', sectionId);
             window.history.replaceState({}, '', url.toString());
         }
     }">

    <!-- Top Workspace & Protection Summary Bar -->
    <div class="bg-slate-900 text-white rounded-2xl p-5 sm:p-6 border border-slate-800 shadow-lg flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div class="flex items-start sm:items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-orange-500 to-amber-500 flex items-center justify-center font-black text-lg text-white shrink-0 shadow-md shadow-orange-500/25">
                {{ mb_strtoupper(mb_substr($business->name, 0, 1)) }}
            </div>
            <div class="space-y-1">
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="text-base sm:text-lg font-black tracking-tight text-white">{{ $business->name }}</h2>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold {{ $business->status === 'active' ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' : 'bg-amber-500/20 text-amber-300 border border-amber-500/30' }}">
                        <span class="w-1.5 h-1.5 rounded-full {{ $business->status === 'active' ? 'bg-emerald-400 animate-pulse' : 'bg-amber-400' }}"></span>
                        {{ $business->status === 'active' ? 'Storefront Live' : 'Draft Mode' }}
                    </span>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold {{ $twoFactorEnabled ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' : 'bg-amber-500/20 text-amber-300 border border-amber-500/30' }}">
                        <span>{{ $twoFactorEnabled ? '🛡️ 2FA Enabled' : '⚠️ 2FA Disabled' }}</span>
                    </span>
                </div>
                <p class="text-xs text-slate-300">
                    Manage your SABAYRA storefront configuration, Telegram alerts, Two-Factor Authentication, login activity, active sessions, and account preferences.
                </p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2.5 shrink-0">
            @if ($business->slug)
                <a href="/{{ $business->slug }}" target="_blank"
                   class="inline-flex items-center gap-2 px-4 py-2 text-xs font-bold text-slate-900 bg-white hover:bg-orange-50 rounded-xl transition shadow-xs">
                    <svg class="w-4 h-4 text-orange-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                    </svg>
                    <span>Preview Storefront</span>
                </a>
            @endif
        </div>
    </div>

    <!-- Mobile / Tablet Horizontal Scrollable Tabs -->
    <div class="lg:hidden -mx-1 px-1 overflow-x-auto pb-1">
        <div class="inline-flex gap-2 min-w-full">
            @foreach ($navItems as $item)
                <button type="button"
                        @click="switchSection('{{ $item['id'] }}')"
                        :class="activeSection === '{{ $item['id'] }}'
                            ? 'bg-orange-600 text-white border-orange-600 shadow-sm'
                            : 'bg-white dark:bg-gray-800 text-slate-700 dark:text-slate-200 border-gray-200 dark:border-gray-700 hover:border-orange-400'"
                        class="flex items-center gap-2 px-3.5 py-2 rounded-xl border text-xs font-bold whitespace-nowrap transition">
                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">{!! $item['icon'] !!}</svg>
                    <span>{{ $item['label'] }}</span>
                </button>
            @endforeach
        </div>
    </div>

    <!-- Validation Error Summary (visible across tabs) -->
    @if ($errors->any() || $errors->updatePassword->any())
        <div class="p-4 rounded-2xl bg-rose-50 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-800/60 text-xs text-rose-800 dark:text-rose-200 space-y-1">
            <p class="font-bold">Please review the highlighted fields before saving:</p>
            <ul class="list-disc list-inside space-y-0.5">
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
                @foreach ($errors->updatePassword->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Main 2-Column SaaS Settings Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

        <!-- Desktop Sidebar Navigation -->
        <aside class="hidden lg:block lg:col-span-3 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700/70 rounded-2xl p-3 shadow-xs lg:sticky lg:top-20 lg:max-h-[calc(100vh-6rem)] lg:overflow-y-auto space-y-4">
            @php $groupedNav = collect($navItems)->groupBy('group'); @endphp
            @foreach ($groupedNav as $groupTitle => $items)
                <div class="space-y-1">
                    <p class="px-3 pt-1 pb-1 text-[10px] font-black uppercase tracking-wider text-slate-400 dark:text-slate-400">
                        {{ $groupTitle }}
                    </p>
                    @foreach ($items as $item)
                        <button type="button"
                                @click="switchSection('{{ $item['id'] }}')"
                                :class="activeSection === '{{ $item['id'] }}'
                                    ? 'bg-orange-600 text-white shadow-sm'
                                    : 'text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-gray-700/60'"
                                class="w-full text-left flex items-center justify-between gap-2.5 px-3 py-2.5 rounded-xl transition group">
                            <div class="flex items-start gap-2.5 min-w-0">
                                <svg class="w-4 h-4 mt-0.5 shrink-0"
                                     :class="activeSection === '{{ $item['id'] }}' ? 'text-white' : 'text-slate-400 group-hover:text-orange-500'"
                                     fill="none" viewBox="0 0 24 24" stroke="currentColor">{!! $item['icon'] !!}</svg>
                                <div class="min-w-0">
                                    <span class="block text-xs font-bold truncate">{{ $item['label'] }}</span>
                                    <span class="block text-[10px] truncate"
                                          :class="activeSection === '{{ $item['id'] }}' ? 'text-orange-100' : 'text-slate-500 dark:text-slate-400'">
                                        {{ $item['subtitle'] }}
                                    </span>
                                </div>
                            </div>
                            @if (!empty($item['badge']))
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold shrink-0 border"
                                      :class="activeSection === '{{ $item['id'] }}'
                                          ? 'bg-white/20 text-white border-white/30'
                                          : '{{ $item['badgeColor'] ?? 'bg-slate-100 dark:bg-gray-900 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-gray-700' }}'">
                                    {{ $item['badge'] }}
                                </span>
                            @endif
                        </button>
                    @endforeach
                </div>
            @endforeach
        </aside>

        <!-- Settings Active Panel -->
        <div class="lg:col-span-9 space-y-6">
            @if ($canManageBusinessSettings)
                <div x-show="activeSection === 'general'" x-cloak>
                    @include('business.settings.partials.general')
                </div>

                <div x-show="activeSection === 'business'" x-cloak>
                    @include('business.settings.partials.business-info')
                </div>

                <div x-show="activeSection === 'experience'" x-cloak>
                    @include('business.settings.partials.category-experience')
                </div>

                <div x-show="activeSection === 'payment'" x-cloak>
                    @include('business.settings.partials.payment-tax')
                </div>

                <div x-show="activeSection === 'notifications'" x-cloak>
                    @include('business.settings.partials.notifications')
                </div>
            @endif

            <div x-show="activeSection === 'security'" x-cloak>
                @include('business.settings.partials.security')
            </div>

            <div x-show="activeSection === 'account'" x-cloak>
                @include('business.settings.partials.account')
            </div>
        </div>
    </div>
</div>
</x-business-layout>
