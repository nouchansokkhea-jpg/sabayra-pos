<x-business-layout title="Edit Category" heading="Edit Category: {{ $category->name_en }}">
<div class="max-w-2xl p-6 bg-white border border-gray-100 rounded-2xl shadow-xs dark:bg-gray-800 dark:border-gray-700/60">
    <form method="POST" action="{{ route('business.categories.update', $category) }}" class="space-y-6">
        @csrf
        @method('PUT')

        <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">Category Name (English) *</label>
            <input type="text" name="name_en" value="{{ old('name_en', $category->name_en) }}" required
                   class="w-full text-sm rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3.5 py-2.5 focus:border-orange-500 focus:ring-orange-500">
            @error('name_en') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">Category Name (Khmer) - ឈ្មោះជាភាសាខ្មែរ</label>
            <input type="text" name="name_km" value="{{ old('name_km', $category->name_km) }}"
                   class="w-full text-sm rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3.5 py-2.5 font-khmer focus:border-orange-500 focus:ring-orange-500">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">Slug *</label>
                <input type="text" name="slug" value="{{ old('slug', $category->slug) }}" required
                       class="w-full text-sm rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3.5 py-2.5 focus:border-orange-500 focus:ring-orange-500">
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">Sort Order</label>
                <input type="number" name="sort_order" value="{{ old('sort_order', $category->sort_order) }}"
                       class="w-full text-sm rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3.5 py-2.5 focus:border-orange-500 focus:ring-orange-500">
            </div>
        </div>

        <div>
            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">Image URL</label>
            <input type="url" name="image_url" value="{{ old('image_url', $category->image_url) }}"
                   class="w-full text-sm rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3.5 py-2.5 focus:border-orange-500 focus:ring-orange-500">
        </div>

        <div>
            <label class="flex items-center space-x-2 text-xs">
                <input type="checkbox" name="is_active" value="1" {{ $category->is_active ? 'checked' : '' }} class="rounded text-orange-600 focus:ring-orange-500">
                <span class="font-semibold text-gray-800 dark:text-gray-200">Active (Visible on public menu and POS)</span>
            </label>
        </div>

        <div class="flex items-center justify-end space-x-3 pt-4 border-t border-gray-100 dark:border-gray-700">
            <a href="{{ route('business.categories.index') }}" class="px-4 py-2 text-xs font-semibold text-gray-600 hover:text-gray-800 dark:text-gray-300">Cancel</a>
            <button type="submit" class="px-5 py-2.5 bg-orange-600 hover:bg-orange-700 text-white font-bold text-xs rounded-xl shadow-xs transition">
                Save Changes
            </button>
        </div>
    </form>
</div>
</x-business-layout>
