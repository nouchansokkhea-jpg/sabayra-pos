<x-business-layout title="Printers" heading="Printer Management">
<div class="space-y-6" x-data="{
    modalOpen: false,
    editMode: false,
    editUrl: '',
    form: {
        id: null,
        name: '',
        printer_type: 'receipt',
        connection_type: 'network',
        printing_purpose: 'both',
        paper_size: '80mm',
        ip_address: '',
        port: 9100,
        branch_id: '',
        is_default: false,
        is_active: true
    },
    openAdd() {
        this.editMode = false;
        this.editUrl = '{{ route('business.printers.store') }}';
        this.form = {
            id: null,
            name: '',
            printer_type: 'receipt',
            connection_type: 'network',
            printing_purpose: 'both',
            paper_size: '80mm',
            ip_address: '',
            port: 9100,
            branch_id: '',
            is_default: false,
            is_active: true
        };
        this.modalOpen = true;
    },
    openEdit(p) {
        this.editMode = true;
        this.editUrl = '/app/printers/' + p.id;
        this.form = {
            id: p.id,
            name: p.name,
            printer_type: p.printer_type || 'receipt',
            connection_type: p.connection_type || 'network',
            printing_purpose: p.printing_purpose || 'both',
            paper_size: p.paper_size || '80mm',
            ip_address: p.ip_address || '',
            port: p.port || 9100,
            branch_id: p.branch_id || '',
            is_default: !!p.is_default,
            is_active: p.is_active !== undefined ? !!p.is_active : true
        };
        this.modalOpen = true;
    }
}">

    <!-- Flash message -->
    @if (session('status'))
        <div class="p-4 rounded-2xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-xs font-bold text-emerald-800 dark:text-emerald-200 flex items-center justify-between shadow-xs">
            <span class="flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <span>{{ session('status') }}</span>
            </span>
        </div>
    @endif

    <!-- Top Management Bar -->
    <div class="p-5 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-2xl shadow-xs flex flex-wrap items-center justify-between gap-4">
        <div>
            <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <svg class="w-5 h-5 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                <span>Printers</span>
            </h3>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Manage receipt printers and kitchen printers for your business.
            </p>
        </div>
        <button @click="openAdd()"
                class="px-4 py-2.5 bg-orange-600 hover:bg-orange-500 text-white rounded-xl text-xs font-bold shadow-xs transition flex items-center gap-1.5">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            <span>Add New Printer</span>
        </button>
    </div>

    <!-- Section 2.2: Printers Grid -->
    @if ($printers->isEmpty())
        <!-- Section 2.4: Empty State -->
        <div class="p-12 text-center bg-white dark:bg-gray-800 rounded-3xl border border-gray-100 dark:border-gray-700/60 shadow-xs max-w-xl mx-auto space-y-4">
            <div class="w-16 h-16 rounded-2xl bg-orange-50 dark:bg-orange-950/30 text-orange-600 dark:text-orange-400 flex items-center justify-center mx-auto shadow-inner">
                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
            </div>
            <div class="space-y-1">
                <h4 class="text-base font-bold text-gray-900 dark:text-white">No Printers Added</h4>
                <p class="text-xs text-slate-500 dark:text-slate-400 max-w-sm mx-auto">
                    Add a printer to start printing receipts and kitchen tickets.
                </p>
            </div>
            <button @click="openAdd()"
                    class="px-5 py-2.5 bg-orange-600 hover:bg-orange-500 text-white rounded-xl text-xs font-bold shadow-xs transition inline-flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Add New Printer</span>
            </button>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            @foreach ($printers as $pr)
                <div class="p-5 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-2xl shadow-xs space-y-3.5 flex flex-col justify-between hover:shadow-md transition">
                    <div>
                        <!-- Header & Badges -->
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <h4 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-1.5">
                                    <span>{{ $pr->name }}</span>
                                    @if ($pr->is_default)
                                        <span class="text-[9px] bg-amber-500/15 text-amber-500 border border-amber-500/30 px-1.5 py-0.5 rounded font-mono font-bold">DEFAULT</span>
                                    @endif
                                </h4>
                                <span class="text-[10px] text-slate-400 font-medium">
                                    Branch: <strong class="text-slate-600 dark:text-slate-300">{{ $pr->branch ? $pr->branch->name : 'All Outlets' }}</strong>
                                </span>
                            </div>
                            <span class="text-[10px] font-mono font-bold px-2 py-0.5 rounded-full uppercase {{ $pr->is_active ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300' : 'bg-gray-100 text-gray-500 dark:bg-gray-700' }}">
                                {{ $pr->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>

                        <!-- Technical Specs Matrix -->
                        <div class="mt-3 p-3 bg-gray-50 dark:bg-gray-900/50 rounded-xl space-y-1.5 text-xs">
                            <div class="flex justify-between items-center text-slate-500 dark:text-slate-400 text-[11px]">
                                <span>Printer Type:</span>
                                <span class="font-bold text-gray-800 dark:text-gray-200 capitalize">{{ $pr->printer_type ?? 'Receipt Printer' }}</span>
                            </div>
                            <div class="flex justify-between items-center text-slate-500 dark:text-slate-400 text-[11px]">
                                <span>Purpose:</span>
                                <span class="font-bold text-gray-800 dark:text-gray-200 capitalize">{{ str_replace('_', ' ', $pr->printing_purpose ?? 'Both') }}</span>
                            </div>
                            <div class="flex justify-between items-center text-slate-500 dark:text-slate-400 text-[11px]">
                                <span>Connection:</span>
                                <span class="font-bold text-gray-800 dark:text-gray-200 uppercase">{{ $pr->connection_type }}</span>
                            </div>
                            <div class="flex justify-between items-center text-slate-500 dark:text-slate-400 text-[11px]">
                                <span>Paper Width:</span>
                                <span class="font-mono font-bold text-gray-800 dark:text-gray-200">{{ $pr->paper_size }}</span>
                            </div>
                            @if ($pr->ip_address)
                                <div class="flex justify-between items-center text-slate-500 dark:text-slate-400 text-[11px]">
                                    <span>IP Address:</span>
                                    <span class="font-mono text-orange-600 dark:text-orange-400 font-bold">{{ $pr->ip_address }}:{{ $pr->port }}</span>
                                </div>
                            @endif
                        </div>

                        <!-- Status & Last Used Notice -->
                        <div class="mt-2 text-[10px] text-slate-400 flex items-center justify-between">
                            <span>Status:
                                @if ($pr->connection_type === 'browser')
                                    <span class="text-emerald-600 font-semibold">Ready (Browser Print)</span>
                                @elseif ($pr->connection_type === 'network')
                                    <span class="text-blue-600 font-semibold">Configured (TCP 9100)</span>
                                @else
                                    <span class="text-slate-500 font-semibold">Configured (Direct)</span>
                                @endif
                            </span>
                            <span>Last used: <strong>{{ $pr->last_used_at ? $pr->last_used_at->diffForHumans() : 'Never' }}</strong></span>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="pt-3 border-t border-gray-100 dark:border-gray-700/60 flex items-center justify-between gap-2 text-xs">
                        <div class="flex items-center gap-2">
                            <a href="{{ route('business.printers.test-print', $pr) }}" target="_blank"
                               class="px-2.5 py-1 bg-blue-50 text-blue-600 hover:bg-blue-100 dark:bg-blue-950/40 dark:text-blue-300 rounded-lg font-bold transition flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                <span>Test Print</span>
                            </a>
                            <button type="button" @click='openEdit(@json($pr))'
                                    class="px-2.5 py-1 bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-200 rounded-lg font-bold transition">
                                Edit
                            </button>
                        </div>
                        <form method="POST" action="{{ route('business.printers.destroy', $pr) }}" onsubmit="return confirm('Remove printer {{ $pr->name }}?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="px-2 py-1 text-red-500 hover:text-red-600 dark:hover:text-red-400 font-bold transition">
                                Remove
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <!-- Section 2.5: Quick Print — Recent Orders -->
    <div class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-2xl shadow-xs overflow-hidden">
        <div class="p-5 border-b border-gray-100 dark:border-gray-700/60 flex items-center justify-between">
            <div>
                <h4 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <svg class="w-4 h-4 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Quick Print — Recent Orders</span>
                </h4>
                <p class="text-xs text-slate-400 mt-0.5">Quickly print thermal customer receipts or kitchen dockets for today's active tickets.</p>
            </div>
            <a href="{{ route('business.orders.index') }}" class="text-xs font-bold text-orange-600 hover:text-orange-500 flex items-center gap-1">
                <span>All Orders</span>
                <span>&rarr;</span>
            </a>
        </div>

        @if ($recentOrders->isEmpty())
            <div class="p-8 text-center text-xs text-slate-600 dark:text-slate-300 font-medium">
                No recent orders to print.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs divide-y divide-gray-100 dark:divide-gray-700/60">
                    <thead class="bg-gray-50 dark:bg-gray-900/50 text-slate-700 dark:text-slate-200 uppercase font-black text-[10px] tracking-wider border-b border-gray-100 dark:border-gray-800">
                        <tr>
                            <th class="px-5 py-3">Order #</th>
                            <th class="px-5 py-3">Date & Time</th>
                            <th class="px-5 py-3">Type & Table</th>
                            <th class="px-5 py-3">Customer</th>
                            <th class="px-5 py-3">Total Amount</th>
                            <th class="px-5 py-3 text-right">Available Print Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                        @foreach ($recentOrders as $ord)
                            <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-750 transition">
                                <td class="px-5 py-3.5 font-black text-gray-900 dark:text-white">
                                    #{{ $ord->order_number }}
                                </td>
                                <td class="px-5 py-3.5 text-slate-500 dark:text-slate-400 whitespace-nowrap">
                                    {{ $ord->created_at->format('M d, H:i') }}
                                </td>
                                <td class="px-5 py-3.5">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold {{ $ord->order_type === 'dine_in' ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/40 dark:text-indigo-300' : 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300' }}">
                                        {{ str_replace('_', ' ', $ord->order_type) }}
                                        @if ($ord->table)
                                            ({{ $ord->table->name ?? $ord->table->table_number }})
                                        @endif
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 font-bold text-gray-700 dark:text-gray-300">
                                    {{ $ord->customer_name ?: 'Guest' }}
                                </td>
                                <td class="px-5 py-3.5 font-mono font-bold text-emerald-600 dark:text-emerald-400 whitespace-nowrap">
                                    ${{ number_format($ord->total_amount, 2) }}
                                    <span class="text-[10px] text-slate-400 block font-normal">៛{{ number_format($ord->total_amount * 4100) }}</span>
                                </td>
                                <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1.5">
                                        <a href="{{ route('business.orders.receipt', $ord) }}" target="_blank"
                                           class="px-2.5 py-1 bg-orange-50 text-orange-700 hover:bg-orange-100 dark:bg-orange-950/40 dark:text-orange-300 rounded-lg text-xs font-bold transition flex items-center gap-1"
                                           title="Print Customer Thermal Receipt (80mm)">
                                            <span>🧾 Receipt</span>
                                        </a>
                                        <a href="{{ route('business.orders.kitchen-ticket', $ord) }}" target="_blank"
                                           class="px-2.5 py-1 bg-purple-50 text-purple-700 hover:bg-purple-100 dark:bg-purple-950/40 dark:text-purple-300 rounded-lg text-xs font-bold transition flex items-center gap-1"
                                           title="Print Kitchen Order Docket without pricing">
                                            <span>👨‍🍳 Kitchen Ticket</span>
                                        </a>
                                        <a href="{{ route('business.orders.receipt', ['order' => $ord, 'reprint' => 1]) }}" target="_blank"
                                           class="px-2 py-1 bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-300 rounded-lg text-xs font-bold transition"
                                           title="Reprint receipt">
                                            <span>Reprint</span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <!-- Section 2.7: Architecture & Hardware Note Card -->
    <div class="p-5 rounded-2xl bg-amber-50/70 dark:bg-amber-950/20 border border-amber-200/60 dark:border-amber-800/40 text-xs text-amber-900 dark:text-amber-200 space-y-2">
        <h5 class="font-bold flex items-center gap-1.5">
            <svg class="w-4 h-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span>Printing Architecture & Hardware Guidelines</span>
        </h5>
        <p class="text-amber-800/90 dark:text-amber-300/90">
            <strong>Browser Printing Baseline:</strong> SABAYRA supports direct browser-based thermal printing out of the box with auto-formatted 80mm and 58mm layouts. To print automatically to a USB or LAN thermal printer, set your thermal printer as the default device in your browser's print dialog and enable <em>"Silent / Kiosk Printing"</em> where supported.
        </p>
    </div>

    <!-- Section 2.3: Add / Edit Printer Modal -->
    <div x-show="modalOpen" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/80 backdrop-blur-xs p-4"
         style="display: none;">
        <div @click.away="modalOpen = false"
             class="bg-white dark:bg-gray-800 rounded-3xl max-w-lg w-full p-6 border border-gray-100 dark:border-gray-700 shadow-2xl space-y-4">

            <div class="flex items-center justify-between pb-3 border-b border-gray-100 dark:border-gray-700">
                <h3 class="text-base font-black text-gray-900 dark:text-white" x-text="editMode ? 'Edit Printer' : 'Add New Printer'"></h3>
                <button type="button" @click="modalOpen = false" class="text-slate-400 hover:text-white">&times;</button>
            </div>

            <form method="POST" :action="editUrl" class="space-y-3.5 text-xs">
                @csrf
                <template x-if="editMode">
                    <input type="hidden" name="_method" value="PUT" />
                </template>

                <div>
                    <label class="block font-bold text-slate-800 dark:text-slate-100 mb-1">Printer Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" x-model="form.name" required placeholder="e.g. Front Counter Receipt Printer"
                           class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-2.5 text-xs text-gray-900 dark:text-white focus:ring-2 focus:ring-orange-500 outline-hidden" />
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-800 dark:text-slate-100 mb-1">Printer Type <span class="text-red-500">*</span></label>
                        <select name="printer_type" x-model="form.printer_type" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-2.5 text-xs text-gray-900 dark:text-white">
                            <option value="receipt">Receipt Printer</option>
                            <option value="kitchen">Kitchen Printer</option>
                            <option value="general">General Printer</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-800 dark:text-slate-100 mb-1">Printing Purpose <span class="text-red-500">*</span></label>
                        <select name="printing_purpose" x-model="form.printing_purpose" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-2.5 text-xs text-gray-900 dark:text-white">
                            <option value="both">Both (Receipts & Tickets)</option>
                            <option value="customer_receipts">Customer Receipts Only</option>
                            <option value="kitchen_tickets">Kitchen Tickets Only</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-800 dark:text-slate-100 mb-1">Connection Type <span class="text-red-500">*</span></label>
                        <select name="connection_type" x-model="form.connection_type" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-2.5 text-xs text-gray-900 dark:text-white">
                            <option value="network">Wi-Fi / Network (ESC/POS)</option>
                            <option value="usb">USB Direct</option>
                            <option value="bluetooth">Bluetooth</option>
                            <option value="browser">Browser Print Engine</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-800 dark:text-slate-100 mb-1">Paper Width <span class="text-red-500">*</span></label>
                        <select name="paper_size" x-model="form.paper_size" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-2.5 text-xs text-gray-900 dark:text-white">
                            <option value="80mm">80mm Thermal (Standard)</option>
                            <option value="58mm">58mm Mini Thermal</option>
                            <option value="a4">A4 Full Page</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3" x-show="form.connection_type === 'network'">
                    <div>
                        <label class="block font-bold text-slate-800 dark:text-slate-100 mb-1">IP Address</label>
                        <input type="text" name="ip_address" x-model="form.ip_address" placeholder="192.168.1.200"
                               class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-2.5 text-xs text-gray-900 dark:text-white font-mono" />
                    </div>
                    <div>
                        <label class="block font-bold text-slate-800 dark:text-slate-100 mb-1">Port</label>
                        <input type="number" name="port" x-model="form.port" placeholder="9100"
                               class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-2.5 text-xs text-gray-900 dark:text-white font-mono" />
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-800 dark:text-slate-100 mb-1">Branch Outlet Assignment</label>
                    <select name="branch_id" x-model="form.branch_id" class="w-full rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-2.5 text-xs text-gray-900 dark:text-white">
                        <option value="">All Branch Outlets</option>
                        @foreach ($branches as $br)
                            <option value="{{ $br->id }}">{{ $br->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="pt-2 space-y-2">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_default" value="1" x-model="form.is_default"
                               class="rounded border-gray-300 text-orange-600 focus:ring-orange-500" />
                        <span class="font-bold text-gray-800 dark:text-gray-200">Set as Default Business Printer</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" x-model="form.is_active"
                               class="rounded border-gray-300 text-orange-600 focus:ring-orange-500" />
                        <span class="font-bold text-gray-800 dark:text-gray-200">Printer Active</span>
                    </label>
                </div>

                <div class="mt-5 pt-3 border-t border-gray-100 dark:border-gray-700/60 flex justify-end gap-2">
                    <button type="button" @click="modalOpen = false" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-xl text-xs font-bold">
                        Cancel
                    </button>
                    <button type="submit" class="px-5 py-2 bg-orange-600 hover:bg-orange-500 text-white rounded-xl text-xs font-bold shadow-xs">
                        <span x-text="editMode ? 'Save Changes' : 'Register Printer'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
</x-business-layout>
