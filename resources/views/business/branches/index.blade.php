<x-business-layout title="Branches" heading="Business Branches">
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <p class="text-xs text-gray-500 dark:text-gray-400">Manage multiple locations, stores, or physical restaurant outlets</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Branches List -->
        <div class="lg:col-span-2 space-y-4">
            @forelse ($branches as $branch)
                <div class="p-5 bg-white border border-gray-100 rounded-2xl shadow-xs dark:bg-gray-800 dark:border-gray-700/60 flex items-center justify-between">
                    <div>
                        <div class="flex items-center space-x-2">
                            <h3 class="font-bold text-gray-900 dark:text-white">{{ $branch->name }}</h3>
                            @if ($branch->is_main)
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-orange-100 text-orange-700 dark:bg-orange-950/40 dark:text-orange-400">
                                    Primary Branch
                                </span>
                            @endif
                            <x-badge :color="$branch->is_active ? 'green' : 'gray'">
                                {{ $branch->is_active ? 'Active' : 'Inactive' }}
                            </x-badge>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">{{ $branch->address ?? 'No address provided' }}</p>
                        <p class="text-[11px] text-gray-400 mt-0.5">Phone: {{ $branch->phone ?? 'N/A' }}</p>
                    </div>

                    <div class="flex items-center space-x-2">
                        @if (!$branch->is_main)
                            <form method="POST" action="{{ route('business.branches.destroy', $branch) }}" onsubmit="return confirm('Delete this branch?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-xs font-semibold text-red-600 hover:text-red-700">Delete</button>
                            </form>
                        @endif
                    </div>
                </div>
            @empty
                <div class="p-8 text-center text-xs text-gray-400 bg-white rounded-2xl border border-gray-100 dark:bg-gray-800 dark:border-gray-700/60">
                    No branches created yet.
                </div>
            @endforelse
        </div>

        <!-- Add Branch Form -->
        <div class="p-6 bg-white border border-gray-100 rounded-2xl shadow-xs dark:bg-gray-800 dark:border-gray-700/60 h-fit">
            <h3 class="text-sm font-bold text-gray-900 dark:text-white mb-4">+ Add New Branch</h3>
            <form method="POST" action="{{ route('business.branches.store') }}" class="space-y-4 text-xs">
                @csrf

                <div>
                    <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">Branch Name *</label>
                    <input type="text" name="name" required placeholder="e.g. BKK1 Outlet, Siem Reap Branch..."
                           class="w-full text-xs rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2">
                </div>

                <div>
                    <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">Phone</label>
                    <input type="text" name="phone" placeholder="+855 ..."
                           class="w-full text-xs rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2">
                </div>

                <div>
                    <label class="block font-semibold text-gray-700 dark:text-gray-300 mb-1">Address</label>
                    <textarea name="address" rows="2" placeholder="Street, Khan, City..."
                              class="w-full text-xs rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2"></textarea>
                </div>

                <div>
                    <label class="flex items-center space-x-2">
                        <input type="checkbox" name="is_main" value="1" class="rounded text-orange-600 focus:ring-orange-500">
                        <span class="font-semibold text-gray-800 dark:text-gray-200">Set as Primary Branch</span>
                    </label>
                </div>

                <button type="submit" class="w-full py-2.5 bg-orange-600 hover:bg-orange-700 text-white font-bold rounded-xl shadow-xs transition">
                    Create Branch
                </button>
            </form>
        </div>
    </div>
</div>
</x-business-layout>
