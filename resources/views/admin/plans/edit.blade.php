<x-admin-layout :title="'Edit Plan - ' . $plan->name" :heading="'Edit Plan: ' . $plan->name">

<div class="max-w-4xl p-6 bg-white border border-gray-100 rounded-2xl shadow-xs dark:bg-gray-800 dark:border-gray-700/60">
    <form method="POST" action="{{ route('admin.plans.update', $plan) }}" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">Plan Name *</label>
                <input type="text" name="name" value="{{ old('name', $plan->name) }}" required
                       class="w-full text-sm rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2.5 focus:border-orange-500 focus:ring-orange-500">
                @error('name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">Slug *</label>
                <input type="text" name="slug" value="{{ old('slug', $plan->slug) }}" required
                       class="w-full text-sm rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2.5 focus:border-orange-500 focus:ring-orange-500">
                @error('slug') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
            </div>

            <div class="sm:col-span-2">
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">Description</label>
                <textarea name="description" rows="2"
                          class="w-full text-sm rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2.5 focus:border-orange-500 focus:ring-orange-500">{{ old('description', $plan->description) }}</textarea>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">Monthly Price (USD) *</label>
                <input type="number" step="0.01" name="monthly_price" value="{{ old('monthly_price', $plan->monthly_price) }}" required
                       class="w-full text-sm rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2.5 focus:border-orange-500 focus:ring-orange-500">
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">Yearly Price (USD) *</label>
                <input type="number" step="0.01" name="yearly_price" value="{{ old('yearly_price', $plan->yearly_price) }}" required
                       class="w-full text-sm rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2.5 focus:border-orange-500 focus:ring-orange-500">
            </div>

            <input type="hidden" name="currency" value="USD">

            <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">Product Limit *</label>
                <input type="number" name="product_limit" value="{{ old('product_limit', $plan->product_limit) }}" required
                       class="w-full text-sm rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2.5 focus:border-orange-500 focus:ring-orange-500">
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">Branch Limit *</label>
                <input type="number" name="branch_limit" value="{{ old('branch_limit', $plan->branch_limit) }}" required
                       class="w-full text-sm rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2.5 focus:border-orange-500 focus:ring-orange-500">
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">Staff Limit *</label>
                <input type="number" name="staff_limit" value="{{ old('staff_limit', $plan->staff_limit) }}" required
                       class="w-full text-sm rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2.5 focus:border-orange-500 focus:ring-orange-500">
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-2">Sort Order</label>
                <input type="number" name="sort_order" value="{{ old('sort_order', $plan->sort_order) }}"
                       class="w-full text-sm rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2.5 focus:border-orange-500 focus:ring-orange-500">
            </div>
        </div>

        <div class="pt-4 border-t border-gray-100 dark:border-gray-700">
            <h4 class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider mb-3">Feature Entitlements</h4>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3 text-xs">
                <label class="flex items-center space-x-2">
                    <input type="checkbox" name="has_pos" value="1" {{ $plan->has_pos ? 'checked' : '' }} class="rounded text-orange-600 focus:ring-orange-500">
                    <span>Point of Sale (POS)</span>
                </label>
                <label class="flex items-center space-x-2">
                    <input type="checkbox" name="has_inventory" value="1" {{ $plan->has_inventory ? 'checked' : '' }} class="rounded text-orange-600 focus:ring-orange-500">
                    <span>Recipe & Inventory</span>
                </label>
                <label class="flex items-center space-x-2">
                    <input type="checkbox" name="has_online_ordering" value="1" {{ $plan->has_online_ordering ? 'checked' : '' }} class="rounded text-orange-600 focus:ring-orange-500">
                    <span>Online Ordering</span>
                </label>
                <label class="flex items-center space-x-2">
                    <input type="checkbox" name="has_analytics" value="1" {{ $plan->has_analytics ? 'checked' : '' }} class="rounded text-orange-600 focus:ring-orange-500">
                    <span>Financial Analytics</span>
                </label>
                <label class="flex items-center space-x-2">
                    <input type="checkbox" name="has_custom_branding" value="1" {{ $plan->has_custom_branding ? 'checked' : '' }} class="rounded text-orange-600 focus:ring-orange-500">
                    <span>Custom Branding</span>
                </label>
                <label class="flex items-center space-x-2">
                    <input type="checkbox" name="is_popular" value="1" {{ $plan->is_popular ? 'checked' : '' }} class="rounded text-orange-600 focus:ring-orange-500">
                    <span>Mark as "Most Popular"</span>
                </label>
                <label class="flex items-center space-x-2">
                    <input type="checkbox" name="is_active" value="1" {{ $plan->is_active ? 'checked' : '' }} class="rounded text-orange-600 focus:ring-orange-500">
                    <span>Active & Available for Purchase</span>
                </label>
            </div>
        </div>

        <div class="flex items-center justify-between pt-4 border-t border-gray-100 dark:border-gray-700">
            <button type="button" onclick="if(confirm('Delete this plan?')) document.getElementById('delete-plan-form').submit();"
                    class="text-xs font-semibold text-red-600 hover:text-red-700">
                Delete Plan
            </button>

            <div class="flex items-center space-x-3">
                <a href="{{ route('admin.plans.index') }}" class="px-4 py-2 text-xs font-semibold text-gray-600 hover:text-gray-800 dark:text-gray-300">Cancel</a>
                <button type="submit" class="px-5 py-2.5 bg-orange-600 hover:bg-orange-700 text-white font-semibold text-xs rounded-xl shadow-xs transition">
                    Save Changes
                </button>
            </div>
        </div>
    </form>

    <form id="delete-plan-form" method="POST" action="{{ route('admin.plans.destroy', $plan) }}" style="display: none;">
        @csrf
        @method('DELETE')
    </form>
</div>
</x-admin-layout>
