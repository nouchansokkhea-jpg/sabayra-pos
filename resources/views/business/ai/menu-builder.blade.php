<x-business-layout title="AI Menu Builder" heading="AI Guided Menu Builder">
<div class="space-y-6" x-data="{
    step: 1,
    concept: 'Authentic Khmer street food and grill specialties in Phnom Penh',
    generatedItems: [
        { category: 'Signature Grills', name_en: 'Grilled Beef Skewers with Tuk Prahok', name_km: 'សាច់គោអាំងទឹកប្រហុក', price: '4.50', description_en: 'Marinated tender lemongrass beef skewers with spicy fermented dipping sauce' },
        { category: 'Signature Grills', name_en: 'Crispy Stuffed Chicken Wings', name_km: 'ស្លាបមាន់ញាត់សាច់', price: '3.75', description_en: 'Crisp roasted chicken wings stuffed with seasoned minced pork and herbs' },
        { category: 'Traditional Soups', name_en: 'Samlor Kako Khmer Stew', name_km: 'សម្លកកូរប្រពៃណី', price: '5.00', description_en: 'Classic aromatic vegetable and fish stew with toasted rice powder' },
        { category: 'Drinks', name_en: 'Sugar Cane Juice with Passionfruit', name_km: 'ទឹកអំពៅផាសិន', price: '1.50', description_en: 'Cold pressed sugarcane with zesty local passionfruit and crushed ice' }
    ]
}">

    <div class="p-5 bg-gradient-to-r from-orange-950/40 via-gray-900 to-amber-950/30 border border-orange-500/30 rounded-2xl shadow-xs text-white">
        <div class="flex items-center space-x-3">
            <span class="text-3xl">✨</span>
            <div>
                <h3 class="text-base font-black text-white">Interactive AI Menu Builder</h3>
                <p class="text-xs text-slate-300 mt-1">Accelerate catalog creation by generating industry-standard dishes, categories, and bilingual descriptions in seconds.</p>
            </div>
        </div>
    </div>

    <!-- Step 1: Input Concept -->
    <div class="p-6 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-2xl shadow-xs">
        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">
            Describe Your Business Style & Specialties
        </label>
        <textarea x-model="concept" rows="3" class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 p-3 text-xs text-gray-900 dark:text-white"></textarea>
        
        <p class="text-[11px] text-slate-400 mt-2">
            Tip: Specify your style (e.g. Italian wood-fired pizza, BKK coffee shop, Milk Tea dessert bar, Korean BBQ).
        </p>
    </div>

    <!-- Step 2: Review & Approve Generated Items -->
    <form method="POST" action="{{ route('business.ai.menu-builder.store') }}">
        @csrf

        <div class="p-6 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-2xl shadow-xs space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-700/60">
                <h4 class="text-sm font-bold text-gray-900 dark:text-white">Review & Edit Generated Items Before Saving</h4>
                <span class="text-xs font-mono font-bold text-orange-400 bg-orange-500/10 px-2 py-0.5 rounded-full">
                    <span x-text="generatedItems.length"></span> Items Proposed
                </span>
            </div>

            <div class="space-y-4">
                <template x-for="(item, index) in generatedItems" :key="index">
                    <div class="p-4 rounded-2xl bg-slate-50 dark:bg-gray-900/50 border border-gray-200/60 dark:border-gray-700/40 grid grid-cols-1 sm:grid-cols-12 gap-3 text-xs">
                        <div class="sm:col-span-3">
                            <label class="text-[10px] font-bold text-slate-400 uppercase">Category</label>
                            <input type="text" :name="'items[' + index + '][category]'" x-model="item.category" class="mt-1 w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-1.5 text-xs text-gray-900 dark:text-white font-bold" />
                        </div>
                        <div class="sm:col-span-4">
                            <label class="text-[10px] font-bold text-slate-400 uppercase">Item Name (EN)</label>
                            <input type="text" :name="'items[' + index + '][name_en]'" x-model="item.name_en" class="mt-1 w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-1.5 text-xs text-gray-900 dark:text-white font-bold" />
                        </div>
                        <div class="sm:col-span-3">
                            <label class="text-[10px] font-bold text-slate-400 uppercase">Khmer (KM)</label>
                            <input type="text" :name="'items[' + index + '][name_km]'" x-model="item.name_km" class="mt-1 w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-1.5 text-xs text-gray-900 dark:text-white" />
                        </div>
                        <div class="sm:col-span-2">
                            <label class="text-[10px] font-bold text-slate-400 uppercase">Price ($)</label>
                            <input type="number" step="0.01" :name="'items[' + index + '][price]'" x-model="item.price" class="mt-1 w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-1.5 text-xs text-gray-900 dark:text-white font-mono font-bold" />
                        </div>
                        <div class="sm:col-span-12">
                            <label class="text-[10px] font-bold text-slate-400 uppercase">Description</label>
                            <input type="text" :name="'items[' + index + '][description_en]'" x-model="item.description_en" class="mt-1 w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-1.5 text-xs text-gray-900 dark:text-white" />
                        </div>
                    </div>
                </template>
            </div>

            <div class="pt-4 border-t border-gray-100 dark:border-gray-700/60 flex items-center justify-between">
                <button type="button" @click="generatedItems.push({ category: 'Specials', name_en: 'New Dish', name_km: '', price: '3.00', description_en: '' })" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-slate-700 dark:text-slate-300 text-xs font-bold rounded-xl transition cursor-pointer">
                    + Add Another Item
                </button>
                <button type="submit" class="px-6 py-2.5 bg-orange-600 hover:bg-orange-500 text-white text-xs font-bold rounded-xl shadow-xs transition cursor-pointer">
                    Approve & Save To Catalog
                </button>
            </div>
        </div>
    </form>

</div>
</x-business-layout>
