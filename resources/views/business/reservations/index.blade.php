<x-business-layout title="{{ ($isServiceMode ?? false) ? 'Service Appointments' : 'Reservations' }}" heading="{{ ($isServiceMode ?? false) ? 'Service Appointments & Schedule' : 'Reservations Management' }}">
<div class="space-y-6" x-data="{ addModal: false }">

    @if (session('status'))
        <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-xs font-bold text-emerald-800 dark:text-emerald-200">
            {{ session('status') }}
        </div>
    @endif

    <!-- Top Stats & Actions -->
    <div class="p-4 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-2xl shadow-xs flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center space-x-4 text-xs font-bold">
            <span class="text-slate-500">Today: <strong class="text-gray-900 dark:text-white">{{ $stats['today_total'] }}</strong></span>
            <span class="text-amber-500">Pending: <strong>{{ $stats['pending'] }}</strong></span>
            <span class="text-emerald-500">Confirmed: <strong>{{ $stats['confirmed'] }}</strong></span>
        </div>

        <button @click="addModal = true" class="px-4 py-2 bg-orange-600 hover:bg-orange-500 text-white rounded-xl text-xs font-bold shadow-xs transition">
            + New {{ ($isServiceMode ?? false) ? 'Appointment' : 'Reservation' }}
        </button>
    </div>

    <!-- Date Filter -->
    <div class="p-4 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-2xl shadow-xs flex items-center gap-3 text-xs">
        <span class="font-bold text-slate-500">Filter Date:</span>
        <form method="GET" class="flex items-center gap-2">
            <input type="date" name="date" value="{{ $date }}" class="rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-3 py-1.5 text-xs text-gray-900 dark:text-white" />
            <button type="submit" class="px-3 py-1.5 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 text-gray-800 dark:text-gray-200 rounded-xl font-bold">
                Apply
            </button>
        </form>
    </div>

    <!-- Reservations Table -->
    <div class="bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-2xl shadow-xs overflow-hidden">
        <table class="w-full text-left text-xs">
            <thead class="bg-slate-50 dark:bg-gray-900/60 border-b border-gray-200 dark:border-gray-700/60 text-slate-500 uppercase tracking-wider text-[10px]">
                <tr>
                    <th class="p-4">Time</th>
                    <th class="p-4">{{ ($isServiceMode ?? false) ? 'Client Name' : 'Guest Name' }}</th>
                    <th class="p-4">Phone</th>
                    <th class="p-4">{{ ($isServiceMode ?? false) ? 'Service' : 'Party Size' }}</th>
                    <th class="p-4">{{ ($isServiceMode ?? false) ? 'Specialist' : 'Assigned Table' }}</th>
                    <th class="p-4">Status</th>
                    <th class="p-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                @forelse ($reservations as $res)
                    <tr>
                        <td class="p-4 font-mono font-bold text-gray-900 dark:text-white">
                            {{ $res->reservation_time }}{{ $res->end_time ? ' - ' . $res->end_time : '' }}
                            @if($res->booking_code)
                                <div class="text-[10px] text-orange-600">{{ $res->booking_code }}</div>
                            @endif
                        </td>
                        <td class="p-4 font-bold text-gray-900 dark:text-white">{{ $res->customer_name }}</td>
                        <td class="p-4 font-mono text-slate-500">{{ $res->customer_phone }}</td>
                        <td class="p-4 font-bold">
                            @if($res->booking_type === 'service')
                                {{ $res->serviceProduct?->name_en ?? 'Service Visit' }}
                                @if($res->price_usd)
                                    <span class="text-emerald-600 font-mono ml-1">${{ number_format((float) $res->price_usd, 2) }}</span>
                                @endif
                            @else
                                {{ $res->party_size }} guests
                            @endif
                        </td>
                        <td class="p-4">
                            @if($res->booking_type === 'service')
                                {{ $res->staffMember?->user?->name ?? 'Any Staff' }}
                            @else
                                {{ $res->table ? 'Table ' . $res->table->table_number : 'Unassigned' }}
                            @endif
                        </td>
                        <td class="p-4">
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase
                                {{ $res->status === 'confirmed' ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/40 dark:text-emerald-400' :
                                   ($res->status === 'completed' ? 'bg-sky-50 text-sky-600 dark:bg-sky-950/40 dark:text-sky-400' :
                                   ($res->status === 'seated' ? 'bg-blue-50 text-blue-600 dark:bg-blue-950/40 dark:text-blue-400' :
                                   ($res->status === 'cancelled' ? 'bg-red-50 text-red-600 dark:bg-red-950/40 dark:text-red-400' : 'bg-amber-50 text-amber-600 dark:bg-amber-950/40 dark:text-amber-400'))) }}">
                                {{ $res->status }}
                            </span>
                        </td>
                        <td class="p-4 text-right space-x-1">
                            @if ($res->booking_type === 'service' && $res->status !== 'completed')
                                <form method="POST" action="{{ route('business.reservations.update-status', $res) }}" class="inline">
                                    @csrf
                                    <input type="hidden" name="status" value="completed" />
                                    <button type="submit" class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-[10px] font-bold">
                                        Complete
                                    </button>
                                </form>
                            @elseif ($res->booking_type !== 'service' && $res->status !== 'seated')
                                <form method="POST" action="{{ route('business.reservations.update-status', $res) }}" class="inline">
                                    @csrf
                                    <input type="hidden" name="status" value="seated" />
                                    <button type="submit" class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-[10px] font-bold">
                                        Seat Guest
                                    </button>
                                </form>
                            @endif
                            @if ($res->status !== 'cancelled')
                                <form method="POST" action="{{ route('business.reservations.update-status', $res) }}" class="inline">
                                    @csrf
                                    <input type="hidden" name="status" value="cancelled" />
                                    <button type="submit" class="px-2.5 py-1 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 text-gray-800 dark:text-gray-200 rounded-lg text-[10px] font-bold">
                                        Cancel
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="p-10 text-center text-slate-400">
                            No reservations scheduled for {{ $date }}.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Add Reservation Modal -->
    <div x-show="addModal" class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/80 p-4" style="display: none;">
        <div @click.away="addModal = false" class="bg-white dark:bg-gray-800 rounded-3xl max-w-md w-full p-6 border border-gray-700 shadow-2xl">
            <h3 class="text-base font-black text-gray-900 dark:text-white">Book New Reservation</h3>

            <form method="POST" action="{{ route('business.reservations.store') }}" class="mt-4 space-y-3 text-xs">
                @csrf
                <div>
                    <label class="block font-bold text-slate-500 mb-1">Guest Name</label>
                    <input type="text" name="customer_name" required class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 p-2.5 text-xs text-gray-900 dark:text-white" />
                </div>
                <div>
                    <label class="block font-bold text-slate-500 mb-1">Phone Number</label>
                    <input type="text" name="customer_phone" required placeholder="012 345 678" class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 p-2.5 text-xs text-gray-900 dark:text-white" />
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-500 mb-1">Date</label>
                        <input type="date" name="reservation_date" value="{{ $date }}" required class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 p-2.5 text-xs text-gray-900 dark:text-white" />
                    </div>
                    <div>
                        <label class="block font-bold text-slate-500 mb-1">Time</label>
                        <input type="time" name="reservation_time" value="18:30" required class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 p-2.5 text-xs text-gray-900 dark:text-white" />
                    </div>
                </div>
                @if ($isServiceMode ?? false)
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-slate-500 mb-1">Service *</label>
                            <select name="service_product_id" required class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 p-2.5 text-xs text-gray-900 dark:text-white">
                                @foreach ($services ?? collect() as $svc)
                                    <option value="{{ $svc->id }}">{{ $svc->name_en }} (${{ number_format((float) $svc->effective_price, 2) }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block font-bold text-slate-500 mb-1">Specialist</label>
                            <select name="staff_member_id" class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 p-2.5 text-xs text-gray-900 dark:text-white">
                                <option value="">Any Available Staff</option>
                                @foreach ($staffMembers ?? collect() as $staff)
                                    <option value="{{ $staff->id }}">{{ $staff->user?->name ?? 'Staff' }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                @else
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-bold text-slate-500 mb-1">Party Size</label>
                            <input type="number" name="party_size" value="2" min="1" max="100" required class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 p-2.5 text-xs text-gray-900 dark:text-white font-bold" />
                        </div>
                        <div>
                            <label class="block font-bold text-slate-500 mb-1">Table</label>
                            <select name="table_id" class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 p-2.5 text-xs text-gray-900 dark:text-white">
                                <option value="">Any Available Table</option>
                                @foreach ($tables as $t)
                                    <option value="{{ $t->id }}">Table {{ $t->table_number }} ({{ $t->capacity }} seats)</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                @endif

                <div class="mt-5 pt-3 border-t border-gray-100 dark:border-gray-700/60 flex justify-end gap-2">
                    <button type="button" @click="addModal = false" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-xl text-xs font-bold">Cancel</button>
                    <button type="submit" class="px-5 py-2 bg-orange-600 hover:bg-orange-500 text-white rounded-xl text-xs font-bold shadow-xs">Confirm Booking</button>
                </div>
            </form>
        </div>
    </div>

</div>
</x-business-layout>
