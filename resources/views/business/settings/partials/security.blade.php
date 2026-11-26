<div class="space-y-6"
     x-data="{
         copiedSecret: false,
         copiedCodes: false,
         showRegenerateForm: {{ $errors->twoFactorRecovery->any() ? 'true' : 'false' }},
         showDisableForm: {{ $errors->twoFactorDisable->any() ? 'true' : 'false' }},
         showRevokeSessionsForm: {{ $errors->revokeSessions->any() ? 'true' : 'false' }},
         copyText(value, flag) {
             if (navigator.clipboard) {
                 navigator.clipboard.writeText(value);
                 this[flag] = true;
                 setTimeout(() => this[flag] = false, 2000);
             }
         },
         downloadCodes(codes) {
             const content = 'SABAYRA Backup Recovery Codes\nAccount: {{ $user->email }}\nGenerated: {{ now()->toDateTimeString() }}\n\n' + codes.join('\n') + '\n';
             const blob = new Blob([content], { type: 'text/plain' });
             const url = URL.createObjectURL(blob);
             const a = document.createElement('a');
             a.href = url;
             a.download = 'sabayra-recovery-codes.txt';
             a.click();
             URL.revokeObjectURL(url);
         }
     }">
    <!-- Top Row: Two-Factor Authentication + Security Health -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Two-Factor Authentication (2FA) Card -->
        <div class="lg:col-span-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700/70 rounded-2xl p-6 shadow-xs flex flex-col justify-between gap-5">
            <div class="space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h3 class="text-base font-black text-slate-900 dark:text-white flex items-center gap-2">
                        <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        <span>Two-Factor Authentication (2FA)</span>
                    </h3>
                    @if ($twoFactorEnabled)
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-500/15 text-emerald-600 dark:text-emerald-300 border border-emerald-500/30">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            Enabled
                        </span>
                    @elseif ($twoFactorPendingSetup)
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-sky-500/15 text-sky-600 dark:text-sky-300 border border-sky-500/30">
                            <span class="w-2 h-2 rounded-full bg-sky-500 animate-pulse"></span>
                            Setup Pending
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-500/15 text-amber-600 dark:text-amber-300 border border-amber-500/30">
                            <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                            Disabled
                        </span>
                    @endif
                </div>

                <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed">
                    Add another layer of protection to your SABAYRA account by requiring a 6-digit verification code from an authenticator app when signing in.
                </p>

                @if ($twoFactorPendingSetup && !empty($twoFactorSetupPayload))
                    <!-- Pending Authenticator QR Setup Flow -->
                    <div class="p-5 rounded-2xl bg-slate-50 dark:bg-gray-900/60 border border-slate-200 dark:border-gray-700 space-y-5">
                        <div class="grid grid-cols-1 sm:grid-cols-12 gap-5 items-center">
                            <div class="sm:col-span-5 flex flex-col items-center justify-center p-4 rounded-xl bg-white border border-slate-200 shadow-xs">
                                <div class="w-44 h-44 flex items-center justify-center">
                                    {!! $twoFactorSetupPayload['qr_svg'] !!}
                                </div>
                            </div>

                            <div class="sm:col-span-7 space-y-3">
                                <p class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-wider">
                                    Step 1: Scan QR Code
                                </p>
                                <p class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed">
                                    Scan with Google Authenticator, Authy, 1Password, or Apple Passwords, or enter this manual setup key:
                                </p>

                                <div class="p-3 rounded-xl bg-slate-900 text-white font-mono text-xs flex items-center justify-between gap-2">
                                    <span class="truncate select-all tracking-wider">{{ $twoFactorSetupPayload['secret'] }}</span>
                                    <button type="button"
                                            @click="copyText('{{ $twoFactorSetupPayload['secret'] }}', 'copiedSecret')"
                                            class="px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-[10px] font-bold text-orange-400 shrink-0">
                                        <span x-show="!copiedSecret">Copy</span>
                                        <span x-show="copiedSecret" style="display: none;">Copied</span>
                                    </button>
                                </div>

                                <form method="POST" action="{{ route('business.security.2fa.confirm') }}" class="space-y-3 pt-2">
                                    @csrf
                                    <div>
                                        <label for="two_factor_confirm_code" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                                            Step 2: Enter 6-Digit Verification Code *
                                        </label>
                                        <input id="two_factor_confirm_code"
                                               type="text"
                                               name="code"
                                               inputmode="numeric"
                                               maxlength="6"
                                               required
                                               placeholder="000000"
                                               class="w-full sm:w-56 text-sm rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-4 py-2.5 font-mono tracking-[0.3em] text-center focus:border-orange-500 focus:ring-orange-500">
                                        @error('code', 'twoFactorConfirm')
                                            <p class="text-[11px] font-semibold text-rose-500 mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div class="flex flex-wrap items-center gap-2.5">
                                        <button type="submit"
                                                class="px-5 py-2.5 rounded-xl bg-orange-600 hover:bg-orange-700 text-white text-xs font-bold shadow-xs transition">
                                            Confirm &amp; Activate 2FA
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="pt-3 border-t border-slate-200 dark:border-gray-700 flex justify-end">
                            <form method="POST" action="{{ route('business.security.2fa.cancel') }}">
                                @csrf
                                <button type="submit" class="text-xs font-semibold text-slate-500 hover:text-rose-500 transition">
                                    Cancel Setup
                                </button>
                            </form>
                        </div>
                    </div>
                @elseif ($twoFactorEnabled)
                    <!-- Enabled 2FA Status & Recovery Codes -->
                    <div class="p-4 rounded-xl bg-emerald-500/10 border border-emerald-500/30 text-xs flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <p class="font-bold text-emerald-700 dark:text-emerald-300">
                                Authenticator protection is active for {{ $user->email }}.
                            </p>
                            <p class="text-[11px] text-emerald-600 dark:text-emerald-400 mt-0.5">
                                Confirmed {{ $user->two_factor_confirmed_at?->diffForHumans() ?? 'recently' }} &bull; {{ $remainingRecoveryCodesCount ?? 0 }} unused backup recovery code(s) remaining.
                            </p>
                        </div>
                    </div>

                    @if (!empty($twoFactorRecoveryCodesPlain) && is_array($twoFactorRecoveryCodesPlain))
                        <div class="p-5 rounded-2xl bg-amber-50 dark:bg-amber-950/25 border border-amber-300 dark:border-amber-700/60 space-y-3">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div>
                                    <p class="text-xs font-black text-amber-900 dark:text-amber-300">Save Your Single-Use Backup Recovery Codes</p>
                                    <p class="text-[11px] text-amber-700 dark:text-amber-400">
                                        Store these codes securely now. Each code works once if you lose your authenticator device, and they will not be shown again.
                                    </p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button type="button"
                                            @click="copyText(@js(implode("\n", $twoFactorRecoveryCodesPlain)), 'copiedCodes')"
                                            class="px-3 py-1.5 rounded-lg bg-slate-900 text-white text-[11px] font-bold hover:bg-slate-800 transition">
                                        <span x-show="!copiedCodes">Copy All</span>
                                        <span x-show="copiedCodes" style="display: none;">Copied!</span>
                                    </button>
                                    <button type="button"
                                            @click="downloadCodes(@js($twoFactorRecoveryCodesPlain))"
                                            class="px-3 py-1.5 rounded-lg bg-amber-600 text-white text-[11px] font-bold hover:bg-amber-700 transition">
                                        Download .txt
                                    </button>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                @foreach ($twoFactorRecoveryCodesPlain as $recoveryCode)
                                    <div class="px-3 py-2 rounded-lg bg-white dark:bg-gray-900 border border-amber-200 dark:border-gray-700 font-mono text-xs font-bold text-center text-slate-900 dark:text-white select-all">
                                        {{ $recoveryCode }}
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Regenerate / Disable Forms -->
                    <div class="flex flex-wrap items-center gap-2.5 pt-2">
                        <button type="button"
                                @click="showRegenerateForm = !showRegenerateForm; showDisableForm = false"
                                class="px-4 py-2 rounded-xl text-xs font-bold bg-slate-100 hover:bg-slate-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-slate-800 dark:text-slate-100 transition">
                            Regenerate Recovery Codes
                        </button>

                        <button type="button"
                                @click="showDisableForm = !showDisableForm; showRegenerateForm = false"
                                class="px-4 py-2 rounded-xl text-xs font-bold text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-950/30 hover:bg-rose-100 dark:hover:bg-rose-950/50 border border-rose-200 dark:border-rose-800/50 transition">
                            Disable 2FA
                        </button>
                    </div>

                    <div x-show="showRegenerateForm" style="display: none;"
                         class="p-4 rounded-xl bg-slate-50 dark:bg-gray-900/50 border border-slate-200 dark:border-gray-700">
                        <form method="POST" action="{{ route('business.security.2fa.recovery-codes') }}" class="flex flex-col sm:flex-row sm:items-end gap-3">
                            @csrf
                            <div class="flex-1">
                                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Confirm Password to Regenerate Codes *</label>
                                <input type="password" name="current_password" required autocomplete="current-password"
                                       class="w-full text-xs rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3.5 py-2 focus:border-orange-500 focus:ring-orange-500">
                                @error('current_password', 'twoFactorRecovery')
                                    <p class="text-[11px] font-semibold text-rose-500 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <button type="submit" class="px-4 py-2 rounded-xl bg-orange-600 hover:bg-orange-700 text-white text-xs font-bold shrink-0">
                                Generate New Codes
                            </button>
                        </form>
                    </div>

                    <div x-show="showDisableForm" style="display: none;"
                         class="p-4 rounded-xl bg-rose-50/70 dark:bg-rose-950/20 border border-rose-200 dark:border-rose-800/60">
                        <form method="POST" action="{{ route('business.security.2fa.disable') }}" class="flex flex-col sm:flex-row sm:items-end gap-3">
                            @csrf
                            @method('DELETE')
                            <div class="flex-1">
                                <label class="block text-xs font-bold text-rose-700 dark:text-rose-300 mb-1">Confirm Current Password to Disable 2FA *</label>
                                <input type="password" name="current_password" required autocomplete="current-password"
                                       class="w-full text-xs rounded-xl border-rose-200 dark:border-rose-800 dark:bg-gray-900 dark:text-white px-3.5 py-2 focus:border-rose-500 focus:ring-rose-500">
                                @error('current_password', 'twoFactorDisable')
                                    <p class="text-[11px] font-semibold text-rose-500 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <button type="submit" class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold shrink-0">
                                Confirm Disable 2FA
                            </button>
                        </form>
                    </div>
                @else
                    <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-gray-900/50 border border-slate-200/80 dark:border-gray-700/60 text-xs">
                        <span class="font-semibold text-slate-700 dark:text-slate-300">
                            2FA is currently disabled. Set up an authenticator app and backup recovery codes to protect your sign-in.
                        </span>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-3 pt-3 border-t border-gray-100 dark:border-gray-700">
                        <span class="text-[11px] text-slate-500 dark:text-slate-400">
                            Account-level protection for {{ $user->name }}
                        </span>
                        <form method="POST" action="{{ route('business.security.2fa.setup') }}">
                            @csrf
                            <button type="submit"
                                    class="px-5 py-2.5 rounded-xl text-xs font-bold bg-orange-600 hover:bg-orange-700 text-white shadow-xs transition">
                                Set Up Two-Factor Authentication
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        </div>

        <!-- Account Protection Summary -->
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700/70 rounded-2xl p-6 shadow-xs space-y-4">
            <h3 class="text-sm font-black text-slate-900 dark:text-white flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
                <span>Security Health Check</span>
            </h3>

            <ul class="text-xs space-y-2.5 text-slate-600 dark:text-slate-300">
                <li class="flex items-center justify-between">
                    <span>Email Verified</span>
                    <span class="font-bold {{ $user->email_verified_at ? 'text-emerald-500' : 'text-amber-500' }}">
                        {{ $user->email_verified_at ? 'Verified' : 'Pending' }}
                    </span>
                </li>
                <li class="flex items-center justify-between">
                    <span>2FA Protection</span>
                    <span class="font-bold {{ $twoFactorEnabled ? 'text-emerald-500' : 'text-amber-500' }}">
                        {{ $twoFactorEnabled ? 'Enabled' : ($twoFactorPendingSetup ? 'Pending' : 'Disabled') }}
                    </span>
                </li>
                <li class="flex items-center justify-between">
                    <span>Backup Recovery Codes</span>
                    <span class="font-bold text-slate-900 dark:text-white">
                        {{ $twoFactorEnabled ? ($remainingRecoveryCodesCount ?? 0) . ' left' : '—' }}
                    </span>
                </li>
                <li class="flex items-center justify-between">
                    <span>Active Sessions</span>
                    <span class="font-bold text-slate-900 dark:text-white">{{ $activeSessionCount ?? 1 }}</span>
                </li>
                <li class="flex items-center justify-between">
                    <span>Last Sign-In</span>
                    <span class="font-medium text-slate-500 dark:text-slate-400">
                        {{ $user->last_login_at ? $user->last_login_at->diffForHumans() : 'Not recorded yet' }}
                    </span>
                </li>
            </ul>
        </div>
    </div>

    <!-- Active Devices & Sessions Card -->
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700/70 rounded-2xl p-6 shadow-xs space-y-5">
        <div class="flex flex-wrap items-center justify-between gap-3 pb-3 border-b border-gray-100 dark:border-gray-700">
            <div>
                <h3 class="text-base font-black text-slate-900 dark:text-white">Active Devices &amp; Sessions</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                    Review active browser sessions signed into {{ $user->email }} and revoke unrecognized devices.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-500/15 text-emerald-600 dark:text-emerald-300 border border-emerald-500/30">
                    {{ ($activeSessions ?? collect())->count() }} Active Session{{ ($activeSessions ?? collect())->count() === 1 ? '' : 's' }}
                </span>

                @if ($sessionManagementSupported ?? false)
                    <button type="button"
                            @click="showRevokeSessionsForm = !showRevokeSessionsForm"
                            class="px-3.5 py-1.5 rounded-xl text-xs font-bold text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-950/30 hover:bg-rose-100 dark:hover:bg-rose-950/50 border border-rose-200 dark:border-rose-800/50 transition">
                        Sign Out Other Devices
                    </button>
                @endif
            </div>
        </div>

        @if (!($sessionManagementSupported ?? false))
            <div class="p-3.5 rounded-xl bg-amber-500/10 border border-amber-500/30 text-xs text-amber-700 dark:text-amber-300">
                Multi-device session enumeration requires <code class="font-mono">SESSION_DRIVER=database</code>. Currently showing your active request session (<code class="font-mono">{{ config('session.driver') }}</code> driver).
            </div>
        @endif

        <div x-show="showRevokeSessionsForm" style="display: none;"
             class="p-4 rounded-xl bg-rose-50/70 dark:bg-rose-950/20 border border-rose-200 dark:border-rose-800/60">
            <form method="POST" action="{{ route('business.security.sessions.revoke-others') }}" class="flex flex-col sm:flex-row sm:items-end gap-3">
                @csrf
                <div class="flex-1">
                    <label class="block text-xs font-bold text-rose-700 dark:text-rose-300 mb-1">
                        Confirm Password to Sign Out All Other Devices *
                    </label>
                    <input type="password" name="current_password" required autocomplete="current-password"
                           placeholder="Enter your password"
                           class="w-full text-xs rounded-xl border-rose-200 dark:border-rose-800 dark:bg-gray-900 dark:text-white px-3.5 py-2 focus:border-rose-500 focus:ring-rose-500">
                    @error('current_password', 'revokeSessions')
                        <p class="text-[11px] font-semibold text-rose-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit" class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold shrink-0">
                    Confirm Sign Out Others
                </button>
            </form>
        </div>

        <div class="space-y-3">
            @forelse (($activeSessions ?? collect()) as $session)
                <div class="p-4 rounded-xl {{ $session['is_current'] ? 'bg-emerald-500/5 border-emerald-500/30' : 'bg-slate-50 dark:bg-gray-900/50 border-slate-200 dark:border-gray-700' }} border flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div class="space-y-1 min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-xs font-bold text-slate-900 dark:text-white">{{ $session['device_label'] }}</span>
                            @if ($session['is_current'])
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/15 text-emerald-600 dark:text-emerald-300 border border-emerald-500/30">
                                    Current Device
                                </span>
                            @endif
                        </div>
                        <p class="text-[11px] font-mono text-slate-500 dark:text-slate-400">
                            IP: {{ $session['ip_address'] ?? 'Unavailable' }} &bull; Last active: {{ $session['last_active_human'] }}
                        </p>
                        @if (!empty($session['user_agent']))
                            <p class="text-[11px] text-slate-400 truncate max-w-xl" title="{{ $session['user_agent'] }}">
                                {{ $session['user_agent'] }}
                            </p>
                        @endif
                    </div>

                    @if (!$session['is_current'] && ($sessionManagementSupported ?? false))
                        <form method="POST" action="{{ route('business.security.sessions.destroy', $session['session_ref']) }}" class="shrink-0">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="px-3.5 py-1.5 rounded-xl text-xs font-bold text-rose-600 dark:text-rose-400 bg-white dark:bg-gray-800 hover:bg-rose-50 dark:hover:bg-rose-950/30 border border-rose-200 dark:border-rose-800/50 transition">
                                Revoke
                            </button>
                        </form>
                    @endif
                </div>
            @empty
                <p class="text-xs text-slate-500 dark:text-slate-400 text-center py-6">
                    No active sessions found.
                </p>
            @endforelse
        </div>
    </div>

    @if ($canManageBusinessSettings)
        <!-- Workspace Security Settings Card -->
        <form method="POST" action="{{ route('business.settings.security.update') }}"
              class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700/70 rounded-2xl p-6 shadow-xs space-y-5">
            @csrf
            @method('PUT')

            <div class="pb-3 border-b border-gray-100 dark:border-gray-700">
                <h3 class="text-base font-black text-slate-900 dark:text-white">Workspace Security Settings</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                    Configure real backend session timeout enforcement and suspicious sign-in alerting for {{ $business->name }}.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-start">
                <div>
                    <label for="session_timeout_minutes" class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                        Idle Session Timeout
                    </label>
                    <select id="session_timeout_minutes" name="session_timeout_minutes"
                            class="w-full text-xs rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3.5 py-2.5 focus:border-orange-500 focus:ring-orange-500">
                        @php $currentTimeout = (int) ($securitySettings['session_timeout_minutes'] ?? 0); @endphp
                        <option value="0" @selected($currentTimeout === 0)>Platform Default ({{ config('session.lifetime', 120) }} min)</option>
                        <option value="15" @selected($currentTimeout === 15)>15 minutes</option>
                        <option value="30" @selected($currentTimeout === 30)>30 minutes</option>
                        <option value="60" @selected($currentTimeout === 60)>60 minutes</option>
                        <option value="120" @selected($currentTimeout === 120)>2 hours (120 min)</option>
                        <option value="480" @selected($currentTimeout === 480)>8 hours (480 min)</option>
                    </select>
                </div>

                <label class="p-3.5 rounded-xl bg-slate-50 dark:bg-gray-900/50 border border-slate-200 dark:border-gray-700 flex items-start gap-2.5 cursor-pointer">
                    <input type="checkbox" name="alert_unfamiliar_login" value="1"
                           @checked($securitySettings['alert_unfamiliar_login'] ?? true)
                           class="mt-0.5 rounded border-gray-300 dark:border-gray-600 text-orange-600 focus:ring-orange-500">
                    <span class="text-xs">
                        <span class="font-bold text-slate-900 dark:text-white block">Alert on New IP Sign-In</span>
                        <span class="text-[11px] text-slate-500 dark:text-slate-400">Notify owner channels when signing in from an unseen IP.</span>
                    </span>
                </label>

                <label class="p-3.5 rounded-xl bg-slate-50 dark:bg-gray-900/50 border border-slate-200 dark:border-gray-700 flex items-start gap-2.5 cursor-pointer">
                    <input type="checkbox" name="alert_failed_logins" value="1"
                           @checked($securitySettings['alert_failed_logins'] ?? true)
                           class="mt-0.5 rounded border-gray-300 dark:border-gray-600 text-orange-600 focus:ring-orange-500">
                    <span class="text-xs">
                        <span class="font-bold text-slate-900 dark:text-white block">Alert on Failed Attempts</span>
                        <span class="text-[11px] text-slate-500 dark:text-slate-400">Send security alerts after 3+ wrong passwords or lockouts.</span>
                    </span>
                </label>
            </div>

            <div class="flex justify-end pt-3 border-t border-gray-100 dark:border-gray-700">
                <button type="submit"
                        class="px-5 py-2.5 bg-orange-600 hover:bg-orange-700 text-white font-bold text-xs rounded-xl shadow-xs transition">
                    Save Security Policy
                </button>
            </div>
        </form>
    @endif

    <!-- Recent Login & Security Activity -->
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700/70 rounded-2xl p-6 shadow-xs space-y-5">
        <div class="flex flex-wrap items-start justify-between gap-3 pb-4 border-b border-gray-100 dark:border-gray-700">
            <div>
                <h3 class="text-base font-black text-slate-900 dark:text-white">Recent Login Activity</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                    Review recent sign-in attempts, device activity, and security audit events for {{ $user->email }}.
                </p>
            </div>
            <span class="text-xs font-semibold text-slate-400">Showing up to 25 latest records</span>
        </div>

        <!-- 30-Day Sign-In Summary -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-gray-900/50 border border-slate-200 dark:border-gray-700">
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Successful Sign-Ins (30d)</p>
                <p class="text-lg font-black text-slate-900 dark:text-white mt-0.5">{{ $recentLoginCount ?? 0 }}</p>
            </div>
            <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-gray-900/50 border border-slate-200 dark:border-gray-700">
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Failed Attempts (30d)</p>
                <p class="text-lg font-black {{ ($recentFailureCount ?? 0) > 0 ? 'text-rose-500' : 'text-emerald-500' }} mt-0.5">
                    {{ $recentFailureCount ?? 0 }}
                </p>
            </div>
            <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-gray-900/50 border border-slate-200 dark:border-gray-700">
                <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Last Verified Device / IP</p>
                <p class="text-xs font-bold text-slate-900 dark:text-white mt-1 truncate">
                    {{ $lastLoginDevice ?? 'Not recorded yet' }}
                </p>
                @if (!empty($lastLoginEntry?->ip_address))
                    <p class="text-[11px] font-mono text-slate-500 dark:text-slate-400 truncate">{{ $lastLoginEntry->ip_address }}</p>
                @endif
            </div>
        </div>

        @if (($user->failed_login_attempts ?? 0) >= 3)
            <div class="p-3.5 rounded-xl bg-rose-500/10 border border-rose-500/30 text-xs text-rose-700 dark:text-rose-300">
                <span class="font-bold">Security Notice:</span> {{ $user->failed_login_attempts }} consecutive failed sign-in attempt(s) were recorded since your last successful login. Review your recent IP activity below and rotate your password if unfamiliar.
            </div>
        @endif

        <!-- Activity Filter Pills -->
        <div class="flex flex-wrap items-center gap-2">
            @php
                $filters = [
                    'all' => 'All Activity',
                    'logins' => 'Sign-Ins & Logouts',
                    'failed' => 'Failed Attempts',
                    '2fa' => '2FA Events',
                    'account' => 'Account & Keys',
                ];
                $currentFilter = $activityFilter ?? 'all';
            @endphp
            @foreach ($filters as $filterKey => $filterLabel)
                <a href="{{ request()->fullUrlWithQuery(['section' => 'security', 'activity_filter' => $filterKey]) }}"
                   class="px-3 py-1.5 rounded-xl text-xs font-bold border transition {{ $currentFilter === $filterKey
                       ? 'bg-orange-600 text-white border-orange-600 shadow-xs'
                       : 'bg-slate-50 dark:bg-gray-900/60 text-slate-600 dark:text-slate-300 border-slate-200 dark:border-gray-700 hover:border-orange-400' }}">
                    {{ $filterLabel }}
                </a>
            @endforeach
        </div>

        @if ($securityLogs->isEmpty())
            <div class="py-10 text-center space-y-1">
                <p class="text-xs font-bold text-slate-700 dark:text-slate-300">No matching security events found</p>
                <p class="text-[11px] text-slate-500 dark:text-slate-400">
                    Sign-ins, failed login attempts, 2FA changes, password updates, and role updates for your account appear here.
                </p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600 dark:text-slate-300">
                    <thead class="border-b border-gray-200 dark:border-gray-700 text-slate-400 uppercase font-bold text-[11px]">
                        <tr>
                            <th class="pb-3 pr-3">Event</th>
                            <th class="pb-3 pr-3">Status</th>
                            <th class="pb-3 pr-3">Device &amp; Browser</th>
                            <th class="pb-3 pr-3">IP Address</th>
                            <th class="pb-3">Time</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                        @foreach ($securityLogs as $log)
                            <tr>
                                <td class="py-3 pr-3">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <span class="font-bold text-slate-900 dark:text-white">{{ $log->event_label ?? $log->event_type }}</span>
                                        @if (!empty($log->is_new_device_ip))
                                            <span class="px-1.5 py-0.5 rounded-md text-[10px] font-bold bg-amber-500/15 text-amber-600 dark:text-amber-300 border border-amber-500/30">
                                                New IP
                                            </span>
                                        @endif
                                    </div>
                                    <span class="text-[10px] font-mono text-slate-400">{{ $log->event_type }}</span>
                                </td>
                                <td class="py-3 pr-3">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold {{ $log->event_badge_class ?? 'bg-slate-100 text-slate-700' }}">
                                        {{ $log->event_status_text ?? 'Recorded' }}
                                    </span>
                                </td>
                                <td class="py-3 pr-3 text-[11px] text-slate-600 dark:text-slate-300 max-w-xs truncate" title="{{ $log->user_agent ?? 'Unavailable' }}">
                                    {{ $log->parsed_device ?? 'Unknown Device' }}
                                </td>
                                <td class="py-3 pr-3 font-mono text-[11px]">{{ $log->ip_address ?? '—' }}</td>
                                <td class="py-3 text-slate-400 whitespace-nowrap" title="{{ $log->created_at?->toDateTimeString() }}">
                                    {{ $log->created_at?->diffForHumans() ?? '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
