<x-business-layout title="Customer Profile" heading="Customer Profile & History">
<div class="space-y-6">

    @if (session('status'))
        <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-xs font-bold text-emerald-800 dark:text-emerald-200">
            {{ session('status') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Left: Customer Information & Notes Form -->
        <div class="p-6 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-2xl shadow-xs space-y-4">
            <div class="flex items-center space-x-3">
                <div class="w-12 h-12 rounded-2xl bg-orange-600 text-white font-black text-lg flex items-center justify-center">
                    {{ strtoupper(substr($customer->name, 0, 1)) }}
                </div>
                <div>
                    <h3 class="text-base font-black text-gray-900 dark:text-white">{{ $customer->name }}</h3>
                    <p class="text-xs text-slate-400">Client since {{ $customer->created_at->format('M Y') }}</p>
                </div>
            </div>

            <div class="p-3.5 bg-slate-50 dark:bg-gray-900/50 rounded-xl space-y-1 text-xs">
                <p>Total Spent: <strong class="text-emerald-600">${{ number_format($customer->total_spent, 2) }}</strong></p>
                <p>Completed Orders: <strong class="text-gray-900 dark:text-white">{{ $customer->total_orders }}</strong></p>
                <p>Loyalty Points: <strong class="text-purple-600">{{ $customer->loyalty_points }} pts</strong></p>
            </div>

            <form method="POST" action="{{ route('business.customers.update', $customer) }}" class="space-y-3 text-xs pt-2">
                @csrf
                @method('PUT')
                <div>
                    <label class="block font-bold text-slate-500 mb-1">Full Name</label>
                    <input type="text" name="name" value="{{ $customer->name }}" required class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 p-2.5 text-xs text-gray-900 dark:text-white font-bold" />
                </div>
                <div>
                    <label class="block font-bold text-slate-500 mb-1">Phone</label>
                    <input type="text" name="phone" value="{{ $customer->phone }}" class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 p-2.5 text-xs text-gray-900 dark:text-white font-mono" />
                </div>
                <div>
                    <label class="block font-bold text-slate-500 mb-1">Email</label>
                    <input type="email" name="email" value="{{ $customer->email }}" class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 p-2.5 text-xs text-gray-900 dark:text-white" />
                </div>
                <div>
                    <label class="block font-bold text-slate-500 mb-1">Delivery Address</label>
                    <textarea name="address" rows="2" class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 p-2.5 text-xs text-gray-900 dark:text-white">{{ $customer->address }}</textarea>
                </div>
                <div>
                    <label class="block font-bold text-slate-500 mb-1">VIP & Dietary Notes</label>
                    <textarea name="notes" rows="2" placeholder="e.g. VIP guest, peanut allergy..." class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 p-2.5 text-xs text-gray-900 dark:text-white">{{ $customer->notes }}</textarea>
                </div>

                <button type="submit" class="w-full py-2.5 bg-orange-600 hover:bg-orange-500 text-white rounded-xl text-xs font-bold shadow-xs transition">
                    Save Profile Updates
                </button>
            </form>
        </div>

        <!-- Right: Recent Order History -->
        <div class="lg:col-span-2 p-6 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-2xl shadow-xs">
            <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-4">Past Transactions & Orders</h4>

            <div class="divide-y divide-gray-100 dark:divide-gray-700/60">
                @forelse ($orders as $ord)
                    <div class="py-3 flex items-center justify-between text-xs">
                        <div>
                            <span class="font-mono font-bold text-gray-900 dark:text-white">#{{ $ord->order_number }}</span>
                            <span class="text-slate-400 text-[10px] ml-2">{{ $ord->created_at->format('M d, Y H:i') }}</span>
                            <p class="text-[11px] text-slate-500 mt-0.5 capitalize">{{ str_replace('_', ' ', $ord->order_type) }} &bull; {{ $ord->payment_method }}</p>
                        </div>
                        <div class="text-right">
                            <span class="font-black text-emerald-600 font-mono">${{ number_format($ord->total_amount, 2) }}</span>
                            <span class="block text-[10px] font-bold uppercase {{ $ord->status === 'completed' ? 'text-emerald-500' : 'text-amber-500' }}">
                                {{ $ord->status }}
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="py-10 text-center text-xs text-slate-400">No orders logged under this profile.</div>
                @endforelse
            </div>
        </div>

    </div>

</div>
</x-business-layout>
