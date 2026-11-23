<x-business-layout title="Printer Management" heading="Printer Management">
<div class="space-y-6" x-data="{ addPrinter: false }">

    @if (session('status'))
        <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-xs font-bold text-emerald-800 dark:text-emerald-200">
            {{ session('status') }}
        </div>
    @endif

    <div class="p-4 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-2xl shadow-xs flex items-center justify-between">
        <div>
            <h3 class="text-sm font-bold text-gray-900 dark:text-white">Receipt & Kitchen ESC/POS Printers</h3>
            <p class="text-xs text-slate-400">Configure 58mm, 80mm thermal printers or A4 receipt stations.</p>
        </div>
        <button @click="addPrinter = true" class="px-4 py-2 bg-orange-600 hover:bg-orange-500 text-white rounded-xl text-xs font-bold shadow-xs transition">
            + Register Printer
        </button>
    </div>

    <!-- Printers Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse ($printers as $pr)
            <div class="p-5 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-xs space-y-3">
                <div class="flex items-center justify-between">
                    <h4 class="text-sm font-bold text-gray-900 dark:text-white">{{ $pr->name }}</h4>
                    <span class="text-[10px] font-mono font-bold px-2 py-0.5 rounded-full bg-slate-100 dark:bg-gray-700 text-slate-600 dark:text-slate-300 uppercase">
                        {{ $pr->paper_size }} &bull; {{ $pr->connection_type }}
                    </span>
                </div>

                <div class="text-xs text-slate-500 space-y-1">
                    <p>IP Address: <span class="font-mono text-gray-900 dark:text-white">{{ $pr->ip_address ?: 'USB / Local Device' }}</span></p>
                    <p>Branch: <span class="font-bold text-gray-700 dark:text-gray-300">{{ $pr->branch ? $pr->branch->name : 'All Outlets' }}</span></p>
                </div>

                <div class="pt-3 border-t border-gray-100 dark:border-gray-700/60 flex items-center justify-between">
                    <button onclick="window.print()" class="text-xs font-bold text-blue-500 hover:text-blue-600">
                        Print Test Slip
                    </button>
                    <form method="POST" action="{{ route('business.printers.destroy', $pr) }}" onsubmit="return confirm('Remove printer?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-xs text-red-500 hover:text-red-600">Delete</button>
                    </form>
                </div>
            </div>
        @empty
            <div class="col-span-full p-12 text-center text-xs text-slate-400 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700/60">
                No external thermal printers registered yet. Standard browser printing is active.
            </div>
        @endforelse
    </div>

    <!-- Add Printer Modal -->
    <div x-show="addPrinter" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/80 p-4" style="display: none;">
        <div @click.away="addPrinter = false" class="bg-white dark:bg-gray-800 rounded-3xl max-w-sm w-full p-6 border border-gray-700 shadow-2xl">
            <h3 class="text-base font-black text-gray-900 dark:text-white">Register ESC/POS Printer</h3>

            <form method="POST" action="{{ route('business.printers.store') }}" class="mt-4 space-y-3 text-xs">
                @csrf
                <div>
                    <label class="block font-bold text-slate-500 mb-1">Printer Name</label>
                    <input type="text" name="name" required placeholder="e.g. Kitchen Line Printer" class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 p-2.5 text-xs text-gray-900 dark:text-white" />
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-500 mb-1">Paper Size</label>
                        <select name="paper_size" class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 p-2.5 text-xs text-gray-900 dark:text-white">
                            <option value="80mm">80mm Thermal</option>
                            <option value="58mm">58mm Mini</option>
                            <option value="a4">A4 Full Page</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-bold text-slate-500 mb-1">Connection</label>
                        <select name="connection_type" class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 p-2.5 text-xs text-gray-900 dark:text-white">
                            <option value="network">LAN / Wi-Fi</option>
                            <option value="usb">USB Direct</option>
                            <option value="bluetooth">Bluetooth</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block font-bold text-slate-500 mb-1">IP Address (for Network Printer)</label>
                    <input type="text" name="ip_address" placeholder="192.168.1.200" class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 p-2.5 text-xs text-gray-900 dark:text-white font-mono" />
                </div>

                <div class="mt-5 pt-3 border-t border-gray-100 dark:border-gray-700/60 flex justify-end gap-2">
                    <button type="button" @click="addPrinter = false" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-xl text-xs font-bold">Cancel</button>
                    <button type="submit" class="px-5 py-2 bg-orange-600 hover:bg-orange-500 text-white rounded-xl text-xs font-bold shadow-xs">Register</button>
                </div>
            </form>
        </div>
    </div>

</div>
</x-business-layout>
