<x-business-layout title="Menu Health Audit" heading="Automated Menu Audit">
<div class="space-y-6">

    <div class="p-5 bg-gradient-to-r from-emerald-950/40 via-gray-900 to-teal-950/30 border border-emerald-500/30 rounded-2xl shadow-xs text-white flex items-center justify-between">
        <div>
            <h3 class="text-base font-black text-white">Menu Health & Completeness Audit</h3>
            <p class="text-xs text-slate-300 mt-1">Scans your catalog for missing food photos, weak descriptions, unpriced dishes, and pricing outliers.</p>
        </div>
        <div class="text-center px-4 py-2 bg-emerald-500/20 border border-emerald-500/30 rounded-2xl">
            <span class="text-2xl font-black text-emerald-400">{{ $audit['health_score'] }}%</span>
            <p class="text-[10px] text-slate-300 font-bold uppercase">Health Score</p>
        </div>
    </div>

    <!-- Audit Stat Chips -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="p-4 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-xl text-center">
            <p class="text-xl font-black text-amber-500">{{ $audit['missing_images_count'] }}</p>
            <p class="text-xs text-slate-400 mt-1">Missing Images</p>
        </div>
        <div class="p-4 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-xl text-center">
            <p class="text-xl font-black text-blue-500">{{ $audit['weak_descriptions_count'] }}</p>
            <p class="text-xs text-slate-400 mt-1">Short Descriptions</p>
        </div>
        <div class="p-4 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-xl text-center">
            <p class="text-xl font-black text-red-500">{{ $audit['missing_prices_count'] }}</p>
            <p class="text-xs text-slate-400 mt-1">$0.00 Prices</p>
        </div>
        <div class="p-4 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-xl text-center">
            <p class="text-xl font-black text-purple-500">{{ $audit['pricing_outliers_count'] }}</p>
            <p class="text-xs text-slate-400 mt-1">Pricing Outliers</p>
        </div>
    </div>

    <!-- Recommendations -->
    <div class="p-6 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-2xl shadow-xs space-y-4">
        <h4 class="text-sm font-bold text-gray-900 dark:text-white">Audit Recommendations</h4>

        <div class="space-y-3">
            @foreach ($audit['recommendations'] as $rec)
                <div class="p-4 rounded-xl bg-slate-50 dark:bg-gray-900/50 border border-gray-200/60 dark:border-gray-700/40 text-xs">
                    <p class="font-bold text-gray-900 dark:text-white">{{ $rec['title'] }}</p>
                    <p class="text-slate-600 dark:text-slate-300 mt-1">{{ $rec['description'] }}</p>
                </div>
            @endforeach
        </div>

        <div class="pt-4 flex justify-end">
            <a href="{{ route('business.products.index') }}" class="px-5 py-2 bg-orange-600 hover:bg-orange-500 text-white rounded-xl text-xs font-bold shadow-xs">
                Edit Catalog Products &rarr;
            </a>
        </div>
    </div>

</div>
</x-business-layout>
