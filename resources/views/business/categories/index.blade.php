<x-business-layout title="Categories" heading="Product Categories">
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <p class="text-xs text-gray-500 dark:text-gray-400">Organize your menu or retail catalog into logical sections</p>
        <a href="{{ route('business.categories.create') }}" class="inline-flex items-center px-4 py-2 text-xs font-bold text-white bg-orange-600 hover:bg-orange-700 rounded-xl shadow-xs transition">
            + Add Category
        </a>
    </div>

    <div class="overflow-hidden bg-white border border-gray-100 rounded-2xl shadow-xs dark:bg-gray-800 dark:border-gray-700/60">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-750">
                    <tr class="text-left text-xs font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider">
                        <th class="px-6 py-3.5">Category Name (English & Khmer)</th>
                        <th class="px-6 py-3.5">Slug</th>
                        <th class="px-6 py-3.5 text-center">Items Count</th>
                        <th class="px-6 py-3.5 text-center">Sort Order</th>
                        <th class="px-6 py-3.5">Status</th>
                        <th class="px-6 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60 text-xs">
                    @forelse ($categories as $cat)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-750/50 transition">
                            <td class="px-6 py-4">
                                <div class="flex items-center space-x-3">
                                    @if ($cat->image_url)
                                        <img src="{{ $cat->image_url }}" alt="" class="w-9 h-9 rounded-xl object-cover shrink-0">
                                    @else
                                        <div class="w-9 h-9 rounded-xl bg-orange-50 text-orange-600 dark:bg-orange-950/40 dark:text-orange-400 flex items-center justify-center font-bold text-sm shrink-0">
                                            {{ strtoupper(substr($cat->name_en, 0, 1)) }}
                                        </div>
                                    @endif
                                    <div>
                                        <p class="font-bold text-gray-900 dark:text-white">{{ $cat->name_en }}</p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400 font-khmer font-medium">{{ $cat->name_km ?? '—' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 font-mono text-xs text-slate-600 dark:text-slate-300 font-medium">
                                {{ $cat->slug }}
                            </td>
                            <td class="px-6 py-4 text-center font-bold text-slate-800 dark:text-slate-100">
                                {{ $cat->products_count }}
                            </td>
                            <td class="px-6 py-4 text-center text-slate-600 dark:text-slate-400 font-medium">
                                {{ $cat->sort_order }}
                            </td>
                            <td class="px-6 py-4">
                                <x-badge :color="$cat->is_active ? 'green' : 'gray'">
                                    {{ $cat->is_active ? 'Active' : 'Hidden' }}
                                </x-badge>
                            </td>
                            <td class="px-6 py-4 text-right space-x-2">
                                <a href="{{ route('business.categories.edit', $cat) }}" class="font-semibold text-blue-600 hover:text-blue-700 dark:text-blue-400">Edit</a>

                                <form method="POST" action="{{ route('business.categories.toggle-status', $cat) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="font-semibold {{ $cat->is_active ? 'text-amber-600 hover:text-amber-700' : 'text-emerald-600 hover:text-emerald-700' }}">
                                        {{ $cat->is_active ? 'Hide' : 'Show' }}
                                    </button>
                                </form>

                                <form method="POST" action="{{ route('business.categories.destroy', $cat) }}" class="inline" onsubmit="return confirm('Are you sure you want to delete this category?');">
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
                            <td colspan="6" class="px-6 py-8 text-center text-gray-400">No categories created yet. Click "+ Add Category" to get started.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
</x-business-layout>
