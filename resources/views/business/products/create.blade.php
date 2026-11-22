<x-business-layout title="Create Product" heading="Add New Product">
<div class="max-w-4xl p-6 bg-white border border-gray-100 rounded-2xl shadow-xs dark:bg-gray-800 dark:border-gray-700/60"
     x-data="{
         trackInventory: {{ old('track_inventory') ? 'true' : 'false' }},
         variants: []
     }">
    <form method="POST" action="{{ route('business.products.store') }}" class="space-y-8">
        @csrf

        <!-- Section 1: Basic Information -->
        <div>
            <h3 class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider mb-4 pb-2 border-b border-gray-100 dark:border-gray-700">1. Product Identity</h3>
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">Product Name (English) *</label>
                    <input type="text" name="name_en" value="{{ old('name_en') }}" required
                           placeholder="e.g. Traditional Fish Amok, Iced Latte, Silk Scarf..."
                           class="w-full text-sm rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3.5 py-2.5 focus:border-orange-500 focus:ring-orange-500">
                    @error('name_en') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">Product Name (Khmer) - ឈ្មោះជាភាសាខ្មែរ</label>
                    <input type="text" name="name_km" value="{{ old('name_km') }}"
                           placeholder="ឧ. អាម៉ុកត្រីបុរាណ, កាហ្វេឡាតេទឹកកក..."
                           class="w-full text-sm rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3.5 py-2.5 font-khmer focus:border-orange-500 focus:ring-orange-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">Category *</label>
                    <select name="category_id" class="w-full text-xs rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3.5 py-2.5 focus:border-orange-500 focus:ring-orange-500">
                        <option value="">Select Category</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}" {{ old('category_id') === $cat->id ? 'selected' : '' }}>
                                {{ $cat->name_en }} ({{ $cat->name_km }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">Product Type *</label>
                    <select name="product_type" required class="w-full text-xs rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3.5 py-2.5 focus:border-orange-500 focus:ring-orange-500">
                        <option value="food" {{ old('product_type') === 'food' ? 'selected' : '' }}>Food & Dining</option>
                        <option value="beverage" {{ old('product_type') === 'beverage' ? 'selected' : '' }}>Beverage & Drinks</option>
                        <option value="retail" {{ old('product_type') === 'retail' ? 'selected' : '' }}>Retail Product</option>
                        <option value="service" {{ old('product_type') === 'service' ? 'selected' : '' }}>Service</option>
                        <option value="standard" {{ old('product_type') === 'standard' ? 'selected' : '' }}>General Standard Item</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">SKU / Item Code</label>
                    <input type="text" name="sku" value="{{ old('sku') }}" placeholder="e.g. FOOD-001"
                           class="w-full text-sm rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3.5 py-2.5 focus:border-orange-500 focus:ring-orange-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">Image URL</label>
                    <input type="url" name="primary_image_url" value="{{ old('primary_image_url') }}"
                           placeholder="https://your-bucket.supabase.co/..."
                           class="w-full text-sm rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3.5 py-2.5 focus:border-orange-500 focus:ring-orange-500">
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">Description (English)</label>
                    <textarea name="description_en" rows="2" placeholder="Tell customers about this product's ingredients, taste, or materials..."
                              class="w-full text-sm rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3.5 py-2.5 focus:border-orange-500 focus:ring-orange-500">{{ old('description_en') }}</textarea>
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">Description (Khmer) - ការពិពណ៌នាជាភាសាខ្មែរ</label>
                    <textarea name="description_km" rows="2" placeholder="ពណ៌នាអំពីរសជាតិ គ្រឿងផ្សំ ឬលក្ខណៈពិសេស..."
                              class="w-full text-sm rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3.5 py-2.5 font-khmer focus:border-orange-500 focus:ring-orange-500">{{ old('description_km') }}</textarea>
                </div>
            </div>
        </div>

        <!-- Section 2: Pricing & Finance -->
        <div>
            <h3 class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider mb-4 pb-2 border-b border-gray-100 dark:border-gray-700">2. Pricing</h3>
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">Base Selling Price (USD) *</label>
                    <input type="number" step="0.01" name="base_price" value="{{ old('base_price', '0.00') }}" required
                           class="w-full text-sm rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3.5 py-2.5 focus:border-orange-500 focus:ring-orange-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">Discount Price (optional)</label>
                    <input type="number" step="0.01" name="discount_price" value="{{ old('discount_price') }}"
                           placeholder="Special promotional price"
                           class="w-full text-sm rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3.5 py-2.5 focus:border-orange-500 focus:ring-orange-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">Cost Price (COGS)</label>
                    <input type="number" step="0.01" name="cost_price" value="{{ old('cost_price', '0.00') }}"
                           class="w-full text-sm rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3.5 py-2.5 focus:border-orange-500 focus:ring-orange-500">
                </div>
            </div>
        </div>

        <!-- Section 3: Inventory Control -->
        <div>
            <h3 class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider mb-4 pb-2 border-b border-gray-100 dark:border-gray-700">3. Stock & Inventory</h3>
            <div class="space-y-4">
                <label class="flex items-center space-x-2 text-xs">
                    <input type="checkbox" name="track_inventory" value="1" x-model="trackInventory" class="rounded text-orange-600 focus:ring-orange-500">
                    <span class="font-semibold text-gray-800 dark:text-gray-200">Track finished item inventory for this product</span>
                </label>

                <div x-show="trackInventory" class="grid grid-cols-1 sm:grid-cols-3 gap-5 pt-2" style="display: none;">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">Current Stock</label>
                        <input type="number" step="0.01" name="stock_quantity" value="{{ old('stock_quantity', '0.00') }}"
                               class="w-full text-sm rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3.5 py-2.5 focus:border-orange-500 focus:ring-orange-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">Low Stock Threshold</label>
                        <input type="number" step="0.01" name="low_stock_threshold" value="{{ old('low_stock_threshold', '5.00') }}"
                               class="w-full text-sm rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3.5 py-2.5 focus:border-orange-500 focus:ring-orange-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">Unit</label>
                        <input type="text" name="unit" value="{{ old('unit', 'item') }}" placeholder="item, plate, cup, kg..."
                               class="w-full text-sm rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3.5 py-2.5 focus:border-orange-500 focus:ring-orange-500">
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 4: Variants & Options -->
        <div>
            <div class="flex items-center justify-between mb-4 pb-2 border-b border-gray-100 dark:border-gray-700">
                <h3 class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider">4. Product Variants (Sizes / Options)</h3>
                <button type="button" @click="variants.push({ name_en: '', name_km: '', price_adjustment: '0.00' })"
                        class="text-xs font-bold text-orange-600 hover:text-orange-700 dark:text-orange-400">
                    + Add Variant Option
                </button>
            </div>

            <template x-for="(variant, index) in variants" :key="index">
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 mb-3 p-3 bg-gray-50 dark:bg-gray-750 rounded-xl border border-gray-100 dark:border-gray-700">
                    <div>
                        <input type="text" :name="'variants[' + index + '][name_en]'" x-model="variant.name_en" required
                               placeholder="e.g. Regular, Large, XL"
                               class="w-full text-xs rounded-lg border-gray-200 dark:border-gray-600 dark:bg-gray-900 dark:text-white px-2.5 py-2">
                    </div>
                    <div>
                        <input type="text" :name="'variants[' + index + '][name_km]'" x-model="variant.name_km"
                               placeholder="ឈ្មោះខ្មែរ (ឧ. ធំ)"
                               class="w-full text-xs rounded-lg border-gray-200 dark:border-gray-600 dark:bg-gray-900 dark:text-white px-2.5 py-2 font-khmer">
                    </div>
                    <div>
                        <input type="number" step="0.01" :name="'variants[' + index + '][price_adjustment]'" x-model="variant.price_adjustment" required
                               placeholder="Price adj (+/- $)"
                               class="w-full text-xs rounded-lg border-gray-200 dark:border-gray-600 dark:bg-gray-900 dark:text-white px-2.5 py-2">
                    </div>
                    <div class="flex items-center justify-end">
                        <button type="button" @click="variants.splice(index, 1)" class="text-xs font-semibold text-red-600 hover:text-red-700">
                            Remove
                        </button>
                    </div>
                </div>
            </template>
            <p x-show="variants.length === 0" class="text-xs text-gray-400">No variants configured. Click "+ Add Variant Option" if this product has sizes or options.</p>
        </div>

        <!-- Section 5: Available Add-ons -->
        @if ($addons->count() > 0)
            <div>
                <h3 class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider mb-4 pb-2 border-b border-gray-100 dark:border-gray-700">5. Available Add-ons</h3>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    @foreach ($addons as $addon)
                        <label class="flex items-center space-x-2 text-xs p-2.5 border border-gray-100 dark:border-gray-700 rounded-xl bg-gray-50/50 dark:bg-gray-750">
                            <input type="checkbox" name="addons[]" value="{{ $addon->id }}" class="rounded text-orange-600 focus:ring-orange-500">
                            <div>
                                <p class="font-bold text-gray-900 dark:text-white">{{ $addon->name_en }}</p>
                                <p class="text-[10px] text-gray-500">+${{ number_format($addon->price, 2) }}</p>
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Visibility & Status -->
        <div class="pt-4 border-t border-gray-100 dark:border-gray-700 flex items-center space-x-6 text-xs">
            <label class="flex items-center space-x-2">
                <input type="checkbox" name="is_available" value="1" checked class="rounded text-orange-600 focus:ring-orange-500">
                <span class="font-bold text-gray-800 dark:text-gray-200">Available to Order</span>
            </label>

            <label class="flex items-center space-x-2">
                <input type="checkbox" name="is_featured" value="1" class="rounded text-orange-600 focus:ring-orange-500">
                <span class="font-bold text-orange-600 dark:text-orange-400">Show in Featured Section</span>
            </label>
        </div>

        <div class="flex items-center justify-end space-x-3 pt-4 border-t border-gray-100 dark:border-gray-700">
            <a href="{{ route('business.products.index') }}" class="px-4 py-2 text-xs font-semibold text-gray-600 hover:text-gray-800 dark:text-gray-300">Cancel</a>
            <button type="submit" class="px-6 py-2.5 bg-orange-600 hover:bg-orange-700 text-white font-bold text-xs rounded-xl shadow-xs transition">
                Create Product
            </button>
        </div>
    </form>
</div>
</x-business-layout>
