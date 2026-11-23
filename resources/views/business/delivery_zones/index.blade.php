<x-business-layout title="Delivery Zones" heading="Delivery Zones & Rates">
<div class="space-y-6" x-data="{ addZone: false }">

    @if (session('status'))
        <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-xs font-bold text-emerald-800 dark:text-emerald-200">
            {{ session('status') }}
        </div>
    @endif

    <div class="p-4 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-2xl shadow-xs flex items-center justify-between">
        <div>
            <h3 class="text-sm font-bold text-gray-900 dark:text-white">Delivery Areas, Fees & Minimums</h3>
            <p class="text-xs text-slate-400">Configure district delivery fees and free delivery order thresholds.</p>
        </div>
        <button @click="addZone = true" class="px-4 py-2 bg-orange-600 hover:bg-orange-500 text-white rounded-xl text-xs font-bold shadow-xs transition">
            + Add Delivery Zone
        </button>
    </div>

    <!-- Zones Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse ($zones as $zone)
            <div class="p-5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-xs space-y-3">
                <div class="flex items-center justify-between">
                    <h4 class="text-sm font-bold text-gray-900 dark:text-white">{{ $zone->zone_name }}</h4>
                    <span class="text-xs font-black text-emerald-600 font-mono">${{ number_format($zone->delivery_fee, 2) }}</span>
                </div>

                <div class="text-xs text-slate-500 space-y-1">
                    <p>Min Order: <strong class="text-gray-900 dark:text-white">${{ number_format($zone->min_order_amount, 2) }}</strong></p>
                    <p>Est. Time: <strong class="text-gray-900 dark:text-white">{{ $zone->estimated_delivery_minutes }} mins</strong></p>
                    <p>Free Delivery Above: <strong class="text-purple-600">{{ $zone->free_delivery_threshold ? '$' . number_format($zone->free_delivery_threshold, 2) : 'None' }}</strong></p>
                </div>

                <div class="pt-3 border-t border-gray-100 dark:border-gray-700/60 flex justify-end">
                    <form method="POST" action="{{ route('business.delivery-zones.destroy', $zone) }}" onsubmit="return confirm('Remove delivery zone?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-xs text-red-500 hover:text-red-600">Delete</button>
                    </form>
                </div>
            </div>
        @empty
            <div class="col-span-full p-12 text-center text-xs text-slate-400 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700/60">
                No delivery zones defined yet. Standard store pickup is enabled.
            </div>
        @endforelse
    </div>

    <!-- Add Zone Modal -->
    <div x-show="addZone" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/80 p-4" style="display: none;">
        <div @click.away="addZone = false" class="bg-white dark:bg-gray-800 rounded-3xl max-w-sm w-full p-6 border border-gray-700 shadow-2xl">
            <h3 class="text-base font-black text-gray-900 dark:text-white">Add Delivery Zone</h3>

            <form method="POST" action="{{ route('business.delivery-zones.store') }}" class="mt-4 space-y-3 text-xs">
                @csrf
                <div>
                    <label class="block font-bold text-slate-500 mb-1">Zone Name</label>
                    <input type="text" name="zone_name" required placeholder="e.g. BKK1 & Chamkarmon" class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 p-2.5 text-xs text-gray-900 dark:text-white" />
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-500 mb-1">Delivery Fee ($)</label>
                        <input type="number" step="0.01" name="delivery_fee" value="1.50" required class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 p-2.5 text-xs text-gray-900 dark:text-white font-mono font-bold" />
                    </div>
                    <div>
                        <label class="block font-bold text-slate-500 mb-1">Min Order ($)</label>
                        <input type="number" step="0.01" name="min_order_amount" value="5.00" required class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 p-2.5 text-xs text-gray-900 dark:text-white font-mono font-bold" />
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-500 mb-1">Free Delivery At ($)</label>
                        <input type="number" step="0.01" name="free_delivery_threshold" placeholder="e.g. 25.00" class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 p-2.5 text-xs text-gray-900 dark:text-white font-mono" />
                    </div>
                    <div>
                        <label class="block font-bold text-slate-500 mb-1">Est. Minutes</label>
                        <input type="number" name="estimated_delivery_minutes" value="30" required class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 p-2.5 text-xs text-gray-900 dark:text-white font-bold" />
                    </div>
                </div>

                <div class="mt-5 pt-3 border-t border-gray-100 dark:border-gray-700/60 flex justify-end gap-2">
                    <button type="button" @click="addZone = false" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-xl text-xs font-bold">Cancel</button>
                    <button type="submit" class="px-5 py-2 bg-orange-600 hover:bg-orange-500 text-white rounded-xl text-xs font-bold shadow-xs">Save Zone</button>
                </div>
            </form>
        </div>
    </div>

</div>
</x-business-layout>
