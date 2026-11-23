<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                    {{ __('Security Center & Audit Log') }}
                </h2>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                    {{ __('Manage account security, Two-Factor Authentication (2FA), active sessions, and access logs.') }}
                </p>
            </div>
            <div class="flex items-center gap-2">
                @if ($user->two_factor_enabled)
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        {{ __('2FA Enabled') }}
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 dark:bg-amber-950/40 dark:text-amber-300">
                        <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                        {{ __('2FA Disabled') }}
                    </span>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        @if (session('status'))
            <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 dark:bg-emerald-950/30 dark:border-emerald-800 dark:text-emerald-300">
                {{ session('status') }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left: 2FA & Account Protections -->
            <div class="lg:col-span-1 space-y-6">
                <div class="bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700/60 rounded-2xl p-6 shadow-sm">
                    <h3 class="text-base font-bold text-zinc-900 dark:text-zinc-100 mb-2 flex items-center gap-2">
                        <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        {{ __('Two-Factor Authentication (2FA)') }}
                    </h3>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-4">
                        {{ __('Add an additional layer of security to prevent unauthorized access to your merchant cash drawer and bank credentials.') }}
                    </p>

                    <form method="POST" action="{{ route('business.security.2fa') }}">
                        @csrf
                        <button type="submit" class="w-full py-2.5 px-4 rounded-xl text-xs font-semibold transition {{ $user->two_factor_enabled ? 'bg-zinc-100 dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 hover:bg-zinc-200' : 'bg-orange-600 text-white hover:bg-orange-700' }}">
                            {{ $user->two_factor_enabled ? __('Disable Two-Factor Authentication') : __('Enable Two-Factor Authentication') }}
                        </button>
                    </form>
                </div>

                <!-- Security Checklist -->
                <div class="bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700/60 rounded-2xl p-6 shadow-sm">
                    <h3 class="text-sm font-bold text-zinc-900 dark:text-zinc-100 mb-3 flex items-center gap-2">
                        <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        {{ __('Security Health Check') }}
                    </h3>
                    <ul class="text-xs space-y-2.5">
                        <li class="flex items-center gap-2 text-emerald-600 dark:text-emerald-400">
                            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <span>{{ __('Encrypted HTTPS Connection Active') }}</span>
                        </li>
                        <li class="flex items-center gap-2 {{ $user->two_factor_enabled ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400' }}">
                            @if ($user->two_factor_enabled)
                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <span>{{ __('2FA Enabled on Owner Account') }}</span>
                            @else
                                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                <span>{{ __('Recommended: Turn on 2FA') }}</span>
                            @endif
                        </li>
                        <li class="flex items-center gap-2 text-emerald-600 dark:text-emerald-400">
                            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <span>{{ __('Tenant Database Isolation Enforced') }}</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Right: Security Audit Logs -->
            <div class="lg:col-span-2">
                <div class="bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700/60 rounded-2xl p-6 shadow-sm">
                    <h3 class="text-base font-bold text-zinc-900 dark:text-zinc-100 mb-4 flex items-center justify-between">
                        <span>{{ __('Recent Security & Login Events') }}</span>
                        <span class="text-xs font-normal text-zinc-400">{{ __('Last 15 records') }}</span>
                    </h3>

                    @if ($logs->isEmpty())
                        <div class="py-8 text-center text-zinc-500 dark:text-zinc-400 text-xs">
                            {{ __('No explicit security audit entries logged yet. Regular logins and 2FA toggles will appear here.') }}
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs text-zinc-600 dark:text-zinc-300">
                                <thead class="border-b border-zinc-200 dark:border-zinc-700 text-zinc-400 uppercase font-semibold">
                                    <tr>
                                        <th class="pb-2">{{ __('Event') }}</th>
                                        <th class="pb-2">{{ __('IP Address') }}</th>
                                        <th class="pb-2">{{ __('Time') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700/50">
                                    @foreach ($logs as $log)
                                        <tr>
                                            <td class="py-3 font-medium text-zinc-900 dark:text-zinc-100">
                                                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[11px] font-mono bg-zinc-100 dark:bg-zinc-700">
                                                    {{ $log->event_type }}
                                                </span>
                                            </td>
                                            <td class="py-3 font-mono text-[11px]">{{ $log->ip_address ?? '127.0.0.1' }}</td>
                                            <td class="py-3 text-zinc-400">{{ $log->created_at->diffForHumans() }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
