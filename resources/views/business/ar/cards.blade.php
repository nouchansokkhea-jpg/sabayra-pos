<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                    {{ __('AR Table Cards & Experiences') }}
                </h2>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                    {{ __('Connect physical tabletop QR cards with interactive augmented reality web experiences for your guests.') }}
                </p>
            </div>
            <a href="{{ route('business.ar.scan-assistant') }}" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-purple-700 bg-purple-50 hover:bg-purple-100 dark:bg-purple-950/40 dark:text-purple-300 rounded-xl transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-2 1m2-1l-2-1m2 1v2.5M14 4l-2-1-2 1M4 7l2-1M4 7l2 1M4 7v2.5M12 21l-2-1m2 1l2-1m-2 1v-2.5M6 18l-2-1v-2.5M18 18l2-1v-2.5"/></svg>
                {{ __('3D Scan Assistant') }}
            </a>
        </div>
    </x-slot>

    <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        <!-- Overview Banner -->
        <div class="bg-gradient-to-r from-purple-900 via-indigo-900 to-zinc-900 rounded-2xl p-6 text-white shadow-lg relative overflow-hidden">
            <div class="max-w-2xl relative z-10">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-purple-500/30 text-purple-200 border border-purple-400/30 mb-3">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    {{ __('Next-Gen Dining Experience') }}
                </div>
                <h3 class="text-xl font-bold mb-2">{{ __('Web-Based Augmented Reality (No App Required)') }}</h3>
                <p class="text-sm text-purple-200/80 leading-relaxed">
                    {{ __('When guests scan your AR Table Card on iOS Safari or Android Chrome, they can view interactive 3D dishes, special recommendations, and chef highlights rendered directly on their dining table.') }}
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Active AR Featured Highlights -->
            <div class="md:col-span-2 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700/60 rounded-2xl p-6 shadow-sm">
                <h3 class="text-lg font-bold text-zinc-900 dark:text-zinc-100 mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
                    {{ __('Featured Menu Dishes in AR') }}
                </h3>

                @if ($featuredProducts->isEmpty())
                    <div class="py-8 text-center text-zinc-500 dark:text-zinc-400">
                        <p class="text-sm">{{ __('No featured products configured for AR spotlight yet.') }}</p>
                        <p class="text-xs text-zinc-400 mt-1">{{ __('Mark products as "Featured" in Catalog & Menu to showcase them on tabletop AR cards.') }}</p>
                        <a href="{{ route('business.products.index') }}" class="mt-3 inline-block text-xs font-semibold text-purple-600 hover:text-purple-500">
                            {{ __('Manage Products') }} &rarr;
                        </a>
                    </div>
                @else
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        @foreach ($featuredProducts as $item)
                            <div class="p-4 rounded-xl border border-zinc-200 dark:border-zinc-700 flex items-center gap-3">
                                <div class="w-14 h-14 rounded-lg bg-zinc-100 dark:bg-zinc-700 overflow-hidden flex-shrink-0">
                                    @if ($item->image_url)
                                        <img src="{{ $item->image_url }}" alt="{{ $item->name }}" class="w-full h-full object-cover">
                                    @else
                                        <div class="w-full h-full flex items-center justify-center text-zinc-400 text-xs font-bold">3D</div>
                                    @endif
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h4 class="text-sm font-bold text-zinc-900 dark:text-zinc-100 truncate">{{ $item->name }}</h4>
                                    <p class="text-xs text-emerald-600 font-semibold">${{ number_format($item->price, 2) }}</p>
                                    <span class="inline-flex items-center text-[10px] text-purple-600 dark:text-purple-400 font-medium mt-1">
                                        {{ __('AR Spotlight Active') }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Table Card Template Preview -->
            <div class="bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700/60 rounded-2xl p-6 shadow-sm flex flex-col justify-between">
                <div>
                    <h3 class="text-base font-bold text-zinc-900 dark:text-zinc-100 mb-2">{{ __('Table Card Design') }}</h3>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-4">
                        {{ __('Modern standee template with instant camera detection QR code.') }}
                    </p>
                    <div class="border-2 border-dashed border-purple-200 dark:border-zinc-700 rounded-xl p-6 text-center bg-purple-50/50 dark:bg-zinc-900/50">
                        <div class="w-20 h-20 bg-white rounded-xl shadow-md mx-auto mb-3 flex items-center justify-center p-2">
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data={{ urlencode(url('/' . $business->slug . '?ar=1')) }}" alt="AR Preview QR" class="w-full h-full">
                        </div>
                        <h4 class="text-xs font-bold text-zinc-800 dark:text-zinc-200">{{ $business->name }}</h4>
                        <p class="text-[11px] text-purple-600 font-semibold mt-1">{{ __('Scan to view 3D Menu') }}</p>
                    </div>
                </div>

                <div class="pt-4">
                    <button onclick="window.print()" class="w-full py-2.5 px-4 bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 font-medium rounded-xl text-xs hover:opacity-90 transition">
                        {{ __('Print AR Standee Badges') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
