<x-business-layout title="Coupons & Discounts" heading="Coupons & Discount Vouchers">
<div class="space-y-6" x-data="{ addCoupon: false }">

    @if (session('status'))
        <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-xs font-bold text-emerald-800 dark:text-emerald-200">
            {{ session('status') }}
        </div>
    @endif

    <div class="p-4 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-2xl shadow-xs flex items-center justify-between">
        <div>
            <h3 class="text-sm font-bold text-gray-900 dark:text-white">Promotional Codes & Vouchers</h3>
            <p class="text-xs text-slate-400">Offer percentage or fixed dollar discounts during campaigns.</p>
        </div>
        <button @click="addCoupon = true" class="px-4 py-2 bg-orange-600 hover:bg-orange-500 text-white rounded-xl text-xs font-bold shadow-xs transition">
            + Create Coupon
        </button>
    </div>

    <!-- Coupons Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse ($coupons as $coup)
            <div class="p-5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-xs space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-base font-black font-mono text-orange-500 bg-orange-500/10 px-2.5 py-0.5 rounded-lg border border-orange-500/20">
                        {{ $coup->code }}
                    </span>
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full {{ $coup->is_active ? 'bg-emerald-50 text-emerald-600' : 'bg-gray-100 text-gray-500' }}">
                        {{ $coup->is_active ? 'ACTIVE' : 'INACTIVE' }}
                    </span>
                </div>

                <div class="text-xs text-slate-500 space-y-1">
                    <p class="font-bold text-gray-900 dark:text-white">
                        Discount: {{ $coup->discount_type === 'percentage' ? (float) $coup->discount_value . '%' : '$' . number_format($coup->discount_value, 2) }}
                    </p>
                    <p>Min Order: ${{ number_format($coup->min_order_amount, 2) }} &bull; Used: {{ $coup->used_count }} times</p>
                    @if ($coup->expires_at)
                        <p class="text-[11px] text-slate-400">Expires: {{ $coup->expires_at->format('M d, Y') }}</p>
                    @endif
                </div>

                <div class="pt-3 border-t border-gray-100 dark:border-gray-700/60 flex items-center justify-between text-xs">
                    <form method="POST" action="{{ route('business.coupons.toggle', $coup) }}">
                        @csrf
                        <button type="submit" class="font-bold text-blue-500 hover:text-blue-600">
                            {{ $coup->is_active ? 'Pause' : 'Activate' }}
                        </button>
                    </form>
                    <form method="POST" action="{{ route('business.coupons.destroy', $coup) }}" onsubmit="return confirm('Delete coupon?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="font-bold text-red-500 hover:text-red-600">Delete</button>
                    </form>
                </div>
            </div>
        @empty
            <div class="col-span-full p-12 text-center text-xs text-slate-400 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700/60">
                No active promotional coupons. Create one to share on Facebook or Telegram!
            </div>
        @endforelse
    </div>

    <!-- Create Coupon Modal -->
    <div x-show="addCoupon" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/80 p-4" style="display: none;">
        <div @click.away="addCoupon = false" class="bg-white dark:bg-gray-800 rounded-3xl max-w-sm w-full p-6 border border-gray-700 shadow-2xl">
            <h3 class="text-base font-black text-gray-900 dark:text-white">Create Coupon Code</h3>

            <form method="POST" action="{{ route('business.coupons.store') }}" class="mt-4 space-y-3 text-xs">
                @csrf
                <div>
                    <label class="block font-bold text-slate-500 mb-1">Coupon Code (e.g. WELCOME10)</label>
                    <input type="text" name="code" required class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 p-2.5 text-xs text-gray-900 dark:text-white font-mono font-bold uppercase" />
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-500 mb-1">Type</label>
                        <select name="discount_type" class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 p-2.5 text-xs text-gray-900 dark:text-white">
                            <option value="percentage">Percentage (%)</option>
                            <option value="fixed">Fixed Dollar ($)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-500 mb-1">Discount Value</label>
                        <input type="number" step="0.01" name="discount_value" value="10.00" required class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 p-2.5 text-xs text-gray-900 dark:text-white font-mono font-bold" />
                    </div>
                </div>
                <div>
                    <label class="block font-bold text-slate-500 mb-1">Minimum Order ($)</label>
                    <input type="number" step="0.01" name="min_order_amount" value="5.00" class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 p-2.5 text-xs text-gray-900 dark:text-white font-mono" />
                </div>

                <div class="mt-5 pt-3 border-t border-gray-100 dark:border-gray-700/60 flex justify-end gap-2">
                    <button type="button" @click="addCoupon = false" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-xl text-xs font-bold">Cancel</button>
                    <button type="submit" class="px-5 py-2 bg-orange-600 hover:bg-orange-500 text-white rounded-xl text-xs font-bold shadow-xs">Publish Coupon</button>
                </div>
            </form>
        </div>
    </div>

</div>
</x-business-layout>
