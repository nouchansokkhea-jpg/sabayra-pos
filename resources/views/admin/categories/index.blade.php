<x-admin-layout title="Platform Categories - Super Admin" heading="Business Sectors & Presets">
<div class="space-y-6">
    <div class="p-5 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-xs">
        <h3 class="text-base font-bold text-slate-900 dark:text-white">Supported Business Sectors in Cambodia</h3>
        <p class="text-xs text-slate-600 dark:text-slate-300 font-medium mt-1">
            SABAYRA comes out-of-the-box pre-configured for these Cambodian F&B and Retail industries, including bilingual labels and operational workflows (Dine-in, Takeaway, KDS, and Inventory).
        </p>
    </div>

    <!-- Categories Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        @foreach($businessTypes as $key => $type)
            @php
                $total = $typeCounts[$key] ?? 0;
                $active = $activeCounts[$key] ?? 0;
            @endphp
            <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-xs hover:shadow-md transition flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between">
                        <span class="w-10 h-10 rounded-xl bg-orange-50 text-orange-600 dark:bg-orange-950/40 dark:text-orange-400 font-bold flex items-center justify-center text-lg">
                            🏷️
                        </span>
                        <x-badge color="green">Active Sector</x-badge>
                    </div>

                    <h4 class="font-bold text-base text-slate-900 dark:text-white mt-3">{{ $type['name_en'] }}</h4>
                    <p class="text-xs text-slate-500 dark:text-slate-400 font-khmer font-bold mt-0.5">{{ $type['name_km'] }}</p>
                    <p class="text-xs text-slate-600 dark:text-slate-300 font-medium mt-2 leading-relaxed">
                        {{ $type['description'] ?? 'Optimized menu structure, QR standee layout, and point-of-sale workflow.' }}
                    </p>
                </div>

                <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-700/80 flex items-center justify-between text-xs">
                    <span class="text-slate-500 dark:text-slate-400 font-medium">Businesses on Platform:</span>
                    <span class="font-bold font-mono text-slate-900 dark:text-white bg-slate-100 dark:bg-gray-700 px-2 py-0.5 rounded-lg">
                        {{ $total }} registered ({{ $active }} active)
                    </span>
                </div>
            </div>
        @endforeach
    </div>
</div>
</x-admin-layout>
