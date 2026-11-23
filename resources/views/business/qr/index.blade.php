<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                    {{ __('QR Code Studio & Branding') }}
                </h2>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                    {{ __('Generate, customize, and print QR codes for your store storefront, tables, and branches.') }}
                </p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ $publicMenuUrl }}" target="_blank" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-orange-600 bg-orange-50 hover:bg-orange-100 dark:bg-orange-950/40 dark:text-orange-400 rounded-xl transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    {{ __('Open Storefront') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        @if (session('status'))
            <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 dark:bg-emerald-950/30 dark:border-emerald-800 dark:text-emerald-300">
                {{ session('status') }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left: Customizer & Settings -->
            <div class="lg:col-span-1 space-y-6">
                <div class="bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700/60 rounded-2xl p-6 shadow-sm">
                    <h3 class="text-lg font-bold text-zinc-900 dark:text-zinc-100 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/></svg>
                        {{ __('Design & Branding') }}
                    </h3>
                    <form method="POST" action="{{ route('business.qr.update-settings') }}" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-500 mb-1">{{ __('QR Primary Color') }}</label>
                            <div class="flex items-center gap-3">
                                <input type="color" name="color" value="{{ $qrSettings['color'] ?? '#f97316' }}" class="h-10 w-14 rounded-lg border border-zinc-300 dark:border-zinc-600 cursor-pointer">
                                <input type="text" name="color_hex" value="{{ $qrSettings['color'] ?? '#f97316' }}" readonly class="w-full text-xs font-mono rounded-lg border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900 text-zinc-600 dark:text-zinc-300">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-500 mb-1">{{ __('Background Color') }}</label>
                            <div class="flex items-center gap-3">
                                <input type="color" name="background" value="{{ $qrSettings['background'] ?? '#ffffff' }}" class="h-10 w-14 rounded-lg border border-zinc-300 dark:border-zinc-600 cursor-pointer">
                                <input type="text" name="bg_hex" value="{{ $qrSettings['background'] ?? '#ffffff' }}" readonly class="w-full text-xs font-mono rounded-lg border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900 text-zinc-600 dark:text-zinc-300">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-500 mb-1">{{ __('Frame Layout') }}</label>
                            <select name="frame" class="w-full text-sm rounded-xl border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900 text-zinc-800 dark:text-zinc-200">
                                <option value="minimal" {{ ($qrSettings['frame'] ?? '') === 'minimal' ? 'selected' : '' }}>{{ __('Minimal (Just QR)') }}</option>
                                <option value="badge" {{ ($qrSettings['frame'] ?? '') === 'badge' ? 'selected' : '' }}>{{ __('Scan Me Badge') }}</option>
                                <option value="standee" {{ ($qrSettings['frame'] ?? '') === 'standee' ? 'selected' : '' }}>{{ __('Acrylic Table Standee') }}</option>
                            </select>
                        </div>

                        <div class="flex items-center justify-between pt-2">
                            <span class="text-xs font-medium text-zinc-700 dark:text-zinc-300">{{ __('Embed Business Logo') }}</span>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="show_logo" value="1" {{ !empty($qrSettings['show_logo']) ? 'checked' : '' }} class="sr-only peer">
                                <div class="w-9 h-5 bg-zinc-200 peer-focus:outline-none rounded-full peer dark:bg-zinc-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-zinc-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-orange-500"></div>
                            </label>
                        </div>

                        <button type="submit" class="w-full mt-4 py-2.5 px-4 bg-orange-600 hover:bg-orange-700 text-white font-medium rounded-xl text-sm shadow-sm transition">
                            {{ __('Save QR Styling') }}
                        </button>
                    </form>
                </div>

                <!-- Print Specifications -->
                <div class="bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700/60 rounded-2xl p-6 shadow-sm">
                    <h3 class="text-sm font-bold text-zinc-900 dark:text-zinc-100 mb-3 flex items-center gap-2">
                        <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        {{ __('Recommended Print Sizes') }}
                    </h3>
                    <ul class="text-xs text-zinc-500 dark:text-zinc-400 space-y-2">
                        <li class="flex items-center justify-between border-b border-zinc-100 dark:border-zinc-700/50 pb-1.5">
                            <span>{{ __('Table Stickers') }}</span>
                            <span class="font-medium text-zinc-700 dark:text-zinc-300">50mm × 50mm</span>
                        </li>
                        <li class="flex items-center justify-between border-b border-zinc-100 dark:border-zinc-700/50 pb-1.5">
                            <span>{{ __('Acrylic Standee (A6)') }}</span>
                            <span class="font-medium text-zinc-700 dark:text-zinc-300">105mm × 148mm</span>
                        </li>
                        <li class="flex items-center justify-between pb-1">
                            <span>{{ __('Counter Poster (A4)') }}</span>
                            <span class="font-medium text-zinc-700 dark:text-zinc-300">210mm × 297mm</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Right: Preview and Codes Cards -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Main Storefront QR Card -->
                <div class="bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700/60 rounded-2xl p-6 shadow-sm">
                    <div class="flex flex-col sm:flex-row items-center gap-6">
                        <div class="p-4 bg-white rounded-2xl border-2 border-dashed border-orange-200 dark:border-zinc-700 shadow-inner flex flex-col items-center">
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data={{ urlencode($publicMenuUrl) }}&color=000000" alt="Main Storefront QR" class="w-44 h-44 rounded-lg">
                            <span class="text-[11px] font-semibold text-zinc-500 uppercase tracking-wider mt-2">{{ __('Scan For Storefront') }}</span>
                        </div>
                        <div class="flex-1 space-y-3 text-center sm:text-left">
                            <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-orange-100 text-orange-800 dark:bg-orange-950/40 dark:text-orange-300">
                                {{ __('Public Storefront QR') }}
                            </div>
                            <h3 class="text-xl font-bold text-zinc-900 dark:text-zinc-100">{{ $business->name }}</h3>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                {{ __('Customers can scan this code to browse products, view prices in USD/KHR, and place pickup or delivery orders.') }}
                            </p>
                            <div class="p-3 bg-zinc-50 dark:bg-zinc-900 rounded-xl border border-zinc-200 dark:border-zinc-700 text-xs font-mono text-zinc-600 dark:text-zinc-300 break-all">
                                {{ $publicMenuUrl }}
                            </div>
                            <div class="flex flex-wrap items-center gap-2 pt-1">
                                <a href="https://api.qrserver.com/v1/create-qr-code/?size=800x800&data={{ urlencode($publicMenuUrl) }}" download="{{ $business->slug }}-qr.png" target="_blank" class="px-4 py-2 bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900 font-medium rounded-xl text-xs hover:opacity-90 transition">
                                    {{ __('Download High-Res PNG') }}
                                </a>
                                <button onclick="window.print()" class="px-4 py-2 border border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 rounded-xl text-xs font-medium hover:bg-zinc-50 dark:hover:bg-zinc-700 transition">
                                    {{ __('Print Poster') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Table QR Codes (If tables exist) -->
                <div class="bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700/60 rounded-2xl p-6 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-base font-bold text-zinc-900 dark:text-zinc-100">{{ __('Dine-In Table QR Codes') }}</h3>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Unique codes mapped to specific tables for contactless dine-in ordering.') }}</p>
                        </div>
                        <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300">
                            {{ $tables->count() }} {{ __('Tables') }}
                        </span>
                    </div>

                    @if ($tables->isEmpty())
                        <div class="py-8 text-center text-zinc-500 dark:text-zinc-400">
                            <p class="text-sm">{{ __('No tables registered for this business yet.') }}</p>
                            <a href="{{ route('business.tables.index') }}" class="mt-2 inline-block text-xs font-semibold text-orange-600 hover:text-orange-500">
                                + {{ __('Add Tables in Operations') }}
                            </a>
                        </div>
                    @else
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                            @foreach ($tables as $table)
                                @php
                                    $tableUrl = $publicMenuUrl . '?table=' . urlencode($table->table_number);
                                @endphp
                                <div class="p-3 bg-zinc-50 dark:bg-zinc-900/60 rounded-xl border border-zinc-200 dark:border-zinc-700 text-center flex flex-col items-center">
                                    <div class="w-24 h-24 bg-white p-1 rounded-lg border border-zinc-200 dark:border-zinc-700 mb-2">
                                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=120x120&data={{ urlencode($tableUrl) }}" alt="Table {{ $table->table_number }}" class="w-full h-full">
                                    </div>
                                    <span class="text-xs font-bold text-zinc-900 dark:text-zinc-100">Table {{ $table->table_number }}</span>
                                    <span class="text-[10px] text-zinc-400">{{ $table->capacity }} {{ __('Seats') }}</span>
                                    <a href="https://api.qrserver.com/v1/create-qr-code/?size=600x600&data={{ urlencode($tableUrl) }}" download="Table-{{ $table->table_number }}-QR.png" target="_blank" class="mt-2 text-[11px] text-orange-600 dark:text-orange-400 font-semibold hover:underline">
                                        {{ __('Download') }}
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
