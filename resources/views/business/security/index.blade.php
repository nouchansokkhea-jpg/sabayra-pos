<x-business-layout title="Security Center" heading="Security Center &amp; Audit Log">
    <div class="max-w-6xl mx-auto space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    Manage Two-Factor Authentication (2FA), active devices, and recent sign-in activity for <span class="font-bold text-slate-700 dark:text-slate-200">{{ $user->email }}</span>.
                </p>
            </div>
            <a href="{{ route('business.settings.index') }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 text-slate-700 dark:text-slate-200 hover:border-orange-500 transition">
                <span>&larr; Open Full Settings Hub</span>
            </a>
        </div>

        @include('business.settings.partials.security')
    </div>
</x-business-layout>
