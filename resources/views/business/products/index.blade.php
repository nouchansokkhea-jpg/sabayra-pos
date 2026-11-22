<x-business-layout title="Products" heading="Product Catalog">
<div class="space-y-6">
    <!-- Header with Filters & Search -->
    <div class="p-4 bg-white border border-gray-100 rounded-2xl shadow-xs dark:bg-gray-800 dark:border-gray-700/60">
        <form method="GET" action="{{ route('business.products.index') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-4">
            <div class="sm:col-span-2">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Search products by name, SKU..."
                       class="w-full text-xs rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2.5 focus:border-orange-500 focus:ring-orange-500">
            </div>

            <div>
                <select name="category_id" class="w-full text-xs rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2.5 focus:border-orange-500 focus:ring-orange-500">
                    <option value="">All Categories</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}" {{ request('category_id') === $cat->id ? 'selected' : '' }}>
                            {{ $cat->name_en }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex space-x-2">
                <button type="submit" class="px-4 py-2.5 bg-orange-600 hover:bg-orange-700 text-white font-semibold text-xs rounded-xl shadow-xs transition grow">
                    Filter
                </button>
                <a href="{{ route('business.products.create') }}" class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-xs transition shrink-0">
                    + Add Product
                </a>
            </div>
        </form>
    </div>

    <!-- Products Table -->
    <div class="overflow-hidden bg-white border border-gray-100 rounded-2xl shadow-xs dark:bg-gray-800 dark:border-gray-700/60">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-750">
                    <tr class="text-left text-xs font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider">
                        <th class="px-6 py-3.5">Product</th>
                        <th class="px-6 py-3.5">Category</th>
                        <th class="px-6 py-3.5">Price (USD / KHR)</th>
                        <th class="px-6 py-3.5 text-center">Variants</th>
                        <th class="px-6 py-3.5">Stock</th>
                        <th class="px-6 py-3.5">Status</th>
                        <th class="px-6 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60 text-xs">
                    @forelse ($products as $prod)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-750/50 transition">
                            <td class="px-6 py-4">
                                <div class="flex items-center space-x-3">
                                    @if ($prod->primary_image_url)
                                        <img src="{{ $prod->primary_image_url }}" alt="" class="w-10 h-10 rounded-xl object-cover shrink-0">
                                    @else
                                        <div class="w-10 h-10 rounded-xl bg-orange-50 text-orange-600 dark:bg-orange-950/40 dark:text-orange-400 flex items-center justify-center font-bold text-sm shrink-0">
                                            {{ strtoupper(substr($prod->name_en, 0, 1)) }}
                                        </div>
                                    @endif
                                    <div>
                                        <p class="font-bold text-gray-900 dark:text-white flex items-center">
                                            {{ $prod->name_en }}
                                            @if ($prod->is_featured)
                                                <span class="ml-1.5 text-[9px] bg-amber-100 text-amber-800 font-bold px-1.5 py-0.5 rounded uppercase">Featured</span>
                                            @endif
                                        </p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400 font-khmer font-medium">{{ $prod->name_km ?? '—' }}</p>
                                        <span class="text-[11px] text-slate-500 dark:text-slate-400 font-mono font-medium">SKU: {{ $prod->sku ?? 'N/A' }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-slate-700 dark:text-slate-200 font-medium">{{ $prod->category->name_en ?? 'Uncategorized' }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <div>
                                    <x-khr-price :usd="$prod->effective_price" />
                                    @if ($prod->discount_price && $prod->discount_price < $prod->base_price)
                                        <p class="text-[11px] text-gray-400 line-through">${{ number_format($prod->base_price, 2) }}</p>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if ($prod->variants->count() > 0)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-purple-50 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300">
                                        {{ $prod->variants->count() }} options
                                    </span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if ($prod->track_inventory)
                                    <span class="font-bold {{ $prod->stock_quantity <= $prod->low_stock_threshold ? 'text-red-600' : 'text-gray-800 dark:text-gray-200' }}">
                                        {{ $prod->stock_quantity }} {{ $prod->unit }}
                                    </span>
                                @else
                                    <span class="text-gray-400 text-[11px]">Untracked</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <x-badge :color="$prod->is_available ? 'green' : 'red'">
                                    {{ $prod->is_available ? 'Available' : 'Unavailable' }}
                                </x-badge>
                            </td>
                            <td class="px-6 py-4 text-right space-x-2">
                                <a href="{{ route('business.products.edit', $prod) }}" class="font-semibold text-blue-600 hover:text-blue-700 dark:text-blue-400">Edit</a>

                                <form method="POST" action="{{ route('business.products.toggle-availability', $prod) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="font-semibold {{ $prod->is_available ? 'text-amber-600 hover:text-amber-700' : 'text-emerald-600 hover:text-emerald-700' }}">
                                        {{ $prod->is_available ? 'Disable' : 'Enable' }}
                                    </button>
                                </form>

                                <form method="POST" action="{{ route('business.products.destroy', $prod) }}" class="inline" onsubmit="return confirm('Delete this product?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="font-semibold text-red-600 hover:text-red-700">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-gray-400">No products match your search.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($products->hasPages())
            <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">
                {{ $products->links() }}
            </div>
        @endif
    </div>
</div>
</x-business-layout>
