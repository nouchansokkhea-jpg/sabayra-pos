<x-admin-layout title="SaaS Plans - Super Admin" heading="Subscription Plans Management">

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <p class="text-xs text-gray-500 dark:text-gray-400">Manage SaaS pricing tiers, feature flags, and limits for businesses</p>
        <a href="{{ route('admin.plans.create') }}" class="inline-flex items-center px-4 py-2 text-xs font-semibold text-white bg-orange-600 hover:bg-orange-700 rounded-xl shadow-xs transition">
            + Create New Plan
        </a>
    </div>

    <!-- Plans Cards Grid -->
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($plans as $plan)
            <div class="relative p-6 bg-white border {{ $plan->is_popular ? 'border-orange-500 ring-2 ring-orange-500/20' : 'border-gray-100' }} rounded-2xl shadow-xs dark:bg-gray-800 dark:border-gray-700/60 flex flex-col justify-between">
                @if ($plan->is_popular)
                    <div class="absolute -top-3 right-6 bg-orange-600 text-white font-bold text-[10px] tracking-wider uppercase px-3 py-0.5 rounded-full shadow-xs">
                        Most Popular
                    </div>
                @endif

                <div>
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ $plan->name }}</h3>
                        <x-badge :color="$plan->is_active ? 'green' : 'gray'">
                            {{ $plan->is_active ? 'Active' : 'Inactive' }}
                        </x-badge>
                    </div>

                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400 min-h-8">{{ $plan->description }}</p>

                    <div class="mt-4">
                        <div class="flex items-baseline space-x-1">
                            <span class="text-3xl font-extrabold text-gray-900 dark:text-white">${{ number_format($plan->monthly_price, 2) }}</span>
                            <span class="text-xs text-gray-500">/ month</span>
                        </div>
                        <p class="text-xs text-gray-400 mt-0.5">${{ number_format($plan->yearly_price, 2) }} billed annually</p>
                    </div>

                    <!-- Limits & Feature List -->
                    <ul class="mt-6 space-y-2 text-xs text-gray-600 dark:text-gray-300 border-t border-gray-100 dark:border-gray-700 pt-4">
                        <li class="flex items-center justify-between font-semibold">
                            <span>Product Catalog Limit</span>
                            <span class="text-orange-600 dark:text-orange-400">{{ $plan->product_limit }} items</span>
                        </li>
                        <li class="flex items-center justify-between font-semibold">
                            <span>Max Branches</span>
                            <span>{{ $plan->branch_limit }}</span>
                        </li>
                        <li class="flex items-center justify-between font-semibold">
                            <span>Max Staff Members</span>
                            <span>{{ $plan->staff_limit }}</span>
                        </li>
                        <li class="flex items-center space-x-2 pt-2">
                            <svg class="w-4 h-4 {{ $plan->has_pos ? 'text-emerald-500' : 'text-gray-300 dark:text-gray-600' }}" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            <span class="{{ $plan->has_pos ? '' : 'text-gray-400 line-through' }}">Point of Sale (POS)</span>
                        </li>
                        <li class="flex items-center space-x-2">
                            <svg class="w-4 h-4 {{ $plan->has_inventory ? 'text-emerald-500' : 'text-gray-300 dark:text-gray-600' }}" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            <span class="{{ $plan->has_inventory ? '' : 'text-gray-400 line-through' }}">Recipe & Inventory</span>
                        </li>
                        <li class="flex items-center space-x-2">
                            <svg class="w-4 h-4 {{ $plan->has_online_ordering ? 'text-emerald-500' : 'text-gray-300 dark:text-gray-600' }}" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            <span>Table QR & Online Ordering</span>
                        </li>
                        <li class="flex items-center space-x-2">
                            <svg class="w-4 h-4 {{ $plan->has_analytics ? 'text-emerald-500' : 'text-gray-300 dark:text-gray-600' }}" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            <span class="{{ $plan->has_analytics ? '' : 'text-gray-400 line-through' }}">Analytics & Reports</span>
                        </li>
                    </ul>
                </div>

                <div class="mt-6 pt-4 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between">
                    <span class="text-xs text-gray-500">{{ $plan->subscriptions_count }} subscriptions</span>
                    <a href="{{ route('admin.plans.edit', $plan) }}" class="text-xs font-semibold text-orange-600 hover:text-orange-700 dark:text-orange-400">Edit Plan &rarr;</a>
                </div>
            </div>
        @endforeach
    </div>
</div>
</x-admin-layout>
