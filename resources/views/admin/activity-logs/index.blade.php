<x-admin-layout title="Activity Logs - Super Admin" heading="Audit & System Activity Logs">

<div class="space-y-6">
    <div class="overflow-hidden bg-white border border-gray-100 rounded-2xl shadow-xs dark:bg-gray-800 dark:border-gray-700/60">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-750">
                    <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        <th class="px-6 py-3.5">Timestamp</th>
                        <th class="px-6 py-3.5">Log / Area</th>
                        <th class="px-6 py-3.5">Action / Description</th>
                        <th class="px-6 py-3.5">Causer</th>
                        <th class="px-6 py-3.5">Target / Subject</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60 text-xs">
                    @forelse ($logs as $log)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-750/50 transition">
                            <td class="px-6 py-4 text-gray-400 whitespace-nowrap">
                                {{ $log->created_at->format('Y-m-d H:i:s') }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-0.5 rounded text-[11px] font-mono bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                    {{ $log->log_name ?? 'default' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 font-medium text-gray-900 dark:text-white">
                                {{ $log->description }}
                            </td>
                            <td class="px-6 py-4 text-gray-600 dark:text-gray-300">
                                {{ $log->causer->name ?? 'System / Anonymous' }}
                            </td>
                            <td class="px-6 py-4 text-gray-500 font-mono text-[11px]">
                                {{ class_basename($log->subject_type ?? 'None') }} #{{ substr($log->subject_id ?? '', 0, 8) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-gray-400">No activity logs recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($logs->hasPages())
            <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-700">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>
</x-admin-layout>
