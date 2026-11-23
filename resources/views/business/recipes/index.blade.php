<x-business-layout title="Recipes & Ingredients" heading="Recipes & Ingredient Yields">
<div class="space-y-6" x-data="{ addModal: false }">

    @if (session('status'))
        <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-xs font-bold text-emerald-800 dark:text-emerald-200">
            {{ session('status') }}
        </div>
    @endif

    <div class="p-4 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-2xl shadow-xs flex items-center justify-between">
        <div>
            <h3 class="text-sm font-bold text-gray-900 dark:text-white">Recipe Formulas & Auto-Stock Deduction</h3>
            <p class="text-xs text-slate-400">Link menu dishes to raw inventory items so stocks deduct automatically as orders complete.</p>
        </div>
        <button @click="addModal = true" class="px-4 py-2 bg-orange-600 hover:bg-orange-500 text-white rounded-xl text-xs font-bold shadow-xs transition">
            + Create Recipe Formula
        </button>
    </div>

    <!-- Recipes List -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse ($recipes as $rec)
            <div class="p-5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-xs space-y-3">
                <div class="flex items-center justify-between">
                    <h4 class="text-sm font-black text-gray-900 dark:text-white">{{ $rec->product ? $rec->product->name_en : 'Product' }}</h4>
                    <span class="text-[10px] font-mono font-bold px-2 py-0.5 rounded-full bg-slate-100 dark:bg-gray-700 text-slate-500">
                        Yield: {{ (int) $rec->yield_quantity }} portion
                    </span>
                </div>

                <div class="text-xs space-y-1.5 pt-2 border-t border-gray-100 dark:border-gray-700/60">
                    <span class="text-[10px] font-bold text-slate-400 uppercase">Ingredients Deducted:</span>
                    @foreach ($rec->items as $it)
                        <div class="flex items-center justify-between text-slate-600 dark:text-slate-300">
                            <span>{{ $it->inventoryItem ? $it->inventoryItem->name_en : 'Ingredient' }}</span>
                            <span class="font-mono font-bold">{{ (float) $it->quantity }} {{ $it->unit }}</span>
                        </div>
                    @endforeach
                </div>

                <div class="pt-3 border-t border-gray-100 dark:border-gray-700/60 flex justify-end">
                    <form method="POST" action="{{ route('business.recipes.destroy', $rec) }}" onsubmit="return confirm('Delete this recipe formula?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-xs text-red-500 hover:text-red-600">Delete Formula</button>
                    </form>
                </div>
            </div>
        @empty
            <div class="col-span-full p-12 text-center text-xs text-slate-400 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700/60">
                No recipes linked yet. Create a formula to enable automatic raw ingredient deductions!
            </div>
        @endforelse
    </div>

    <!-- Add Recipe Modal -->
    <div x-show="addModal" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/80 p-4" style="display: none;">
        <div @click.away="addModal = false" class="bg-white dark:bg-gray-800 rounded-3xl max-w-lg w-full p-6 border border-gray-700 shadow-2xl">
            <h3 class="text-base font-black text-gray-900 dark:text-white">Create Recipe Formula</h3>

            <form method="POST" action="{{ route('business.recipes.store') }}" class="mt-4 space-y-3 text-xs" x-data="{
                items: [{ inventory_item_id: '', quantity: '1', unit: 'g' }]
            }">
                @csrf
                <div>
                    <label class="block font-bold text-slate-500 mb-1">Select Menu Product</label>
                    <select name="product_id" required class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 p-2.5 text-xs text-gray-900 dark:text-white font-bold">
                        @foreach ($products as $p)
                            <option value="{{ $p->id }}">{{ $p->name_en }} ({{ $p->category ? $p->category->name_en : 'General' }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block font-bold text-slate-500 mb-1">Portion Yield</label>
                    <input type="number" step="0.1" name="yield_quantity" value="1.0" required class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 p-2.5 text-xs text-gray-900 dark:text-white font-mono font-bold" />
                </div>

                <div class="pt-2">
                    <label class="block font-bold text-slate-500 mb-2">Ingredients Formula</label>
                    <template x-for="(it, idx) in items" :key="idx">
                        <div class="flex items-center gap-2 mb-2">
                            <select :name="'items[' + idx + '][inventory_item_id]'" x-model="it.inventory_item_id" required class="flex-1 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 p-2 text-xs text-gray-900 dark:text-white">
                                <option value="">Select Raw Item</option>
                                @foreach ($inventoryItems as $inv)
                                    <option value="{{ $inv->id }}">{{ $inv->name_en }} ({{ $inv->unit }})</option>
                                @endforeach
                            </select>
                            <input type="number" step="0.001" :name="'items[' + idx + '][quantity]'" x-model="it.quantity" placeholder="Qty" required class="w-20 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 p-2 text-xs text-gray-900 dark:text-white font-mono font-bold" />
                            <input type="text" :name="'items[' + idx + '][unit]'" x-model="it.unit" placeholder="Unit" required class="w-16 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 p-2 text-xs text-gray-900 dark:text-white" />
                            <button type="button" @click="items.splice(idx, 1)" class="text-red-500 font-bold px-1" x-show="items.length > 1">&times;</button>
                        </div>
                    </template>
                    <button type="button" @click="items.push({ inventory_item_id: '', quantity: '1', unit: 'g' })" class="text-xs font-bold text-orange-500 hover:text-orange-600 mt-1">
                        + Add Another Ingredient
                    </button>
                </div>

                <div class="mt-5 pt-3 border-t border-gray-100 dark:border-gray-700/60 flex justify-end gap-2">
                    <button type="button" @click="addModal = false" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-xl text-xs font-bold">Cancel</button>
                    <button type="submit" class="px-5 py-2 bg-orange-600 hover:bg-orange-500 text-white rounded-xl text-xs font-bold shadow-xs">Save Recipe</button>
                </div>
            </form>
        </div>
    </div>

</div>
</x-business-layout>
