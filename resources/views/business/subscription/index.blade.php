<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                    {{ __('Subscription & Plan Entitlements') }}
                </h2>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                    {{ __('View your active business plan, feature entitlements, and quota status.') }}
                </p>
            </div>
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300">
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                {{ __('Community Free Tier Active') }}
            </span>
        </div>
    </x-slot>

    <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        <!-- Active Status Banner -->
        <div class="bg-gradient-to-r from-emerald-800 via-teal-900 to-zinc-900 rounded-2xl p-6 text-white shadow-lg relative overflow-hidden">
            <div class="max-w-2xl relative z-10">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-500/30 text-emerald-200 border border-emerald-400/30 mb-3">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    {{ __('All Features Unlocked') }}
                </div>
                <h3 class="text-xl font-bold mb-2">{{ __('100% Free For Cambodian Businesses') }}</h3>
                <p class="text-sm text-emerald-100/80 leading-relaxed">
                    {{ __('Your store currently enjoys full, unrestricted access to all SABAYRA capabilities: Unlimited Products, Touch POS, Kitchen Display System (KDS), Bakong KHQR, AI Weekly Insights, Multi-Branch Management, and Thermal Printing.') }}
                </p>
            </div>
        </div>

        <!-- Entitlement Cards Grid -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700/60 rounded-2xl p-5 shadow-sm">
                <span class="text-xs font-semibold text-zinc-400 uppercase tracking-wider">{{ __('Products & Menu') }}</span>
                <div class="mt-2 text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ __('Unlimited') }}</div>
                <p class="text-xs text-emerald-600 mt-1 flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    {{ __('No SKU limit') }}
                </p>
            </div>

            <div class="bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700/60 rounded-2xl p-5 shadow-sm">
                <span class="text-xs font-semibold text-zinc-400 uppercase tracking-wider">{{ __('Monthly Orders') }}</span>
                <div class="mt-2 text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ __('Unlimited') }}</div>
                <p class="text-xs text-emerald-600 mt-1 flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    {{ __('0% commission') }}
                </p>
            </div>

            <div class="bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700/60 rounded-2xl p-5 shadow-sm">
                <span class="text-xs font-semibold text-zinc-400 uppercase tracking-wider">{{ __('Branch Outlets') }}</span>
                <div class="mt-2 text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ __('Unlimited') }}</div>
                <p class="text-xs text-emerald-600 mt-1 flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    {{ __('Multi-location') }}
                </p>
            </div>

            <div class="bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700/60 rounded-2xl p-5 shadow-sm">
                <span class="text-xs font-semibold text-zinc-400 uppercase tracking-wider">{{ __('Staff Accounts') }}</span>
                <div class="mt-2 text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ __('Unlimited') }}</div>
                <p class="text-xs text-emerald-600 mt-1 flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    {{ __('Custom RBAC roles') }}
                </p>
            </div>
        </div>

        @if ($plansEnabled && $availablePlans->isNotEmpty())
            <!-- Renderable when restored with secret pin keyword -->
            <div class="bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700/60 rounded-2xl p-6 shadow-sm">
                <h3 class="text-lg font-bold text-zinc-900 dark:text-zinc-100 mb-4">{{ __('Available Subscription Tiers') }}</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    @foreach ($availablePlans as $plan)
                        <div class="p-6 rounded-2xl border border-zinc-200 dark:border-zinc-700 flex flex-col justify-between">
                            <div>
                                <h4 class="text-lg font-bold text-zinc-900 dark:text-zinc-100">{{ $plan->name }}</h4>
                                <div class="mt-2 text-3xl font-extrabold text-orange-600">${{ number_format($plan->price, 2) }}<span class="text-xs font-normal text-zinc-400">/mo</span></div>
                                <p class="text-xs text-zinc-500 mt-2">{{ $plan->description }}</p>
                            </div>
                            <button class="mt-6 w-full py-2 px-4 rounded-xl text-xs font-semibold bg-orange-600 text-white hover:bg-orange-700 transition">
                                {{ __('Select Plan') }}
                            </button>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
