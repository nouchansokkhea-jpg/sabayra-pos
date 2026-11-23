<x-business-layout title="Live Orders Feed" heading="Live Orders Kanban">
<div class="space-y-6" x-data="{
    soundEnabled: localStorage.getItem('sabayra_kds_sound') !== 'false',
    lastPendingCount: {{ $pending->count() }},
    isRefreshing: false,
    toggleSound() {
        this.soundEnabled = !this.soundEnabled;
        localStorage.setItem('sabayra_kds_sound', this.soundEnabled);
        if (this.soundEnabled) this.playAlert();
    },
    playAlert() {
        if (!this.soundEnabled) return;
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = 'triangle';
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.frequency.setValueAtTime(587.33, ctx.currentTime);
            osc.frequency.exponentialRampToValueAtTime(880, ctx.currentTime + 0.15);
            gain.gain.setValueAtTime(0.3, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.3);
            osc.start();
            osc.stop(ctx.currentTime + 0.3);
        } catch(e) {}
    },
    pollLiveOrders() {
        fetch('{{ route('business.orders.live.poll') }}')
            .then(res => res.json())
            .then(data => {
                if (data.has_pending && data.total_active > this.lastPendingCount) {
                    this.playAlert();
                }
                this.lastPendingCount = data.total_active;
            })
            .catch(() => {});
    },
    init() {
        setInterval(() => this.pollLiveOrders(), 10000);
    }
}">

    <!-- Top Live Status & Tool Bar -->
    <div class="p-4 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-2xl shadow-xs flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center space-x-3">
            <span class="relative flex h-3 w-3">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
            </span>
            <div>
                <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <span>Real-Time Kitchen & Floor Feed</span>
                    <span class="text-[10px] bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 px-2 py-0.5 rounded-full font-mono font-bold">10s AUTO-SYNC</span>
                </h3>
                <p class="text-xs text-slate-500 dark:text-slate-400">Incoming QR orders, Touch POS checkouts, and fulfillment queue</p>
            </div>
        </div>

        <div class="flex items-center space-x-2.5">
            <button @click="toggleSound()"
                    class="px-3 py-1.5 rounded-xl text-xs font-bold border transition flex items-center space-x-1.5"
                    :class="soundEnabled ? 'bg-emerald-50 text-emerald-700 border-emerald-300 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-700' : 'bg-gray-100 text-gray-500 border-gray-300 dark:bg-gray-700 dark:text-gray-400'">
                <span x-text="soundEnabled ? '🔔 Sound Alerts: ON' : '🔕 Sound Alerts: OFF'"></span>
            </button>

            <button @click="window.location.reload()"
                    class="px-3 py-1.5 rounded-xl text-xs font-bold bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 border border-gray-200 dark:border-gray-600 transition flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                <span>Refresh</span>
            </button>

            <a href="{{ route('business.kitchen.index') }}" target="_blank"
               class="px-3.5 py-1.5 bg-orange-600 hover:bg-orange-500 text-white rounded-xl text-xs font-bold shadow-xs transition flex items-center gap-1.5">
                <span>Fullscreen KDS</span>
                <span>&rarr;</span>
            </a>
        </div>
    </div>

    <!-- 4-Column Live Kanban Board -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 items-start">

        <!-- Column 1: New / Pending -->
        <div class="bg-amber-50/60 dark:bg-gray-900/40 p-3.5 rounded-2xl border border-amber-200/80 dark:border-gray-800 flex flex-col min-h-[500px]">
            <div class="flex items-center justify-between pb-3 mb-3 border-b border-amber-200/70 dark:border-gray-800">
                <span class="text-xs font-black uppercase text-amber-700 dark:text-amber-400 flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                    1. New Pending ({{ $pending->count() }})
                </span>
                <span class="text-[10px] text-amber-600 dark:text-amber-400 font-mono font-bold bg-amber-100 dark:bg-amber-950/60 px-2 py-0.5 rounded-full">ACTION NEEDED</span>
            </div>

            <div class="space-y-3 flex-1 overflow-y-auto max-h-[75vh]">
                @forelse ($pending as $order)
                    <div class="p-4 bg-white dark:bg-gray-800 rounded-xl border border-amber-300 dark:border-amber-900/60 shadow-xs space-y-3 transition hover:shadow-md">
                        <div class="flex items-center justify-between">
                            <span class="font-black text-gray-900 dark:text-white text-sm">#{{ $order->order_number }}</span>
                            <span class="text-[10px] text-slate-600 dark:text-slate-300 font-medium">{{ $order->created_at->diffForHumans() }}</span>
                        </div>
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-bold text-gray-700 dark:text-gray-300">
                                {{ $order->table ? ($order->table->name ?? 'Table ' . $order->table->table_number) : ($order->customer_name ?: 'Takeaway Guest') }}
                            </span>
                            <span class="text-[10px] uppercase font-bold px-1.5 py-0.5 rounded {{ $order->order_type === 'dine_in' ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-300' : 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300' }}">
                                {{ str_replace('_', ' ', $order->order_type) }}
                            </span>
                        </div>
                        <div class="text-xs text-slate-600 dark:text-slate-300 divide-y divide-gray-100 dark:divide-gray-700/60 pt-1">
                            @foreach ($order->items as $it)
                                <div class="py-1 flex justify-between items-start">
                                    <span>
                                        <strong class="text-orange-600 font-bold">{{ (int) $it->quantity }}x</strong> {{ $it->product_name }}
                                        @if ($it->variant)
                                            <span class="text-[10px] text-slate-600 dark:text-slate-300">({{ $it->variant->name }})</span>
                                        @endif
                                    </span>
                                    <span class="font-mono font-medium text-slate-500">${{ number_format($it->subtotal, 2) }}</span>
                                </div>
                            @endforeach
                        </div>
                        @if ($order->notes)
                            <div class="p-2 rounded bg-amber-50 dark:bg-amber-950/30 border border-amber-200/50 dark:border-amber-900/40 text-[11px] text-amber-800 dark:text-amber-300">
                                <strong>Note:</strong> {{ $order->notes }}
                            </div>
                        @endif
                        <div class="pt-2 flex justify-between items-center border-t border-gray-100 dark:border-gray-700/60">
                            <div>
                                <span class="text-sm font-black text-emerald-600 dark:text-emerald-400 font-mono">${{ number_format($order->total_amount, 2) }}</span>
                                <span class="text-[10px] text-slate-600 dark:text-slate-300 block font-mono font-medium">៛{{ number_format($order->total_amount * 4100) }}</span>
                            </div>
                            <form method="POST" action="{{ route('business.orders.update-status', $order) }}">
                                @csrf
                                <input type="hidden" name="status" value="confirmed" />
                                <button type="submit" class="px-3 py-1.5 bg-amber-600 hover:bg-amber-500 text-white rounded-lg text-xs font-bold shadow-xs transition flex items-center gap-1">
                                    <span>Accept</span>
                                    <span>&check;</span>
                                </button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center text-xs text-slate-600 dark:text-slate-300 font-medium">No new pending tickets.</div>
                @endforelse
            </div>
        </div>

        <!-- Column 2: Confirmed / Accepted -->
        <div class="bg-blue-50/60 dark:bg-gray-900/40 p-3.5 rounded-2xl border border-blue-200/80 dark:border-gray-800 flex flex-col min-h-[500px]">
            <div class="flex items-center justify-between pb-3 mb-3 border-b border-blue-200/70 dark:border-gray-800">
                <span class="text-xs font-black uppercase text-blue-700 dark:text-blue-400 flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                    2. Confirmed ({{ $confirmed->count() }})
                </span>
                <span class="text-[10px] text-blue-600 dark:text-blue-400 font-mono font-bold bg-blue-100 dark:bg-blue-950/60 px-2 py-0.5 rounded-full">QUEUED</span>
            </div>

            <div class="space-y-3 flex-1 overflow-y-auto max-h-[75vh]">
                @forelse ($confirmed as $order)
                    <div class="p-4 bg-white dark:bg-gray-800 rounded-xl border border-blue-200 dark:border-blue-900/60 shadow-xs space-y-3 transition hover:shadow-md">
                        <div class="flex items-center justify-between">
                            <span class="font-black text-gray-900 dark:text-white text-sm">#{{ $order->order_number }}</span>
                            <span class="text-[10px] text-slate-600 dark:text-slate-300 font-medium">{{ $order->created_at->diffForHumans() }}</span>
                        </div>
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-bold text-gray-700 dark:text-gray-300">
                                {{ $order->table ? ($order->table->name ?? 'Table ' . $order->table->table_number) : ($order->customer_name ?: 'Takeaway Guest') }}
                            </span>
                            <span class="text-[10px] text-blue-600 font-bold bg-blue-50 dark:bg-blue-950/40 px-2 py-0.5 rounded">Queued</span>
                        </div>
                        <div class="text-xs text-slate-600 dark:text-slate-300 divide-y divide-gray-100 dark:divide-gray-700/60 pt-1">
                            @foreach ($order->items as $it)
                                <div class="py-1 flex justify-between items-start">
                                    <span><strong class="text-blue-600 font-bold">{{ (int) $it->quantity }}x</strong> {{ $it->product_name }}</span>
                                    <span class="font-mono text-slate-500">${{ number_format($it->subtotal, 2) }}</span>
                                </div>
                            @endforeach
                        </div>
                        <div class="pt-2 flex justify-between items-center border-t border-gray-100 dark:border-gray-700/60">
                            <span class="text-xs font-black text-emerald-600 font-mono">${{ number_format($order->total_amount, 2) }}</span>
                            <form method="POST" action="{{ route('business.orders.update-status', $order) }}">
                                @csrf
                                <input type="hidden" name="status" value="preparing" />
                                <button type="submit" class="px-3 py-1.5 bg-blue-600 hover:bg-blue-500 text-white rounded-lg text-xs font-bold shadow-xs transition flex items-center gap-1">
                                    <span>Send to Kitchen</span>
                                    <span>&rarr;</span>
                                </button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center text-xs text-slate-600 dark:text-slate-300 font-medium">No confirmed tickets queued.</div>
                @endforelse
            </div>
        </div>

        <!-- Column 3: In Kitchen / Preparing -->
        <div class="bg-purple-50/60 dark:bg-gray-900/40 p-3.5 rounded-2xl border border-purple-200/80 dark:border-gray-800 flex flex-col min-h-[500px]">
            <div class="flex items-center justify-between pb-3 mb-3 border-b border-purple-200/70 dark:border-gray-800">
                <span class="text-xs font-black uppercase text-purple-700 dark:text-purple-400 flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-purple-500 animate-pulse"></span>
                    3. In Kitchen ({{ $preparing->count() }})
                </span>
                <span class="text-[10px] text-purple-600 dark:text-purple-400 font-mono font-bold bg-purple-100 dark:bg-purple-950/60 px-2 py-0.5 rounded-full">COOKING</span>
            </div>

            <div class="space-y-3 flex-1 overflow-y-auto max-h-[75vh]">
                @forelse ($preparing as $order)
                    <div class="p-4 bg-white dark:bg-gray-800 rounded-xl border border-purple-300 dark:border-purple-900/60 shadow-xs space-y-3 transition hover:shadow-md">
                        <div class="flex items-center justify-between">
                            <span class="font-black text-gray-900 dark:text-white text-sm">#{{ $order->order_number }}</span>
                            <span class="text-[10px] text-purple-600 font-mono font-bold bg-purple-50 dark:bg-purple-950/50 px-2 py-0.5 rounded">Cooking</span>
                        </div>
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-bold text-gray-700 dark:text-gray-300">
                                {{ $order->table ? ($order->table->name ?? 'Table ' . $order->table->table_number) : ($order->customer_name ?: 'Takeaway Guest') }}
                            </span>
                            <span class="text-[10px] text-slate-600 dark:text-slate-300 font-medium">{{ $order->created_at->diffForHumans() }}</span>
                        </div>
                        <div class="text-xs text-slate-600 dark:text-slate-300 divide-y divide-gray-100 dark:divide-gray-700/60 pt-1">
                            @foreach ($order->items as $it)
                                <div class="py-1 flex justify-between items-start">
                                    <span><strong class="text-purple-600 font-bold">{{ (int) $it->quantity }}x</strong> {{ $it->product_name }}</span>
                                    <span class="font-mono text-slate-500">${{ number_format($it->subtotal, 2) }}</span>
                                </div>
                            @endforeach
                        </div>
                        <div class="pt-2 flex justify-between items-center border-t border-gray-100 dark:border-gray-700/60">
                            <span class="text-xs font-black text-emerald-600 font-mono">${{ number_format($order->total_amount, 2) }}</span>
                            <form method="POST" action="{{ route('business.orders.update-status', $order) }}">
                                @csrf
                                <input type="hidden" name="status" value="ready" />
                                <button type="submit" class="px-3 py-1.5 bg-purple-600 hover:bg-purple-500 text-white rounded-lg text-xs font-bold shadow-xs transition flex items-center gap-1">
                                    <span>Food Ready</span>
                                    <span>&rarr;</span>
                                </button>
                            </form>
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center text-xs text-slate-400">Kitchen is idle. No cooking tickets.</div>
                @endforelse
            </div>
        </div>

        <!-- Column 4: Ready for Service / Pickup -->
        <div class="bg-emerald-50/60 dark:bg-gray-900/40 p-3.5 rounded-2xl border border-emerald-200/80 dark:border-gray-800 flex flex-col min-h-[500px]">
            <div class="flex items-center justify-between pb-3 mb-3 border-b border-emerald-200/70 dark:border-gray-800">
                <span class="text-xs font-black uppercase text-emerald-700 dark:text-emerald-400 flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    4. Ready ({{ $ready->count() }})
                </span>
                <span class="text-[10px] text-emerald-600 dark:text-emerald-400 font-mono font-bold bg-emerald-100 dark:bg-emerald-950/60 px-2 py-0.5 rounded-full">PICKUP</span>
            </div>

            <div class="space-y-3 flex-1 overflow-y-auto max-h-[75vh]">
                @forelse ($ready as $order)
                    <div class="p-4 bg-white dark:bg-gray-800 rounded-xl border border-emerald-300 dark:border-emerald-900/60 shadow-xs space-y-3 transition hover:shadow-md">
                        <div class="flex items-center justify-between">
                            <span class="font-black text-gray-900 dark:text-white text-sm">#{{ $order->order_number }}</span>
                            <span class="text-[10px] text-emerald-600 font-mono font-bold bg-emerald-50 dark:bg-emerald-950/50 px-2 py-0.5 rounded">Ready to Serve</span>
                        </div>
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-bold text-gray-700 dark:text-gray-300">
                                {{ $order->table ? ($order->table->name ?? 'Table ' . $order->table->table_number) : ($order->customer_name ?: 'Takeaway Guest') }}
                            </span>
                            <span class="text-[10px] text-slate-600 dark:text-slate-300 font-medium">{{ $order->created_at->diffForHumans() }}</span>
                        </div>
                        <div class="text-xs text-slate-600 dark:text-slate-300 divide-y divide-gray-100 dark:divide-gray-700/60 pt-1">
                            @foreach ($order->items as $it)
                                <div class="py-1 flex justify-between items-start">
                                    <span><strong class="text-emerald-600 font-bold">{{ (int) $it->quantity }}x</strong> {{ $it->product_name }}</span>
                                    <span class="font-mono text-slate-500">${{ number_format($it->subtotal, 2) }}</span>
                                </div>
                            @endforeach
                        </div>
                        <div class="pt-2 flex justify-between items-center border-t border-gray-100 dark:border-gray-700/60">
                            <div>
                                <span class="text-xs font-black text-emerald-600 font-mono">${{ number_format($order->total_amount, 2) }}</span>
                                <span class="text-[10px] text-slate-600 dark:text-slate-300 block font-mono font-medium">៛{{ number_format($order->total_amount * 4100) }}</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <a href="{{ route('business.orders.receipt', $order) }}" target="_blank"
                                   class="px-2.5 py-1.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-lg text-xs font-bold transition">
                                    Print
                                </a>
                                <form method="POST" action="{{ route('business.orders.update-status', $order) }}">
                                    @csrf
                                    <input type="hidden" name="status" value="completed" />
                                    <button type="submit" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-bold shadow-xs transition flex items-center gap-1">
                                        <span>Complete</span>
                                        <span>&check;</span>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center text-xs text-slate-600 dark:text-slate-300 font-medium">No orders awaiting pickup or delivery.</div>
                @endforelse
            </div>
        </div>

    </div>

</div>
</x-business-layout>
