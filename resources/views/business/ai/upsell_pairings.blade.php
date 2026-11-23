<x-business-layout title="AI Upsell Pairings" heading="AI Upsell Pairings">
<div class="space-y-6">

    <div class="p-5 bg-gradient-to-r from-purple-950/40 via-gray-900 to-indigo-950/30 border border-purple-500/30 rounded-2xl shadow-xs text-white">
        <div class="flex items-center space-x-3">
            <span class="text-3xl">🤝</span>
            <div>
                <h3 class="text-base font-black text-white">Frequently Bought Together Co-Purchase Matrix</h3>
                <p class="text-xs text-slate-300 mt-1">Identifies natural food and drink combinations based on actual order basket history to suggest smart upsells during digital checkout.</p>
            </div>
        </div>
    </div>

    @if ($pairings['has_data'] && count($pairings['pairings']) > 0)
        <div class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-2xl shadow-xs overflow-hidden">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-gray-900/60 border-b border-gray-200 dark:border-gray-700/60 text-slate-500 uppercase tracking-wider text-[10px]">
                    <tr>
                        <th class="p-4">Primary Dish / Item</th>
                        <th class="p-4">Recommended Upsell</th>
                        <th class="p-4">Co-Purchase Count</th>
                        <th class="p-4">Match Confidence</th>
                        <th class="p-4 text-right">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                    @foreach ($pairings['pairings'] as $pair)
                        <tr>
                            <td class="p-4 font-bold text-gray-900 dark:text-white">{{ $pair['main_product_name'] }}</td>
                            <td class="p-4 font-bold text-purple-600 dark:text-purple-400">{{ $pair['pairing_product_name'] }}</td>
                            <td class="p-4 font-mono">{{ $pair['co_purchase_count'] }} orders</td>
                            <td class="p-4">
                                <span class="px-2 py-0.5 rounded-full font-bold text-[10px] bg-purple-50 text-purple-600 dark:bg-purple-950/40 dark:text-purple-300">
                                    {{ $pair['confidence_percent'] }}% Confidence
                                </span>
                            </td>
                            <td class="p-4 text-right">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-400">
                                    Active on Menu
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="p-12 text-center text-xs text-slate-400 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700/60">
            <p class="font-bold text-slate-700 dark:text-slate-200 text-sm">Insufficient Multi-Item Orders</p>
            <p class="mt-2 max-w-md mx-auto">SABAYRA never invents purchase relationships. As guests checkout with multiple items (e.g. food + drink), the AI will automatically compute authentic pairing associations.</p>
        </div>
    @endif

</div>
</x-business-layout>
