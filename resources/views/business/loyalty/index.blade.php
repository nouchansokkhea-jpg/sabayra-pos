<x-business-layout title="Loyalty Program" heading="Customer Loyalty & Rewards">
<div class="space-y-6">

    @if (session('status'))
        <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-xs font-bold text-emerald-800 dark:text-emerald-200">
            {{ session('status') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Program Settings -->
        <div class="p-6 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-2xl shadow-xs space-y-4">
            <h3 class="text-sm font-bold text-gray-900 dark:text-white">Loyalty Rules & Earning Rate</h3>

            <form method="POST" action="{{ route('business.loyalty.update') }}" class="space-y-4 text-xs">
                @csrf
                @method('PUT')

                <div>
                    <label class="block font-bold text-slate-500 mb-1">Points Earned Per $1 Spent</label>
                    <input type="number" name="points_per_dollar" value="{{ $settings['points_per_dollar'] }}" min="1" max="1000" class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 p-2.5 text-xs text-gray-900 dark:text-white font-mono font-bold" />
                </div>

                <div>
                    <label class="block font-bold text-slate-500 mb-1">Points Required For $1 Discount</label>
                    <input type="number" name="redemption_rate" value="{{ $settings['redemption_rate'] }}" min="10" max="10000" class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 p-2.5 text-xs text-gray-900 dark:text-white font-mono font-bold" />
                </div>

                <label class="flex items-center space-x-2 pt-2">
                    <input type="checkbox" name="is_enabled" value="1" {{ $settings['is_enabled'] ? 'checked' : '' }} class="rounded text-orange-600" />
                    <span class="font-bold text-gray-900 dark:text-white">Enable Loyalty Program on Digital Checkout</span>
                </label>

                <button type="submit" class="w-full py-2.5 bg-orange-600 hover:bg-orange-500 text-white rounded-xl text-xs font-bold shadow-xs transition">
                    Save Loyalty Rules
                </button>
            </form>
        </div>

        <!-- Top Loyalty Members Leaderboard -->
        <div class="lg:col-span-2 p-6 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-2xl shadow-xs">
            <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-4">Top Loyalty Point Holders</h4>

            <div class="divide-y divide-gray-100 dark:divide-gray-700/60">
                @forelse ($topMembers as $mem)
                    <div class="py-3 flex items-center justify-between text-xs">
                        <div>
                            <span class="font-bold text-gray-900 dark:text-white">{{ $mem->name }}</span>
                            <span class="text-slate-400 text-[10px] ml-2">{{ $mem->phone }}</span>
                        </div>
                        <span class="font-black text-purple-600 font-mono">{{ number_format($mem->loyalty_points) }} pts</span>
                    </div>
                @empty
                    <div class="py-10 text-center text-xs text-slate-400">No customers have accumulated loyalty points yet.</div>
                @endforelse
            </div>
        </div>

    </div>

</div>
</x-business-layout>
