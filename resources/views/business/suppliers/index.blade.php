<x-business-layout title="Suppliers & POs" heading="Suppliers & Purchase Orders">
<div class="space-y-6" x-data="{ addSup: false, addPo: false }">

    @if (session('status'))
        <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-xs font-bold text-emerald-800 dark:text-emerald-200">
            {{ session('status') }}
        </div>
    @endif

    <div class="p-4 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-2xl shadow-xs flex flex-wrap items-center justify-between gap-4">
        <div>
            <h3 class="text-sm font-bold text-gray-900 dark:text-white">Vendor Directory & Procurement</h3>
            <p class="text-xs text-slate-400">Manage supplier contacts, wholesale costs, and purchase orders.</p>
        </div>
        <div class="flex items-center gap-2">
            <button @click="addSup = true" class="px-3.5 py-2 bg-slate-800 hover:bg-slate-700 text-white rounded-xl text-xs font-bold transition">
                + Register Supplier
            </button>
            <button @click="addPo = true" class="px-3.5 py-2 bg-orange-600 hover:bg-orange-500 text-white rounded-xl text-xs font-bold shadow-xs transition">
                + New Purchase Order
            </button>
        </div>
    </div>

    <!-- Two Columns: Suppliers List & POs List -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        <!-- Suppliers Directory -->
        <div class="p-5 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-2xl shadow-xs">
            <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-4">Registered Suppliers ({{ $suppliers->count() }})</h4>

            <div class="space-y-3">
                @forelse ($suppliers as $sup)
                    <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700/60 text-xs space-y-1">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-gray-900 dark:text-white">{{ $sup->name }}</span>
                            <span class="text-slate-400 font-mono">{{ $sup->phone }}</span>
                        </div>
                        <p class="text-slate-500">{{ $sup->contact_person ? 'Contact: ' . $sup->contact_person : 'Vendor' }} &bull; {{ $sup->address ?: 'Phnom Penh' }}</p>
                    </div>
                @empty
                    <div class="p-8 text-center text-xs text-slate-400">No suppliers registered.</div>
                @endforelse
            </div>
        </div>

        <!-- Purchase Orders History -->
        <div class="p-5 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-2xl shadow-xs">
            <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-4">Recent Purchase Orders ({{ $purchaseOrders->count() }})</h4>

            <div class="space-y-3">
                @forelse ($purchaseOrders as $po)
                    <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700/60 text-xs space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="font-mono font-bold text-gray-900 dark:text-white">{{ $po->po_number }}</span>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full uppercase
                                {{ $po->status === 'received' ? 'bg-emerald-50 text-emerald-600' : 'bg-amber-50 text-amber-600' }}">
                                {{ $po->status }}
                            </span>
                        </div>
                        <p class="text-slate-500">Supplier: <strong class="text-gray-900 dark:text-white">{{ $po->supplier ? $po->supplier->name : 'Supplier' }}</strong> &bull; Total: <strong class="text-emerald-600">${{ number_format($po->total_cost, 2) }}</strong></p>
                        
                        @if ($po->status !== 'received')
                            <form method="POST" action="{{ route('business.suppliers.po.status', $po) }}" class="pt-1">
                                @csrf
                                <input type="hidden" name="status" value="received" />
                                <button type="submit" class="px-3 py-1 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-[10px] font-bold">
                                    Mark Goods Received & Stock In &rarr;
                                </button>
                            </form>
                        @endif
                    </div>
                @empty
                    <div class="p-8 text-center text-xs text-slate-400">No purchase orders created yet.</div>
                @endforelse
            </div>
        </div>

    </div>

    <!-- Add Supplier Modal -->
    <div x-show="addSup" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/80 p-4" style="display: none;">
        <div @click.away="addSup = false" class="bg-white dark:bg-gray-800 rounded-3xl max-w-sm w-full p-6 border border-gray-700 shadow-2xl">
            <h3 class="text-base font-black text-gray-900 dark:text-white">Register Supplier</h3>
            <form method="POST" action="{{ route('business.suppliers.store') }}" class="mt-4 space-y-3 text-xs">
                @csrf
                <div>
                    <label class="block font-bold text-slate-500 mb-1">Company / Vendor Name</label>
                    <input type="text" name="name" required class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 p-2.5 text-xs text-gray-900 dark:text-white" />
                </div>
                <div>
                    <label class="block font-bold text-slate-500 mb-1">Contact Person</label>
                    <input type="text" name="contact_person" class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 p-2.5 text-xs text-gray-900 dark:text-white" />
                </div>
                <div>
                    <label class="block font-bold text-slate-500 mb-1">Phone</label>
                    <input type="text" name="phone" placeholder="012 888 999" class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 p-2.5 text-xs text-gray-900 dark:text-white" />
                </div>
                <div class="mt-5 pt-3 border-t border-gray-100 dark:border-gray-700/60 flex justify-end gap-2">
                    <button type="button" @click="addSup = false" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-xl text-xs font-bold">Cancel</button>
                    <button type="submit" class="px-5 py-2 bg-orange-600 hover:bg-orange-500 text-white rounded-xl text-xs font-bold shadow-xs">Register</button>
                </div>
            </form>
        </div>
    </div>

</div>
</x-business-layout>
