<x-business-layout title="Edit Product" heading="Edit Product: {{ $product->name_en }}">
<div class="max-w-4xl p-6 bg-white border border-gray-100 rounded-2xl shadow-xs dark:bg-gray-800 dark:border-gray-700/60"
     x-data="{
         trackInventory: {{ old('track_inventory', $product->track_inventory) ? 'true' : 'false' }}
     }">
    <form method="POST" action="{{ route('business.products.update', $product) }}" class="space-y-8">
        @csrf
        @method('PUT')

        <!-- Section 1: Basic Information -->
        <div>
            <h3 class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider mb-4 pb-2 border-b border-gray-100 dark:border-gray-700">1. Product Identity</h3>
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">Product Name (English) *</label>
                    <input type="text" name="name_en" value="{{ old('name_en', $product->name_en) }}" required
                           class="w-full text-sm rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3.5 py-2.5 focus:border-orange-500 focus:ring-orange-500">
                    @error('name_en') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">Product Name (Khmer) - ឈ្មោះជាភាសាខ្មែរ</label>
                    <input type="text" name="name_km" value="{{ old('name_km', $product->name_km) }}"
                           class="w-full text-sm rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3.5 py-2.5 font-khmer focus:border-orange-500 focus:ring-orange-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">Category *</label>
                    <select name="category_id" class="w-full text-xs rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3.5 py-2.5 focus:border-orange-500 focus:ring-orange-500">
                        <option value="">Select Category</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}" {{ old('category_id', $product->category_id) === $cat->id ? 'selected' : '' }}>
                                {{ $cat->name_en }} ({{ $cat->name_km }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">Product Type *</label>
                    <select name="product_type" required class="w-full text-xs rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3.5 py-2.5 focus:border-orange-500 focus:ring-orange-500">
                        <option value="food" {{ old('product_type', $product->product_type) === 'food' ? 'selected' : '' }}>Food & Dining</option>
                        <option value="beverage" {{ old('product_type', $product->product_type) === 'beverage' ? 'selected' : '' }}>Beverage & Drinks</option>
                        <option value="retail" {{ old('product_type', $product->product_type) === 'retail' ? 'selected' : '' }}>Retail Product</option>
                        <option value="service" {{ old('product_type', $product->product_type) === 'service' ? 'selected' : '' }}>Service</option>
                        <option value="standard" {{ old('product_type', $product->product_type) === 'standard' ? 'selected' : '' }}>General Standard Item</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">Slug *</label>
                    <input type="text" name="slug" value="{{ old('slug', $product->slug) }}" required
                           class="w-full text-sm rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3.5 py-2.5 focus:border-orange-500 focus:ring-orange-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">SKU / Item Code</label>
                    <input type="text" name="sku" value="{{ old('sku', $product->sku) }}"
                           class="w-full text-sm rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3.5 py-2.5 focus:border-orange-500 focus:ring-orange-500">
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">Primary Image URL</label>
                    <input type="url" name="primary_image_url" value="{{ old('primary_image_url', $product->primary_image_url) }}"
                           class="w-full text-sm rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3.5 py-2.5 focus:border-orange-500 focus:ring-orange-500">
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">Description (English)</label>
                    <textarea name="description_en" rows="2"
                              class="w-full text-sm rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3.5 py-2.5 focus:border-orange-500 focus:ring-orange-500">{{ old('description_en', $product->description_en) }}</textarea>
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">Description (Khmer) - ការពិពណ៌នាជាភាសាខ្មែរ</label>
                    <textarea name="description_km" rows="2"
                              class="w-full text-sm rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3.5 py-2.5 font-khmer focus:border-orange-500 focus:ring-orange-500">{{ old('description_km', $product->description_km) }}</textarea>
                </div>
            </div>
        </div>

        <!-- Section 2: Pricing -->
        <div>
            <h3 class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider mb-4 pb-2 border-b border-gray-100 dark:border-gray-700">2. Pricing</h3>
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">Base Selling Price (USD) *</label>
                    <input type="number" step="0.01" name="base_price" value="{{ old('base_price', $product->base_price) }}" required
                           class="w-full text-sm rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3.5 py-2.5 focus:border-orange-500 focus:ring-orange-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">Discount Price (optional)</label>
                    <input type="number" step="0.01" name="discount_price" value="{{ old('discount_price', $product->discount_price) }}"
                           class="w-full text-sm rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3.5 py-2.5 focus:border-orange-500 focus:ring-orange-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">Cost Price (COGS)</label>
                    <input type="number" step="0.01" name="cost_price" value="{{ old('cost_price', $product->cost_price) }}"
                           class="w-full text-sm rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3.5 py-2.5 focus:border-orange-500 focus:ring-orange-500">
                </div>
            </div>
        </div>

        <!-- Section 3: Stock Control -->
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
                        <input type="number" step="0.01" name="stock_quantity" value="{{ old('stock_quantity', $product->stock_quantity) }}"
                               class="w-full text-sm rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3.5 py-2.5 focus:border-orange-500 focus:ring-orange-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">Low Stock Threshold</label>
                        <input type="number" step="0.01" name="low_stock_threshold" value="{{ old('low_stock_threshold', $product->low_stock_threshold) }}"
                               class="w-full text-sm rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3.5 py-2.5 focus:border-orange-500 focus:ring-orange-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">Unit</label>
                        <input type="text" name="unit" value="{{ old('unit', $product->unit) }}"
                               class="w-full text-sm rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3.5 py-2.5 focus:border-orange-500 focus:ring-orange-500">
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 4: Add-ons -->
        @if ($addons->count() > 0)
            <div>
                <h3 class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider mb-4 pb-2 border-b border-gray-100 dark:border-gray-700">4. Linked Add-ons</h3>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    @php $productAddonIds = $product->addons->pluck('id')->toArray(); @endphp
                    @foreach ($addons as $addon)
                        <label class="flex items-center space-x-2 text-xs p-2.5 border border-gray-100 dark:border-gray-700 rounded-xl bg-gray-50/50 dark:bg-gray-750">
                            <input type="checkbox" name="addons[]" value="{{ $addon->id }}" {{ in_array($addon->id, $productAddonIds) ? 'checked' : '' }} class="rounded text-orange-600 focus:ring-orange-500">
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
                <input type="checkbox" name="is_available" value="1" {{ $product->is_available ? 'checked' : '' }} class="rounded text-orange-600 focus:ring-orange-500">
                <span class="font-bold text-gray-800 dark:text-gray-200">Available to Order</span>
            </label>

            <label class="flex items-center space-x-2">
                <input type="checkbox" name="is_featured" value="1" {{ $product->is_featured ? 'checked' : '' }} class="rounded text-orange-600 focus:ring-orange-500">
                <span class="font-bold text-orange-600 dark:text-orange-400">Show in Featured Section</span>
            </label>
        </div>

        <div class="flex items-center justify-end space-x-3 pt-4 border-t border-gray-100 dark:border-gray-700">
            <a href="{{ route('business.products.index') }}" class="px-4 py-2 text-xs font-semibold text-gray-600 hover:text-gray-800 dark:text-gray-300">Cancel</a>
            <button type="submit" class="px-6 py-2.5 bg-orange-600 hover:bg-orange-700 text-white font-bold text-xs rounded-xl shadow-xs transition">
                Save Product Changes
            </button>
        </div>
    </form>
</div>
</x-business-layout>
