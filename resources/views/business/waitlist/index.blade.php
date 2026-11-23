<x-business-layout title="Customer Waitlist" heading="Customer Waitlist Queue">
<div class="space-y-6" x-data="{ addQueue: false }">

    @if (session('status'))
        <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-xs font-bold text-emerald-800 dark:text-emerald-200">
            {{ session('status') }}
        </div>
    @endif

    <div class="p-4 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-2xl shadow-xs flex items-center justify-between">
        <div>
            <h3 class="text-sm font-bold text-gray-900 dark:text-white">Live Walk-in Queue</h3>
            <p class="text-xs text-slate-400">Manage waiting diners during peak dining hours.</p>
        </div>
        <button @click="addQueue = true" class="px-4 py-2 bg-orange-600 hover:bg-orange-500 text-white rounded-xl text-xs font-bold shadow-xs transition">
            + Add to Waitlist
        </button>
    </div>

    <!-- Active Waitlist Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse ($activeWaitlist as $wait)
            <div class="p-5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-xs space-y-3">
                <div class="flex items-center justify-between">
                    <span class="text-lg font-black text-orange-500 font-mono">{{ $wait->queue_number }}</span>
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full {{ $wait->status === 'notified' ? 'bg-blue-50 text-blue-600' : 'bg-amber-50 text-amber-600' }}">
                        {{ strtoupper($wait->status) }}
                    </span>
                </div>

                <div>
                    <h4 class="text-sm font-bold text-gray-900 dark:text-white">{{ $wait->customer_name }}</h4>
                    <p class="text-xs text-slate-500">{{ $wait->party_size }} Guests &bull; Est. {{ $wait->estimated_wait_minutes }} min wait</p>
                    <p class="text-[11px] font-mono text-slate-400 mt-1">Phone: {{ $wait->customer_phone }}</p>
                </div>

                <div class="pt-3 border-t border-gray-100 dark:border-gray-700/60 flex items-center gap-2">
                    <form method="POST" action="{{ route('business.waitlist.update-status', $wait) }}" class="flex-1">
                        @csrf
                        <input type="hidden" name="status" value="seated" />
                        <button type="submit" class="w-full py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-bold transition">
                            Seat Party &check;
                        </button>
                    </form>
                    <form method="POST" action="{{ route('business.waitlist.update-status', $wait) }}">
                        @csrf
                        <input type="hidden" name="status" value="cancelled" />
                        <button type="submit" class="py-2 px-3 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 rounded-xl text-xs font-bold hover:bg-gray-200">
                            Cancel
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div class="col-span-full p-12 text-center text-xs text-slate-400 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700/60">
                No customers currently waiting in queue.
            </div>
        @endforelse
    </div>

    <!-- Modal: Add Party to Waitlist -->
    <div x-show="addQueue" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/80 p-4" style="display: none;">
        <div @click.away="addQueue = false" class="bg-white dark:bg-gray-800 rounded-3xl max-w-sm w-full p-6 border border-gray-700 shadow-2xl">
            <h3 class="text-base font-black text-gray-900 dark:text-white">Add Party to Waitlist</h3>

            <form method="POST" action="{{ route('business.waitlist.store') }}" class="mt-4 space-y-3 text-xs">
                @csrf
                <div>
                    <label class="block font-bold text-slate-500 mb-1">Customer Name</label>
                    <input type="text" name="customer_name" required class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 p-2.5 text-xs text-gray-900 dark:text-white" />
                </div>
                <div>
                    <label class="block font-bold text-slate-500 mb-1">Phone Number</label>
                    <input type="text" name="customer_phone" required placeholder="012 345 678" class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 p-2.5 text-xs text-gray-900 dark:text-white" />
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-500 mb-1">Party Size</label>
                        <input type="number" name="party_size" value="2" min="1" max="50" required class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 p-2.5 text-xs text-gray-900 dark:text-white font-bold" />
                    </div>
                    <div>
                        <label class="block font-bold text-slate-500 mb-1">Est. Wait (min)</label>
                        <input type="number" name="estimated_wait_minutes" value="15" min="1" max="180" class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 p-2.5 text-xs text-gray-900 dark:text-white font-bold" />
                    </div>
                </div>

                <div class="mt-5 pt-3 border-t border-gray-100 dark:border-gray-700/60 flex justify-end gap-2">
                    <button type="button" @click="addQueue = false" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-xl text-xs font-bold">Cancel</button>
                    <button type="submit" class="px-5 py-2 bg-orange-600 hover:bg-orange-500 text-white rounded-xl text-xs font-bold shadow-xs">Generate Ticket</button>
                </div>
            </form>
        </div>
    </div>

</div>
</x-business-layout>
