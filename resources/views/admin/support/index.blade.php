<x-admin-layout title="Support Management - Super Admin" heading="Support Management & Helpdesk">
<div class="space-y-6">
    <!-- Summary Header Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
        <div class="p-4 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-xs">
            <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Inquiries</span>
            <div class="text-2xl font-black text-slate-900 dark:text-white mt-1">{{ $stats['total'] }}</div>
        </div>
        <div class="p-4 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-xs">
            <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Open / Pending</span>
            <div class="text-2xl font-black text-rose-600 dark:text-rose-400 mt-1">{{ $stats['open'] }}</div>
        </div>
        <div class="p-4 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-xs">
            <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">In Progress</span>
            <div class="text-2xl font-black text-amber-600 dark:text-amber-400 mt-1">{{ $stats['in_progress'] }}</div>
        </div>
        <div class="p-4 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-xs">
            <span class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Resolved</span>
            <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-1">{{ $stats['resolved'] }}</div>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="p-4 bg-white border border-gray-100 rounded-2xl shadow-xs dark:bg-gray-800 dark:border-gray-700">
        <form method="GET" action="{{ route('admin.support.index') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-4">
            <div class="sm:col-span-2">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Search by subject, message, merchant name or email..."
                       class="w-full text-xs rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2.5 focus:border-orange-500 focus:ring-orange-500 font-medium">
            </div>

            <div>
                <select name="status" class="w-full text-xs rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2.5 focus:border-orange-500 focus:ring-orange-500 font-medium">
                    <option value="">All Statuses</option>
                    <option value="open" {{ request('status') === 'open' ? 'selected' : '' }}>Open</option>
                    <option value="in_progress" {{ request('status') === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                    <option value="resolved" {{ request('status') === 'resolved' ? 'selected' : '' }}>Resolved</option>
                    <option value="closed" {{ request('status') === 'closed' ? 'selected' : '' }}>Closed</option>
                </select>
            </div>

            <div class="flex space-x-2">
                <button type="submit" class="px-4 py-2.5 bg-orange-600 hover:bg-orange-700 text-white font-bold text-xs rounded-xl shadow-xs transition grow">
                    Filter
                </button>
                @if(request()->hasAny(['search', 'status', 'priority']))
                    <a href="{{ route('admin.support.index') }}" class="px-3 py-2.5 bg-slate-100 dark:bg-gray-700 text-slate-700 dark:text-slate-200 text-xs font-bold rounded-xl transition">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Tickets Table -->
    <div class="overflow-hidden bg-white border border-gray-100 rounded-2xl shadow-xs dark:bg-gray-800 dark:border-gray-700">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-750">
                    <tr class="text-left text-xs font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider">
                        <th class="px-6 py-3.5">Subject & Merchant</th>
                        <th class="px-6 py-3.5">Inquiry Message</th>
                        <th class="px-6 py-3.5">Priority</th>
                        <th class="px-6 py-3.5">Channel</th>
                        <th class="px-6 py-3.5">Status</th>
                        <th class="px-6 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60 text-xs">
                    @forelse ($tickets as $ticket)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-750/50 transition">
                            <td class="px-6 py-4 align-top">
                                <h4 class="font-bold text-slate-900 dark:text-white text-sm">
                                    {{ $ticket->subject }}
                                </h4>
                                <p class="text-slate-600 dark:text-slate-300 font-medium mt-1">
                                    👤 {{ $ticket->sender_name ?? 'Merchant' }}
                                    @if($ticket->business)
                                        • <span class="font-bold text-orange-600">{{ $ticket->business->name }}</span>
                                    @endif
                                </p>
                                <span class="text-[11px] text-slate-500 dark:text-slate-400 block mt-0.5">
                                    📧 {{ $ticket->sender_email ?? '-' }} | 📞 {{ $ticket->sender_phone ?? '-' }}
                                </span>
                            </td>

                            <td class="px-6 py-4 align-top max-w-md">
                                <p class="text-slate-700 dark:text-slate-200 leading-relaxed font-medium">
                                    {{ $ticket->message }}
                                </p>
                                @if($ticket->admin_notes)
                                    <div class="mt-2 p-2 rounded-lg bg-slate-100 dark:bg-gray-700/60 border border-slate-200 dark:border-gray-600 text-[11px]">
                                        <span class="font-bold text-purple-600 dark:text-purple-300">Admin Resolution Note:</span>
                                        <span class="text-slate-700 dark:text-slate-200">{{ $ticket->admin_notes }}</span>
                                    </div>
                                @endif
                            </td>

                            <td class="px-6 py-4 align-top">
                                <x-badge :color="match($ticket->priority) {
                                    'urgent', 'high' => 'red',
                                    'medium' => 'amber',
                                    default => 'blue'
                                }">
                                    {{ strtoupper($ticket->priority) }}
                                </x-badge>
                            </td>

                            <td class="px-6 py-4 align-top">
                                <span class="px-2 py-0.5 rounded text-[11px] font-bold uppercase bg-slate-100 text-slate-700 dark:bg-gray-700 dark:text-slate-200">
                                    {{ $ticket->channel }}
                                </span>
                            </td>

                            <td class="px-6 py-4 align-top">
                                <x-badge :color="match($ticket->status) {
                                    'resolved' => 'green',
                                    'in_progress' => 'amber',
                                    'closed' => 'gray',
                                    default => 'red'
                                }">
                                    {{ ucfirst(str_replace('_', ' ', $ticket->status)) }}
                                </x-badge>
                            </td>

                            <td class="px-6 py-4 align-top text-right space-y-2">
                                <form method="POST" action="{{ route('admin.support.update-status', $ticket) }}" class="space-y-1">
                                    @csrf
                                    <select name="status" onchange="this.form.submit()" class="text-xs py-1 px-2 rounded-lg border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-slate-800 dark:text-slate-100 font-bold">
                                        <option value="open" {{ $ticket->status === 'open' ? 'selected' : '' }}>Open</option>
                                        <option value="in_progress" {{ $ticket->status === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                                        <option value="resolved" {{ $ticket->status === 'resolved' ? 'selected' : '' }}>Mark Resolved</option>
                                        <option value="closed" {{ $ticket->status === 'closed' ? 'selected' : '' }}>Close Ticket</option>
                                    </select>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-slate-400">No support tickets found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($tickets->hasPages())
            <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">
                {{ $tickets->links() }}
            </div>
        @endif
    </div>
</div>
</x-admin-layout>
