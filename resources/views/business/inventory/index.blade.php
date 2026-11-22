<x-business-layout title="Inventory" heading="Inventory & Raw Materials">
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 class="font-bold text-2xl text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                    <svg class="w-7 h-7 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                    <span>{{ __('Inventory & Raw Materials') }} <span class="text-slate-400 font-normal">/</span> <span class="font-khmer font-bold">គ្រប់គ្រងស្តុកទំនិញ</span></span>
                </h2>
                <p class="text-sm text-slate-600 dark:text-slate-300 font-medium mt-1">
                    {{ __('Track raw ingredients, automatic recipe deductions, low-stock alerts, and stock adjustments.') }}
                </p>
            </div>
            <div class="flex items-center gap-3">
                <button type="button" @click="$dispatch('open-modal', 'add-item-modal')" class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-sm font-bold transition shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    {{ __('Add Raw Material') }}
                </button>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        <!-- KPI Cards Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
            <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-slate-200/80 dark:border-gray-700 shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">{{ __('Total Materials') }}</span>
                    <div class="text-2xl font-black text-slate-900 dark:text-white mt-1">{{ $totalItems }}</div>
                </div>
                <div class="w-12 h-12 rounded-xl bg-slate-100 dark:bg-gray-700 text-slate-700 dark:text-slate-200 flex items-center justify-center font-bold text-xl">
                    📦
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-slate-200/80 dark:border-gray-700 shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">{{ __('Low Stock Alerts') }}</span>
                    <div class="text-2xl font-black {{ $lowStockCount > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-900 dark:text-white' }} mt-1">
                        {{ $lowStockCount }}
                    </div>
                </div>
                <div class="w-12 h-12 rounded-xl {{ $lowStockCount > 0 ? 'bg-rose-50 text-rose-600 dark:bg-rose-950/40 dark:text-rose-400 animate-bounce' : 'bg-slate-100 text-slate-400 dark:bg-gray-700 dark:text-slate-300' }} flex items-center justify-center font-bold text-xl">
                    ⚠️
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 p-5 rounded-2xl border border-slate-200/80 dark:border-gray-700 shadow-sm flex items-center justify-between">
                <div>
                    <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">{{ __('Total Stock Value') }}</span>
                    <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1 font-mono">
                        ${{ number_format($totalInventoryValue, 2) }}
                    </div>
                </div>
                <div class="w-12 h-12 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 flex items-center justify-center font-bold text-xl">
                    💰
                </div>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="bg-white dark:bg-gray-800 p-4 rounded-2xl shadow-sm border border-slate-200/80 dark:border-gray-700 flex flex-col sm:flex-row items-center justify-between gap-4">
            <form method="GET" action="{{ route('business.inventory.index') }}" class="flex-1 flex flex-col sm:flex-row items-center gap-3 w-full">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('Search ingredient name or SKU...') }}" class="w-full sm:w-80 text-sm border-slate-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white rounded-xl px-3.5 py-2 focus:ring-emerald-500 focus:border-emerald-500 font-medium">
                <label class="flex items-center gap-2 text-xs font-bold text-slate-700 dark:text-slate-200 cursor-pointer">
                    <input type="checkbox" name="low_stock" value="1" {{ request('low_stock') ? 'checked' : '' }} onchange="this.form.submit()" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                    <span>{{ __('Show Only Low Stock') }}</span>
                </label>
                <button type="submit" class="px-4 py-2 bg-slate-900 hover:bg-black dark:bg-orange-600 dark:hover:bg-orange-700 text-white rounded-xl text-xs font-bold transition">
                    {{ __('Filter') }}
                </button>
                @if(request()->hasAny(['search', 'low_stock']))
                    <a href="{{ route('business.inventory.index') }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 dark:bg-gray-700 dark:text-slate-200 dark:hover:bg-gray-600 rounded-xl text-xs font-bold transition">
                        {{ __('Reset') }}
                    </a>
                @endif
            </form>
        </div>

        <!-- Inventory Items Table -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-slate-200/80 dark:border-gray-700 overflow-hidden">
            @if($items->isEmpty())
                <div class="text-center py-16">
                    <div class="w-16 h-16 mx-auto mb-4 bg-slate-100 dark:bg-gray-700 text-slate-400 dark:text-slate-300 rounded-2xl flex items-center justify-center">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    </div>
                    <h4 class="text-base font-bold text-slate-900 dark:text-white">{{ __('No inventory items recorded') }}</h4>
                    <p class="text-sm text-slate-600 dark:text-slate-300 font-medium max-w-sm mx-auto mt-1 mb-4">{{ __('Add raw materials (e.g. Rice, Beef, Coffee Beans, Cups) to link with product recipes and track usage.') }}</p>
                    <button type="button" @click="$dispatch('open-modal', 'add-item-modal')" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition">
                        {{ __('Add First Item') }}
                    </button>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-700 dark:text-slate-200">
                        <thead class="bg-slate-50/80 dark:bg-gray-900/60 border-b border-slate-200/80 dark:border-gray-700 text-xs uppercase tracking-wider text-slate-600 dark:text-slate-300 font-bold">
                            <tr>
                                <th class="py-3.5 px-4">{{ __('Ingredient / Item') }}</th>
                                <th class="py-3.5 px-4">{{ __('SKU') }}</th>
                                <th class="py-3.5 px-4">{{ __('Current Stock') }}</th>
                                <th class="py-3.5 px-4">{{ __('Alert Threshold') }}</th>
                                <th class="py-3.5 px-4">{{ __('Unit Cost') }}</th>
                                <th class="py-3.5 px-4">{{ __('Stock Value') }}</th>
                                <th class="py-3.5 px-4 text-right">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-gray-700/60 font-normal">
                            @foreach($items as $item)
                                @php
                                    $isLow = $item->current_stock <= $item->low_stock_threshold;
                                    $isOut = $item->current_stock <= 0;
                                @endphp
                                <tr class="hover:bg-slate-50/60 dark:hover:bg-gray-750/50 transition">
                                    <td class="py-3.5 px-4">
                                        <div class="font-bold text-slate-900 dark:text-white">{{ $item->name_en }}</div>
                                        @if($item->name_km)
                                            <div class="text-xs text-slate-500 dark:text-slate-400 font-khmer font-medium">{{ $item->name_km }}</div>
                                        @endif
                                    </td>
                                    <td class="py-3.5 px-4 font-mono text-xs text-slate-600 dark:text-slate-300 font-medium">
                                        {{ $item->sku ?? '-' }}
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold font-mono {{ $isOut ? 'bg-rose-100 text-rose-800 dark:bg-rose-950/50 dark:text-rose-300' : ($isLow ? 'bg-amber-100 text-amber-800 dark:bg-amber-950/50 dark:text-amber-300' : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300') }}">
                                            @if($isLow) ⚠️ @endif
                                            {{ (float)$item->current_stock }} {{ $item->unit }}
                                        </span>
                                    </td>
                                    <td class="py-3.5 px-4 font-mono text-xs text-slate-600 dark:text-slate-300 font-medium">
                                        {{ (float)$item->low_stock_threshold }} {{ $item->unit }}
                                    </td>
                                    <td class="py-3.5 px-4 font-mono text-xs text-slate-800 dark:text-slate-200 font-bold">
                                        ${{ number_format($item->unit_cost, 2) }}
                                    </td>
                                    <td class="py-3.5 px-4 font-mono font-black text-xs text-slate-900 dark:text-white">
                                        ${{ number_format($item->current_stock * $item->unit_cost, 2) }}
                                    </td>
                                    <td class="py-3.5 px-4 text-right space-x-2">
                                        <!-- Quick Adjust Button -->
                                        <button type="button"
                                                @click="$dispatch('open-modal', 'adjust-modal-{{ $item->id }}')"
                                                class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-800 dark:bg-gray-700 dark:text-slate-200 dark:hover:bg-gray-600 rounded-lg text-xs font-bold transition">
                                            {{ __('Adjust') }}
                                        </button>

                                        <!-- Delete -->
                                        <form method="POST" action="{{ route('business.inventory.destroy', $item) }}" class="inline-block" onsubmit="return confirm('Delete {{ $item->name_en }}?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-slate-400 hover:text-red-600 dark:text-slate-400 dark:hover:text-red-400 transition p-1 text-base font-bold">
                                                &times;
                                            </button>
                                        </form>

                                        <!-- Modal: Adjust Stock for this Item -->
                                        <x-modal name="adjust-modal-{{ $item->id }}" :show="false" focusable>
                                            <form method="POST" action="{{ route('business.inventory.adjust-stock', $item) }}" class="p-6 text-left">
                                                @csrf
                                                <h3 class="text-lg font-bold text-slate-900 dark:text-white pb-3 border-b border-slate-100 dark:border-gray-700">
                                                    {{ __('Adjust Stock') }}: {{ $item->name_en }}
                                                </h3>
                                                <div class="space-y-4 py-4">
                                                    <div>
                                                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mb-1">{{ __('Current Stock') }}</label>
                                                        <div class="text-base font-bold font-mono text-slate-900 dark:text-white">{{ (float)$item->current_stock }} {{ $item->unit }}</div>
                                                    </div>

                                                    <div>
                                                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mb-1">{{ __('Movement Type') }} *</label>
                                                        <select name="type" class="w-full text-sm border-slate-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white rounded-xl px-3 py-2 font-medium">
                                                            <option value="in">➕ Stock In / Restock</option>
                                                            <option value="out">➖ Stock Out</option>
                                                            <option value="waste">🗑️ Waste / Spoilage</option>
                                                            <option value="adjustment">🔄 Recount (Set Exact Balance)</option>
                                                        </select>
                                                    </div>

                                                    <div>
                                                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mb-1">{{ __('Quantity') }} ({{ $item->unit }}) *</label>
                                                        <input type="number" step="0.001" min="0.001" name="quantity" required class="w-full text-sm border-slate-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white rounded-xl px-3 py-2 font-mono font-bold">
                                                    </div>

                                                    <div>
                                                        <label class="block text-xs font-bold text-slate-500 dark:text-slate-400 uppercase mb-1">{{ __('Reason / Note') }}</label>
                                                        <input type="text" name="reason" placeholder="e.g. Weekly supermarket purchase" class="w-full text-sm border-slate-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white rounded-xl px-3 py-2">
                                                    </div>
                                                </div>

                                                <div class="flex justify-end gap-3 pt-3 border-t border-slate-100 dark:border-gray-700">
                                                    <button type="button" @click="$dispatch('close')" class="px-4 py-2 bg-slate-100 dark:bg-gray-700 text-slate-700 dark:text-slate-200 rounded-xl text-xs font-bold">Cancel</button>
                                                    <button type="submit" class="px-5 py-2 bg-emerald-600 text-white rounded-xl text-xs font-bold hover:bg-emerald-700">Save Adjustment</button>
                                                </div>
                                            </form>
                                        </x-modal>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="p-4 border-t border-slate-100 dark:border-gray-700">
                    {{ $items->links() }}
                </div>
            @endif
        </div>

        <!-- Recent Stock Movements Audit Trail -->
        @if($recentMovements->isNotEmpty())
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-slate-200/80 dark:border-gray-700 p-6">
                <h3 class="font-bold text-base text-slate-900 dark:text-white pb-3 border-b border-slate-100 dark:border-gray-700 flex items-center gap-2">
                    <svg class="w-5 h-5 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>{{ __('Recent Stock Movements Audit Log') }}</span>
                </h3>

                <div class="overflow-x-auto mt-3">
                    <table class="w-full text-left text-xs text-slate-700 dark:text-slate-200">
                        <thead class="text-slate-500 dark:text-slate-300 font-bold uppercase border-b border-slate-100 dark:border-gray-700 pb-2">
                            <tr>
                                <th class="py-2.5">{{ __('Date/Time') }}</th>
                                <th class="py-2.5">{{ __('Item') }}</th>
                                <th class="py-2.5">{{ __('Type') }}</th>
                                <th class="py-2.5">{{ __('Quantity Changed') }}</th>
                                <th class="py-2.5">{{ __('Balance After') }}</th>
                                <th class="py-2.5">{{ __('Reason / Reference') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-gray-700/60 font-medium">
                            @foreach($recentMovements as $mov)
                                <tr>
                                    <td class="py-2.5 text-slate-500 dark:text-slate-400 font-mono">{{ $mov->created_at->format('d/m/Y H:i') }}</td>
                                    <td class="py-2.5 font-bold text-slate-900 dark:text-white">{{ $mov->inventoryItem?->name_en }}</td>
                                    <td class="py-2.5">
                                        <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase {{ $mov->quantity > 0 ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300' : 'bg-rose-100 text-rose-800 dark:bg-rose-950/50 dark:text-rose-300' }}">
                                            {{ str_replace('_', ' ', $mov->type) }}
                                        </span>
                                    </td>
                                    <td class="py-2.5 font-mono font-bold {{ $mov->quantity > 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                                        {{ $mov->quantity > 0 ? '+' : '' }}{{ (float)$mov->quantity }}
                                    </td>
                                    <td class="py-2.5 font-mono font-bold text-slate-900 dark:text-white">{{ (float)$mov->balance_after }}</td>
                                    <td class="py-2.5 text-slate-600 dark:text-slate-300">{{ $mov->reason ?? $mov->reference_id ?? '-' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>

    <!-- Modal: Add Raw Material -->
    <x-modal name="add-item-modal" :show="false" focusable>
        <form method="POST" action="{{ route('business.inventory.store') }}" class="p-6">
            @csrf
            <h3 class="text-lg font-bold text-slate-900 dark:text-white pb-3 border-b border-slate-100 dark:border-gray-700">
                {{ __('Add New Raw Material / Ingredient') }}
            </h3>

            <div class="space-y-4 py-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="name_en" :value="__('Name (English) *')" />
                        <x-text-input id="name_en" name="name_en" type="text" class="mt-1 block w-full" placeholder="e.g. Jasmine Rice" required />
                    </div>
                    <div>
                        <x-input-label for="name_km" :value="__('Name (Khmer)')" />
                        <x-text-input id="name_km" name="name_km" type="text" class="mt-1 block w-full font-khmer" placeholder="e.g. អង្ករផ្កាម្លិះ" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="sku" :value="__('SKU / Code')" />
                        <x-text-input id="sku" name="sku" type="text" class="mt-1 block w-full font-mono" placeholder="e.g. RAW-RIC-01" />
                    </div>
                    <div>
                        <x-input-label for="unit" :value="__('Unit of Measure *')" />
                        <select id="unit" name="unit" class="mt-1 block w-full border-slate-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white rounded-xl text-sm font-medium" required>
                            <option value="kg">kg (Kilogram)</option>
                            <option value="g">g (Gram)</option>
                            <option value="liter">liter</option>
                            <option value="ml">ml (Milliliter)</option>
                            <option value="piece">piece</option>
                            <option value="can">can</option>
                            <option value="bottle">bottle</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <x-input-label for="current_stock" :value="__('Opening Stock *')" />
                        <x-text-input id="current_stock" name="current_stock" type="number" step="0.001" min="0" value="10" class="mt-1 block w-full font-mono" required />
                    </div>
                    <div>
                        <x-input-label for="low_stock_threshold" :value="__('Alert Level *')" />
                        <x-text-input id="low_stock_threshold" name="low_stock_threshold" type="number" step="0.001" min="0" value="5" class="mt-1 block w-full font-mono" required />
                    </div>
                    <div>
                        <x-input-label for="unit_cost" :value="__('Cost per Unit ($) *')" />
                        <x-text-input id="unit_cost" name="unit_cost" type="number" step="0.0001" min="0" value="1.00" class="mt-1 block w-full font-mono" required />
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-3 border-t border-slate-100 dark:border-gray-700">
                <button type="button" @click="$dispatch('close')" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-gray-700 dark:text-slate-200 rounded-xl text-xs font-bold transition">Cancel</button>
                <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition">Create Raw Material</button>
            </div>
        </form>
    </x-modal>
</x-business-layout>
