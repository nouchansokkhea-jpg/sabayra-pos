<x-business-layout title="Referral Program" heading="Customer Referral Program">
<div class="space-y-6" x-data="{ addRef: false }">

    @if (session('status'))
        <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-xs font-bold text-emerald-800 dark:text-emerald-200">
            {{ session('status') }}
        </div>
    @endif

    <!-- Referral Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="p-5 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-2xl shadow-xs">
            <span class="text-xs font-bold text-slate-400 uppercase">Registered Advocates</span>
            <p class="text-2xl font-black text-gray-900 dark:text-white mt-1">{{ $stats['total_advocates'] }}</p>
        </div>
        <div class="p-5 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-2xl shadow-xs">
            <span class="text-xs font-bold text-slate-400 uppercase">Referrals Completed</span>
            <p class="text-2xl font-black text-emerald-600 mt-1">{{ $stats['total_referrals_completed'] }}</p>
        </div>
    </div>

    <div class="p-4 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-2xl shadow-xs flex items-center justify-between">
        <div>
            <h3 class="text-sm font-bold text-gray-900 dark:text-white">Word-of-Mouth Advocate Codes</h3>
            <p class="text-xs text-slate-400">Reward loyal customers who bring their friends and colleagues to your store.</p>
        </div>
        <button @click="addRef = true" class="px-4 py-2 bg-orange-600 hover:bg-orange-500 text-white rounded-xl text-xs font-bold shadow-xs transition">
            + Generate Referral Code
        </button>
    </div>

    <!-- Referral Codes Table -->
    <div class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-2xl shadow-xs overflow-hidden">
        <table class="w-full text-left text-xs">
            <thead class="bg-slate-50 dark:bg-gray-900/60 border-b border-gray-200 dark:border-gray-700/60 text-slate-500 uppercase tracking-wider text-[10px]">
                <tr>
                    <th class="p-4">Advocate Name</th>
                    <th class="p-4">Referral Code</th>
                    <th class="p-4">Reward</th>
                    <th class="p-4">Times Redeemed</th>
                    <th class="p-4 text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                @forelse ($referrals as $r)
                    <tr>
                        <td class="p-4 font-bold text-gray-900 dark:text-white">{{ $r->referrer_name }}</td>
                        <td class="p-4 font-mono font-black text-orange-500">{{ $r->referral_code }}</td>
                        <td class="p-4">{{ $r->reward_description }}</td>
                        <td class="p-4 font-bold">{{ $r->times_used }} times</td>
                        <td class="p-4 text-right">
                            <form method="POST" action="{{ route('business.referrals.destroy', $r) }}" onsubmit="return confirm('Delete code?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-500 hover:text-red-600 font-bold text-xs">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="p-10 text-center text-slate-400">No active referral advocate codes created.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Modal -->
    <div x-show="addRef" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/80 p-4" style="display: none;">
        <div @click.away="addRef = false" class="bg-white dark:bg-gray-800 rounded-3xl max-w-sm w-full p-6 border border-gray-700 shadow-2xl">
            <h3 class="text-base font-black text-gray-900 dark:text-white">Create Referral Code</h3>

            <form method="POST" action="{{ route('business.referrals.store') }}" class="mt-4 space-y-3 text-xs">
                @csrf
                <div>
                    <label class="block font-bold text-slate-500 mb-1">Advocate / Customer Name</label>
                    <input type="text" name="referrer_name" required placeholder="e.g. Sreysros Meng" class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 p-2.5 text-xs text-gray-900 dark:text-white" />
                </div>
                <div>
                    <label class="block font-bold text-slate-500 mb-1">Phone Number</label>
                    <input type="text" name="referrer_phone" placeholder="012 555 777" class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 p-2.5 text-xs text-gray-900 dark:text-white" />
                </div>
                <div>
                    <label class="block font-bold text-slate-500 mb-1">Advocate Reward Description</label>
                    <input type="text" name="reward_description" value="$2 Off for Both Diner & Advocate" required class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 p-2.5 text-xs text-gray-900 dark:text-white" />
                </div>

                <div class="mt-5 pt-3 border-t border-gray-100 dark:border-gray-700/60 flex justify-end gap-2">
                    <button type="button" @click="addRef = false" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-xl text-xs font-bold">Cancel</button>
                    <button type="submit" class="px-5 py-2 bg-orange-600 hover:bg-orange-500 text-white rounded-xl text-xs font-bold shadow-xs">Generate</button>
                </div>
            </form>
        </div>
    </div>

</div>
</x-business-layout>
