@php
    $isConnected = (bool) ($telegramConnection?->is_active);
    $notificationsActive = (bool) ($telegramConnection?->notifications_enabled);
    $isCustomBot = ($telegramConnection?->connection_mode === 'custom_bot');
@endphp

<div class="space-y-6"
     x-data="{
         pendingVerification: {{ $telegramPendingLink ? 'true' : 'false' }},
         copied: false,
         checkingStatus: false,
         checkHint: '',
         advancedOpen: {{ $isCustomBot || session('error') ? 'true' : 'false' }},
         pollHandle: null,
         init() {
             if (this.pendingVerification) {
                 this.startPolling();
             }
         },
         async pollOnce(manual = false) {
             if (manual) {
                 this.checkingStatus = true;
                 this.checkHint = '';
             }
             try {
                 const res = await fetch('{{ route('business.settings.telegram.status') }}', {
                     headers: { 'Accept': 'application/json' }
                 });
                 if (!res.ok) return;
                 const data = await res.json();
                 if (data.connected) {
                     if (this.pollHandle) clearInterval(this.pollHandle);
                     window.location.href = '{{ route('business.settings.index', ['section' => 'notifications']) }}';
                     return;
                 }
                 if (manual) {
                     this.checkHint = 'Still waiting for your /start verification message in Telegram...';
                 }
             } catch (e) {
                 if (manual) this.checkHint = 'Unable to check right now. Please try again.';
             } finally {
                 if (manual) this.checkingStatus = false;
             }
         },
         startPolling() {
             this.pollHandle = setInterval(() => this.pollOnce(false), 3000);
         },
         copyCommand(text) {
             if (navigator.clipboard) {
                 navigator.clipboard.writeText(text);
                 this.copied = true;
                 setTimeout(() => this.copied = false, 2000);
             }
         }
     }">

    <!-- 1. Telegram Notifications (One-Click Connection & Status) -->
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700/70 rounded-2xl p-6 shadow-xs space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-4 border-b border-gray-100 dark:border-gray-700">
            <div class="flex items-start gap-3">
                <span class="w-10 h-10 rounded-xl bg-sky-500/15 text-sky-500 flex items-center justify-center shrink-0 mt-0.5">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.894 8.221-1.97 9.28c-.145.658-.537.818-1.084.508l-3-2.21-1.446 1.394c-.16.16-.295.295-.605.295l.213-3.053 5.56-5.023c.242-.213-.054-.333-.373-.121l-6.869 4.326-2.96-.924c-.64-.203-.658-.64.135-.954l11.566-4.458c.538-.196 1.006.128.833.94z"/>
                    </svg>
                </span>
                <div>
                    <h3 class="text-base font-black text-slate-900 dark:text-white">Telegram Notifications</h3>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                        Connect your Telegram account to receive important business updates and order notifications directly in Telegram.
                    </p>
                </div>
            </div>

            @if ($isConnected)
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-500/15 text-emerald-600 dark:text-emerald-300 border border-emerald-500/30 shrink-0">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    Connected
                </span>
            @else
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-slate-100 dark:bg-gray-900 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-gray-700 shrink-0">
                    <span class="w-2 h-2 rounded-full bg-slate-400"></span>
                    Not Connected
                </span>
            @endif
        </div>

        @if ($isConnected)
            <!-- Connected Telegram Details -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="p-4 rounded-xl bg-slate-50 dark:bg-gray-900/50 border border-slate-200 dark:border-gray-700">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Connection Type</p>
                    <p class="text-xs font-black text-slate-900 dark:text-white mt-1">
                        {{ $isCustomBot ? 'Custom Business Bot' : 'Official SABAYRA Bot' }}
                    </p>
                    @if ($telegramConnection->bot_username)
                        <p class="text-[11px] font-mono text-sky-500 mt-0.5">{{ '@' . ltrim($telegramConnection->bot_username, '@') }}</p>
                    @endif
                </div>

                <div class="p-4 rounded-xl bg-slate-50 dark:bg-gray-900/50 border border-slate-200 dark:border-gray-700">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Masked Chat ID</p>
                    <p class="text-xs font-mono font-black text-slate-900 dark:text-white mt-1">
                        {{ $telegramConnection->masked_chat_id ?? '••••' }}
                    </p>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">Hidden for privacy</p>
                </div>

                <div class="p-4 rounded-xl bg-slate-50 dark:bg-gray-900/50 border border-slate-200 dark:border-gray-700">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Linked Account</p>
                    <p class="text-xs font-bold text-slate-900 dark:text-white mt-1 truncate">
                        {{ $telegramConnection->telegram_display_name ?? $telegramConnection->user?->name ?? $business->name }}
                    </p>
                    @if ($telegramConnection->telegram_username)
                        <p class="text-[11px] font-mono text-slate-500 dark:text-slate-400 mt-0.5 truncate">
                            {{ str_starts_with($telegramConnection->telegram_username, '@') ? $telegramConnection->telegram_username : '@' . $telegramConnection->telegram_username }}
                        </p>
                    @endif
                </div>

                <div class="p-4 rounded-xl bg-slate-50 dark:bg-gray-900/50 border border-slate-200 dark:border-gray-700">
                    <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Last Notification</p>
                    <p class="text-xs font-bold text-slate-900 dark:text-white mt-1">
                        {{ $telegramConnection->last_notification_at ? $telegramConnection->last_notification_at->diffForHumans() : 'No alerts sent yet' }}
                    </p>
                    <p class="text-[11px] {{ $telegramConnection->notifications_enabled ? 'text-emerald-500' : 'text-amber-500' }} font-semibold mt-0.5">
                        {{ $telegramConnection->notifications_enabled ? 'Delivery Active' : 'Delivery Paused' }}
                    </p>
                </div>
            </div>

            @if ($telegramConnection->last_error)
                <div class="p-3.5 rounded-xl bg-rose-50 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-800/60 text-xs text-rose-700 dark:text-rose-300">
                    <span class="font-bold">Last Delivery Warning:</span> {{ $telegramConnection->last_error }}
                </div>
            @endif

            <!-- Connected Actions Bar -->
            <div class="flex flex-wrap items-center justify-between gap-3 pt-4 border-t border-gray-100 dark:border-gray-700">
                <div class="flex flex-wrap items-center gap-2.5">
                    <form method="POST" action="{{ route('business.settings.telegram.toggle') }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="notifications_enabled" value="{{ $telegramConnection->notifications_enabled ? '0' : '1' }}">
                        <button type="submit"
                                class="px-4 py-2 rounded-xl text-xs font-bold border transition {{ $telegramConnection->notifications_enabled
                                    ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-500/20'
                                    : 'bg-amber-500/10 border-amber-500/30 text-amber-700 dark:text-amber-300 hover:bg-amber-500/20' }}">
                            {{ $telegramConnection->notifications_enabled ? 'Pause Notifications' : 'Resume Notifications' }}
                        </button>
                    </form>

                    <form method="POST" action="{{ route('business.settings.telegram.test') }}">
                        @csrf
                        <button type="submit"
                                class="px-4 py-2 rounded-xl text-xs font-bold bg-sky-600 hover:bg-sky-700 text-white shadow-xs transition">
                            Send Test Connection
                        </button>
                    </form>
                </div>

                <form method="POST" action="{{ route('business.settings.telegram.disconnect') }}"
                      onsubmit="return confirm(@js("Disconnect Telegram notifications and purge stored bot credentials for {$business->name}?"))">
                    @csrf
                    <button type="submit"
                            class="px-4 py-2 rounded-xl text-xs font-bold text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-950/30 hover:bg-rose-100 dark:hover:bg-rose-950/50 border border-rose-200 dark:border-rose-800/50 transition">
                        Disconnect Telegram
                    </button>
                </form>
            </div>
        @else
            <!-- Disconnected: One-Click Connection Workflow -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">
                <div class="lg:col-span-7 p-5 rounded-2xl bg-slate-50 dark:bg-gray-900/60 border border-slate-200/80 dark:border-gray-700/70 space-y-3">
                    <p class="text-xs font-black text-slate-900 dark:text-white uppercase tracking-wider">One-Click Connection Steps</p>
                    <ol class="text-xs text-slate-600 dark:text-slate-300 space-y-1.5 list-decimal list-inside leading-relaxed">
                        <li>Click <span class="font-bold text-slate-900 dark:text-white">Connect Telegram</span> to generate a 15-minute one-time verification code.</li>
                        <li>Open the SABAYRA Telegram Bot (<span class="font-mono text-sky-600 dark:text-sky-400">{{ '@' . $officialBotUsername }}</span>).</li>
                        <li>Tap <span class="font-bold text-slate-900 dark:text-white">Start</span> or send your verification command.</li>
                        <li>Complete the one-time verification inside Telegram (for a staff group chat, add <span class="font-mono text-sky-600 dark:text-sky-400">{{ '@' . $officialBotUsername }}</span> to the group and send <code class="font-mono">/start YOUR_CODE</code>).</li>
                        <li>Return to SABAYRA — status updates automatically once linked.</li>
                    </ol>

                    @if (!$officialBotConfigured)
                        <div class="p-3 rounded-xl bg-amber-500/10 border border-amber-500/30 text-[11px] text-amber-700 dark:text-amber-300">
                            Platform official bot token (<code class="font-mono">TELEGRAM_BOT_TOKEN</code>) is not configured in environment yet. You can still generate codes or connect your own custom bot below.
                        </div>
                    @endif
                </div>

                <div class="lg:col-span-5 p-5 rounded-2xl border border-slate-200 dark:border-gray-700 bg-white dark:bg-gray-900/40 space-y-4">
                    @if ($telegramPendingLink)
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-black text-orange-600 dark:text-orange-400">One-Time Code Active</span>
                                <span class="text-[11px] font-medium text-slate-500">Expires in ~15m</span>
                            </div>

                            <div class="p-3 rounded-xl bg-slate-900 text-white font-mono text-xs flex items-center justify-between gap-2">
                                <span class="truncate select-all">/start {{ $telegramPendingLink['code'] }}</span>
                                <button type="button"
                                        @click="copyCommand('/start {{ $telegramPendingLink['code'] }}')"
                                        class="px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-[10px] font-bold text-orange-400 shrink-0">
                                    <span x-show="!copied">Copy</span>
                                    <span x-show="copied" style="display: none;">Copied</span>
                                </button>
                            </div>

                            <a href="{{ $telegramPendingLink['deep_link'] }}" target="_blank" rel="noopener noreferrer"
                               class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-sky-600 hover:bg-sky-700 text-white text-xs font-bold shadow-xs transition">
                                <span>Open SABAYRA Telegram Bot &rarr;</span>
                            </a>

                            <button type="button"
                                    @click="pollOnce(true)"
                                    :disabled="checkingStatus"
                                    class="w-full py-2 px-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 disabled:opacity-60 text-white text-xs font-bold transition">
                                <span x-show="!checkingStatus">Check Verification Now</span>
                                <span x-show="checkingStatus" style="display: none;">Checking Telegram...</span>
                            </button>

                            <p x-show="checkHint" x-text="checkHint" style="display: none;" class="text-[11px] text-amber-500 text-center"></p>

                            <form method="POST" action="{{ route('business.settings.telegram.connect') }}">
                                @csrf
                                <button type="submit" class="w-full py-1.5 text-[11px] font-semibold text-slate-500 hover:text-orange-500 transition">
                                    Generate New Code
                                </button>
                            </form>
                        </div>
                    @else
                        <div class="space-y-3">
                            <p class="text-xs font-bold text-slate-900 dark:text-white">Ready to link your store?</p>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400">
                                Generate a single-use verification token tied exclusively to {{ $business->name }}.
                            </p>
                            <form method="POST" action="{{ route('business.settings.telegram.connect') }}">
                                @csrf
                                <button type="submit"
                                        class="w-full py-2.5 px-4 rounded-xl bg-orange-600 hover:bg-orange-700 text-white text-xs font-bold shadow-xs transition">
                                    Connect Telegram
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>

    <!-- 2. Advanced Configuration: Custom Business Telegram Bot -->
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700/70 rounded-2xl p-6 shadow-xs">
        <button type="button" @click="advancedOpen = !advancedOpen"
                class="w-full flex items-center justify-between text-left">
            <div>
                <h4 class="text-sm font-black text-slate-900 dark:text-white">Advanced Configuration (Custom Business Bot)</h4>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                    Use your own BotFather token and private group/channel Chat ID. Bot tokens are encrypted at rest and verified before saving.
                </p>
            </div>
            <svg class="w-5 h-5 text-slate-400 transition-transform" :class="advancedOpen ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>

        <div x-show="advancedOpen" style="display: none;" class="pt-5 mt-5 border-t border-gray-100 dark:border-gray-700">
            <form method="POST" action="{{ route('business.settings.telegram.custom') }}" class="space-y-4">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                            Telegram Bot Token
                        </label>
                        <input type="password"
                               name="bot_token"
                               autocomplete="new-password"
                               placeholder="{{ $isCustomBot ? 'Saved securely (enter new token to replace)' : 'e.g. 123456789:ABCdef...' }}"
                               class="w-full text-xs rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3.5 py-2.5 font-mono focus:border-orange-500 focus:ring-orange-500">
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">
                            Never displayed or returned after saving.
                        </p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-1.5">
                            Telegram Chat ID *
                        </label>
                        <input type="text"
                               name="chat_id"
                               value="{{ old('chat_id') }}"
                               required
                               placeholder="{{ $isConnected ? 'Current: ' . ($telegramConnection->masked_chat_id ?? 'Configured') : 'e.g. -1001234567890 or user chat ID' }}"
                               class="w-full text-xs rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3.5 py-2.5 font-mono focus:border-orange-500 focus:ring-orange-500">
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">
                            Start the bot first or add it as admin in your staff notification group.
                        </p>
                    </div>
                </div>

                <label class="inline-flex items-center gap-2.5 text-xs font-semibold text-slate-700 dark:text-slate-300 cursor-pointer">
                    <input type="checkbox" name="notifications_enabled" value="1"
                           {{ old('notifications_enabled', $telegramConnection?->notifications_enabled ?? true) ? 'checked' : '' }}
                           class="rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                    <span>Enable Telegram notifications immediately after verification</span>
                </label>

                <div class="flex flex-wrap items-center justify-end gap-3 pt-3 border-t border-gray-100 dark:border-gray-700">
                    <button type="submit"
                            class="px-5 py-2.5 bg-slate-900 hover:bg-slate-800 dark:bg-orange-600 dark:hover:bg-orange-700 text-white font-bold text-xs rounded-xl shadow-xs transition">
                        Test &amp; Save Custom Bot Connection
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 3. Notification Event Preferences Matrix -->
    <form method="POST" action="{{ route('business.settings.notifications.update') }}"
          class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700/70 rounded-2xl p-6 shadow-xs space-y-6">
        @csrf
        @method('PUT')

        <div class="flex flex-wrap items-start justify-between gap-4 pb-4 border-b border-gray-100 dark:border-gray-700">
            <div>
                <h4 class="text-sm font-black text-slate-900 dark:text-white">Notification Preferences</h4>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                    Choose which business events trigger Telegram, Email, In-App, and Browser alerts. Dispatches run via background queues and respect branch context.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <span class="px-2.5 py-1 rounded-full text-[11px] font-bold {{ $isConnected && $notificationsActive ? 'bg-emerald-500/15 text-emerald-500 border border-emerald-500/30' : ($isConnected ? 'bg-amber-500/15 text-amber-500 border border-amber-500/30' : 'bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300') }}">
                    Telegram: {{ $isConnected && $notificationsActive ? 'Connected' : ($isConnected ? 'Paused' : 'Not Connected') }}
                </span>

                <span class="px-2.5 py-1 rounded-full text-[11px] font-bold {{ ($emailConfigured ?? false) ? 'bg-emerald-500/15 text-emerald-500 border border-emerald-500/30' : 'bg-amber-500/15 text-amber-500 border border-amber-500/30' }}">
                    Email: {{ ($emailConfigured ?? false) ? strtoupper($mailMailer ?? 'SMTP') : 'Not Configured (' . strtoupper($mailMailer ?? 'log') . ')' }}
                </span>

                <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-500/15 text-emerald-500 border border-emerald-500/30">
                    In-App: Active
                </span>
            </div>
        </div>

        @if (($pendingQueueJobsCount ?? 0) > 0)
            <div class="p-3.5 rounded-xl bg-amber-500/10 border border-amber-500/30 text-xs text-amber-700 dark:text-amber-300">
                <span class="font-bold">Queue Notice:</span> {{ $pendingQueueJobsCount }} background notification job(s) are currently waiting in the database queue. Ensure <code class="font-mono">php artisan queue:work</code> is running, or restart <code class="font-mono">php artisan serve</code> if using <code class="font-mono">QUEUE_CONNECTION=sync</code> locally.
            </div>
        @endif

        @if (($businessBranches ?? collect())->isNotEmpty())
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 p-4 rounded-xl bg-slate-50 dark:bg-gray-900/50 border border-slate-200 dark:border-gray-700">
                <div>
                    <label for="notification_branch_id" class="block text-xs font-bold text-slate-900 dark:text-white">Branch Scope Filter</label>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">Filter notifications to a specific branch or receive events across all outlets.</p>
                </div>
                <select id="notification_branch_id" name="branch_id"
                        class="text-xs rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3.5 py-2 focus:border-orange-500 focus:ring-orange-500 sm:w-64">
                    <option value="">All Branches</option>
                    @foreach ($businessBranches as $branch)
                        <option value="{{ $branch->id }}" @selected(($notificationBranchId ?? null) === $branch->id)>
                            {{ $branch->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        @endif

        <div class="space-y-6">
            @foreach (($notificationCatalog ?? []) as $groupKey => $group)
                <div class="rounded-xl border border-gray-200 dark:border-gray-700/80 overflow-hidden">
                    <div class="px-4 py-3 bg-slate-50 dark:bg-gray-900/60 border-b border-gray-200 dark:border-gray-700/80 flex items-center justify-between">
                        <h5 class="text-xs font-black uppercase tracking-wider text-slate-700 dark:text-slate-200">
                            {{ $group['label'] }}
                        </h5>
                        <span class="text-[11px] font-semibold text-slate-400">{{ count($group['events']) }} events</span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="border-b border-gray-100 dark:border-gray-700/60 text-[10px] uppercase text-slate-400 font-bold bg-white dark:bg-gray-800">
                                <tr>
                                    <th class="px-4 py-2.5">Event</th>
                                    <th class="px-3 py-2.5 text-center w-24">Telegram</th>
                                    <th class="px-3 py-2.5 text-center w-24">Email</th>
                                    <th class="px-3 py-2.5 text-center w-24">In-App</th>
                                    <th class="px-3 py-2.5 text-center w-24">Browser</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                                @foreach ($group['events'] as $eventKey => $meta)
                                    @php
                                        $pref = $notificationPreferences[$eventKey] ?? [
                                            'telegram_enabled' => $meta['defaults']['telegram'] ?? false,
                                            'email_enabled' => $meta['defaults']['email'] ?? false,
                                            'in_app_enabled' => $meta['defaults']['in_app'] ?? true,
                                            'browser_enabled' => $meta['defaults']['browser'] ?? false,
                                        ];
                                    @endphp
                                    <tr class="hover:bg-slate-50/60 dark:hover:bg-gray-900/30 transition">
                                        <td class="px-4 py-3">
                                            <p class="font-bold text-slate-900 dark:text-white">{{ $meta['label'] }}</p>
                                            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">{{ $meta['description'] }}</p>
                                        </td>
                                        <td class="px-3 py-3 text-center">
                                            <input type="checkbox"
                                                   name="preferences[{{ $eventKey }}][telegram_enabled]"
                                                   value="1"
                                                   aria-label="{{ $meta['label'] }} Telegram"
                                                   @checked($pref['telegram_enabled'])
                                                   class="rounded border-gray-300 dark:border-gray-600 text-orange-600 focus:ring-orange-500">
                                        </td>
                                        <td class="px-3 py-3 text-center">
                                            <input type="checkbox"
                                                   name="preferences[{{ $eventKey }}][email_enabled]"
                                                   value="1"
                                                   aria-label="{{ $meta['label'] }} Email"
                                                   @checked($pref['email_enabled'])
                                                   class="rounded border-gray-300 dark:border-gray-600 text-orange-600 focus:ring-orange-500">
                                        </td>
                                        <td class="px-3 py-3 text-center">
                                            <input type="checkbox"
                                                   name="preferences[{{ $eventKey }}][in_app_enabled]"
                                                   value="1"
                                                   aria-label="{{ $meta['label'] }} In-App"
                                                   @checked($pref['in_app_enabled'])
                                                   class="rounded border-gray-300 dark:border-gray-600 text-orange-600 focus:ring-orange-500">
                                        </td>
                                        <td class="px-3 py-3 text-center">
                                            <input type="checkbox"
                                                   name="preferences[{{ $eventKey }}][browser_enabled]"
                                                   value="1"
                                                   aria-label="{{ $meta['label'] }} Browser"
                                                   @checked($pref['browser_enabled'])
                                                   class="rounded border-gray-300 dark:border-gray-600 text-orange-600 focus:ring-orange-500">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 pt-4 border-t border-gray-100 dark:border-gray-700">
            <p class="text-[11px] text-slate-500 dark:text-slate-400">
                Customer phone numbers, delivery addresses, and payment credentials are never included in external notification messages.
            </p>
            <button type="submit"
                    class="px-5 py-2.5 bg-orange-600 hover:bg-orange-700 text-white font-bold text-xs rounded-xl shadow-xs transition">
                Save Notification Preferences
            </button>
        </div>
    </form>

    <!-- 4. Recent Telegram Delivery Log -->
    <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700/70 rounded-2xl p-6 shadow-xs space-y-5">
        <div class="flex flex-wrap items-center justify-between gap-2 pb-3 border-b border-gray-100 dark:border-gray-700">
            <div>
                <h4 class="text-sm font-black text-slate-900 dark:text-white">Recent Telegram Delivery Log</h4>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                    Delivery status and error audit for Telegram verification, test sends, and queued business alerts.
                </p>
            </div>
        </div>

        @if ($telegramLogs->isEmpty())
            <p class="text-xs text-slate-500 dark:text-slate-400 py-4 text-center">
                No Telegram deliveries recorded yet for {{ $business->name }}.
            </p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-600 dark:text-slate-300">
                    <thead class="border-b border-gray-200 dark:border-gray-700 text-[11px] uppercase text-slate-400 font-bold">
                        <tr>
                            <th class="pb-2.5">Status</th>
                            <th class="pb-2.5">Event</th>
                            <th class="pb-2.5">Recipient</th>
                            <th class="pb-2.5">Details</th>
                            <th class="pb-2.5">Time</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60">
                        @foreach ($telegramLogs as $log)
                            <tr>
                                <td class="py-2.5">
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $log->status === 'sent' ? 'bg-emerald-500/15 text-emerald-500' : 'bg-rose-500/15 text-rose-500' }}">
                                        {{ strtoupper($log->status) }}
                                    </span>
                                </td>
                                <td class="py-2.5 font-mono text-[11px]">{{ $log->event_type }}</td>
                                <td class="py-2.5 font-mono text-[11px]">{{ $log->recipient_masked ?? '—' }}</td>
                                <td class="py-2.5 text-[11px] max-w-xs truncate">{{ $log->error_message ?: $log->message_preview }}</td>
                                <td class="py-2.5 text-slate-400 whitespace-nowrap">{{ $log->created_at?->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
