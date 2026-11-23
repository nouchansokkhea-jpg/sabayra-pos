<x-business-layout title="Translation Manager" heading="Translation Manager (English & Khmer)">
<div class="space-y-6">

    @if (session('status'))
        <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-xs font-bold text-emerald-800 dark:text-emerald-200">
            {{ session('status') }}
        </div>
    @endif

    <!-- Translation Status Banner -->
    <div class="p-5 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-2xl shadow-xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h3 class="text-sm font-bold text-gray-900 dark:text-white">Dual English & Khmer Content</h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                Provide bilingual descriptions so Cambodian guests and international tourists can both browse effortlessly.
            </p>
        </div>
        <div class="flex items-center space-x-2 text-xs font-bold">
            <span class="px-3 py-1.5 rounded-xl bg-amber-500/10 text-amber-500 border border-amber-500/20">
                {{ $missingKmProductsCount }} Products missing Khmer
            </span>
        </div>
    </div>

    <!-- Product Translations Form -->
    <form method="POST" action="{{ route('business.translations.update') }}">
        @csrf
        @method('PUT')

        <div class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-2xl shadow-xs overflow-hidden">
            <div class="p-4 border-b border-gray-100 dark:border-gray-700/60 flex items-center justify-between">
                <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider">Product Name & Descriptions</h4>
                <button type="submit" class="px-4 py-2 bg-orange-600 hover:bg-orange-500 text-white rounded-xl text-xs font-bold shadow-xs transition">
                    Save All Translations
                </button>
            </div>

            <div class="divide-y divide-gray-100 dark:divide-gray-700/60">
                @foreach ($products as $prod)
                    <div class="p-4 grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                        <!-- English Column -->
                        <div class="p-3 bg-slate-50 dark:bg-gray-900/50 rounded-xl">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">English</span>
                            <p class="text-sm font-bold text-gray-900 dark:text-white mt-1">{{ $prod->name_en }}</p>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">{{ $prod->description_en ?: 'No description' }}</p>
                        </div>

                        <!-- Khmer Translation Inputs -->
                        <div class="space-y-2">
                            <div>
                                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Khmer Name (ឈ្មោះជាភាសាខ្មែរ)</label>
                                <input type="text"
                                       name="products[{{ $prod->id }}][name_km]"
                                       value="{{ old("products.{$prod->id}.name_km", $prod->name_km) }}"
                                       placeholder="ឧទាហរណ៍៖ បាយឆាសាច់គោ"
                                       class="mt-1 w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-3 py-2 text-xs text-gray-900 dark:text-white font-sans" />
                            </div>

                            <div>
                                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Khmer Description (ការពិពណ៌នា)</label>
                                <textarea name="products[{{ $prod->id }}][description_km]"
                                          rows="2"
                                          placeholder="ព័ត៌មានលម្អិតអំពីមុខម្ហូប..."
                                          class="mt-1 w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-3 py-2 text-xs text-gray-900 dark:text-white font-sans">{{ old("products.{$prod->id}.description_km", $prod->description_km) }}</textarea>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="p-4 border-t border-gray-100 dark:border-gray-700/60 flex justify-end">
                <button type="submit" class="px-6 py-2.5 bg-orange-600 hover:bg-orange-500 text-white rounded-xl text-xs font-bold shadow-xs transition">
                    Save All Translations
                </button>
            </div>
        </div>
    </form>

</div>
</x-business-layout>
