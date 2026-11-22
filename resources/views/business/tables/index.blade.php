<x-business-layout title="Tables & QR" heading="Tables & QR Standees">
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 class="font-bold text-2xl text-slate-900 dark:text-white tracking-tight flex items-center gap-2">
                    <svg class="w-7 h-7 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                    </svg>
                    <span>{{ __('Tables & Smart QR Codes') }} <span class="text-slate-400 font-normal">/</span> <span class="font-khmer font-bold">តុ និង QR កូដ</span></span>
                </h2>
                <p class="text-sm text-slate-600 dark:text-slate-300 font-medium mt-1">
                    {{ __('Manage dine-in tables, generate contactless QR standees, and track customer scan activity.') }}
                </p>
            </div>
            <div class="flex items-center gap-3">
                <button type="button" onclick="window.print()" class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 border border-slate-300 dark:border-gray-700 rounded-xl text-sm font-bold text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-gray-700 transition shadow-sm">
                    <svg class="w-4 h-4 text-slate-500 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    {{ __('Print QR Standees') }}
                </button>
                <button type="button" @click="$dispatch('open-modal', 'add-table-modal')" class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-sm font-bold transition shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    {{ __('Add Table') }}
                </button>
            </div>
        </div>
    </x-slot>

    <div class="space-y-6">
        @php
            $qrBaseUrl = \App\Services\Qr\QrCodeService::getBaseUrl();
            $masterMenuUrl = \App\Services\Qr\QrCodeService::getMenuUrl($business);
            $isLocalLoopback = \Illuminate\Support\Str::contains($qrBaseUrl, ['localhost', '127.0.0.1', '::1']);
        @endphp

        @if($isLocalLoopback)
            <!-- Local Network / Mobile Scan Guidance Banner -->
            <div class="bg-amber-50 dark:bg-amber-950/40 border-l-4 border-amber-500 p-4 rounded-2xl shadow-xs text-xs text-amber-950 dark:text-amber-200 flex items-start gap-3">
                <svg class="w-6 h-6 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <div class="space-y-1">
                    <p class="font-bold text-sm text-amber-950 dark:text-amber-100 font-khmer">
                        ⚠️ ហេតុអ្វីបានជាទូរស័ព្ទ Scan មិនចេញ? (Why Mobile Phones Cannot Open 127.0.0.1)
                    </p>
                    <p class="leading-relaxed font-khmer">
                        ពេលនេះ QR Code កំពុងផ្ទុកអាសយដ្ឋាន <code class="font-bold font-mono bg-amber-100 dark:bg-amber-900/60 text-red-600 dark:text-red-400 px-1 py-0.5 rounded">{{ $qrBaseUrl }}</code>។ នៅលើទូរស័ព្ទ <code>127.0.0.1</code> គឺជាម៉ាស៊ីនទូរស័ព្ទខ្លួនឯង មិនមែនកុំព្យូទ័ររបស់អ្នកទេ ហេតុនេះហើយទើបទូរស័ព្ទចេញ <em>"couldn't connect to the server"</em>។
                    </p>
                    <p class="pt-1 font-semibold text-amber-950 dark:text-amber-100 font-khmer">ដើម្បីឱ្យទូរស័ព្ទដែលភ្ជាប់ Wi-Fi ឬ Hotspot ជាមួយគ្នា Scan មើល Menu បានភ្លាមៗ៖</p>
                    <ol class="list-decimal list-inside space-y-1 pl-1 text-[11px] font-medium font-khmer">
                        <li>បើក Server លើ Terminal ដោយប្រើពាក្យបញ្ជា: <code class="bg-amber-200/70 dark:bg-amber-900/80 font-mono font-bold text-slate-900 dark:text-amber-200 px-1.5 py-0.5 rounded">php artisan serve --host=0.0.0.0 --port=8000</code></li>
                        <li>ដាក់ IP ម៉ាស៊ីនក្នុង <code class="font-mono bg-amber-100 dark:bg-amber-900/60 px-1 py-0.5 rounded">.env</code>: <code class="bg-amber-200/70 dark:bg-amber-900/80 font-mono font-bold text-slate-900 dark:text-amber-200 px-1.5 py-0.5 rounded">QR_BASE_URL=http://YOUR_LAN_IP:8000</code> រួច Refresh ទំព័រនេះដើម្បីឱ្យ QR ប្តូរទៅ IP ពិត។</li>
                    </ol>
                </div>
            </div>
        @endif

        <!-- Main Business Digital Menu QR Card -->
        <div class="bg-gradient-to-r from-emerald-700 via-teal-700 to-emerald-900 rounded-2xl shadow-xl p-6 sm:p-8 text-white relative overflow-hidden">
            <div class="relative z-10 flex flex-col md:flex-row items-center justify-between gap-6">
                <div class="space-y-3 max-w-xl text-center md:text-left">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-500/30 text-emerald-100 border border-emerald-400/30">
                        <span class="w-2 h-2 rounded-full bg-emerald-300 animate-pulse"></span>
                        {{ __('Storefront & Takeaway Master QR') }}
                    </span>
                    <h3 class="text-2xl font-black tracking-tight text-white">
                        {{ $business->name }} Master Menu
                    </h3>
                    <p class="text-emerald-100 text-sm font-medium leading-relaxed">
                        {{ __('Place this QR code at your reception counter, flyer, or social media. Customers can view your full catalog, choose takeaway, or order delivery directly.') }}
                    </p>
                    <div class="flex flex-wrap items-center gap-3 pt-2">
                        <a href="{{ $masterMenuUrl }}" target="_blank" class="inline-flex items-center gap-2 px-4 py-2 bg-white text-emerald-900 rounded-xl text-sm font-bold shadow hover:bg-emerald-50 transition">
                            <svg class="w-4 h-4 text-emerald-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                            </svg>
                            {{ __('Open Live Menu') }}
                        </a>
                        <span class="text-xs text-emerald-100 font-mono font-medium bg-emerald-900/50 px-3 py-2 rounded-lg border border-emerald-500/30 truncate max-w-xs">
                            {{ $masterMenuUrl }}
                        </span>
                    </div>
                </div>

                <div class="bg-white p-4 rounded-2xl shadow-2xl flex flex-col items-center gap-2.5 shrink-0 border-4 border-emerald-400/40">
                    <div class="p-2 bg-white rounded-xl" id="qr-master-box">
                        {!! SimpleSoftwareIO\QrCode\Facades\QrCode::size(140)->color(6, 78, 59)->generate($masterMenuUrl) !!}
                    </div>
                    <span class="text-[11px] font-bold text-slate-800 tracking-wide uppercase">{{ __('Scan for Menu') }}</span>
                    <div class="flex items-center gap-2 pt-1 w-full">
                        <a href="{{ route('business.tables.master.download-png') }}"
                           class="flex-1 py-1.5 px-2 bg-slate-900 hover:bg-slate-800 text-white rounded-lg text-[10px] font-bold shadow-xs transition text-center flex items-center justify-center gap-1">
                            <svg class="w-3 h-3 text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            Download PNG
                        </a>
                        <a href="{{ route('business.tables.master.standee') }}" target="_blank"
                           class="flex-1 py-1.5 px-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-[10px] font-bold shadow-xs transition text-center flex items-center justify-center gap-1">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                            Print Standee (PDF)
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tables Management Grid -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-slate-200/80 dark:border-gray-700 p-6">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center pb-6 border-b border-slate-100 dark:border-gray-700 gap-4">
                <div>
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white">{{ __('Dine-In Tables & Individual QR Codes') }}</h3>
                    <p class="text-sm text-slate-600 dark:text-slate-300 font-medium">{{ __('Each table has a dedicated QR code that automatically links orders to that specific table.') }}</p>
                </div>
                <div class="flex items-center gap-2 text-xs font-bold text-slate-700 dark:text-slate-200">
                    <span class="flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800/50">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Available
                    </span>
                    <span class="flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-amber-50 text-amber-800 dark:bg-amber-950/50 dark:text-amber-300 border border-amber-200 dark:border-amber-800/50">
                        <span class="w-2 h-2 rounded-full bg-amber-500"></span> Occupied
                    </span>
                    <span class="flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-blue-50 text-blue-800 dark:bg-blue-950/50 dark:text-blue-300 border border-blue-200 dark:border-blue-800/50">
                        <span class="w-2 h-2 rounded-full bg-blue-500"></span> Reserved
                    </span>
                </div>
            </div>

            @if($tables->isEmpty())
                <div class="text-center py-16">
                    <div class="w-16 h-16 mx-auto mb-4 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 rounded-2xl flex items-center justify-center">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                    </div>
                    <h4 class="text-base font-bold text-slate-900 dark:text-white">{{ __('No tables created yet') }}</h4>
                    <p class="text-sm text-slate-600 dark:text-slate-300 font-medium max-w-sm mx-auto mt-1 mb-4">{{ __('Add tables for your restaurant or cafe so guests can scan and order right from their seats.') }}</p>
                    <button type="button" @click="$dispatch('open-modal', 'add-table-modal')" class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 text-white rounded-xl text-sm font-bold hover:bg-emerald-700 transition shadow-sm">
                        {{ __('Add First Table') }}
                    </button>
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6 pt-6">
                    @foreach($tables as $table)
                        @php
                            $targetUrl = \App\Services\Qr\QrCodeService::getTableUrl($business, $table->table_number);
                        @endphp
                        <div class="bg-slate-50 dark:bg-gray-900/60 hover:bg-white dark:hover:bg-gray-800 rounded-2xl border border-slate-200/90 dark:border-gray-700 hover:border-emerald-300 transition-all duration-200 shadow-sm hover:shadow-md p-5 flex flex-col justify-between group">
                            <!-- Card Header -->
                            <div class="flex items-start justify-between">
                                <div>
                                    <span class="text-xs font-bold text-emerald-800 dark:text-emerald-300 bg-emerald-100/80 dark:bg-emerald-950/60 px-2.5 py-1 rounded-lg">
                                        #{{ $table->table_number }}
                                    </span>
                                    <h4 class="font-bold text-slate-900 dark:text-white text-lg mt-1.5">{{ $table->name ?? "Table {$table->table_number}" }}</h4>
                                    <p class="text-xs text-slate-600 dark:text-slate-300 font-medium flex items-center gap-1 mt-0.5">
                                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                        {{ $table->capacity }} {{ __('seats') }}
                                    </p>
                                </div>

                                <div>
                                    @if($table->status === 'available')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300">
                                            Available
                                        </span>
                                    @elseif($table->status === 'occupied')
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 dark:bg-amber-950/50 dark:text-amber-300">
                                            Occupied
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-800 dark:bg-blue-950/50 dark:text-blue-300">
                                            Reserved
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <!-- QR Code Preview with Download & Print -->
                            <div class="my-4 py-3 bg-white rounded-xl border border-slate-200/70 flex flex-col items-center justify-center p-3 shadow-inner">
                                <div class="p-1 bg-white rounded-lg" id="qr-table-{{ $table->id }}">
                                    {!! SimpleSoftwareIO\QrCode\Facades\QrCode::size(110)->color(15, 23, 42)->generate($targetUrl) !!}
                                </div>
                                <span class="text-[10px] text-slate-600 font-mono font-bold mt-1.5 truncate max-w-full text-center">
                                    table/{{ $table->table_number }}
                                </span>
                                <div class="flex items-center gap-1.5 mt-2.5 w-full">
                                    <a href="{{ route('business.tables.download-png', $table) }}"
                                       class="flex-1 py-1 px-1.5 bg-slate-100 hover:bg-slate-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-slate-800 dark:text-slate-200 rounded-lg text-[10px] font-bold transition flex items-center justify-center gap-1 border border-slate-200 dark:border-gray-700">
                                        <svg class="w-3 h-3 text-slate-600 dark:text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                        PNG
                                    </a>
                                    <a href="{{ route('business.tables.standee', $table) }}" target="_blank"
                                       class="flex-1 py-1 px-1.5 bg-emerald-50 hover:bg-emerald-100 dark:bg-emerald-950/50 dark:hover:bg-emerald-900/60 text-emerald-800 dark:text-emerald-300 rounded-lg text-[10px] font-bold transition flex items-center justify-center gap-1 border border-emerald-200 dark:border-emerald-800">
                                        <svg class="w-3 h-3 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                        Standee / PDF
                                    </a>
                                </div>
                            </div>

                            <!-- Scan Stats & Quick Actions -->
                            <div class="space-y-3 pt-2 border-t border-slate-200/60 dark:border-gray-700">
                                <div class="flex items-center justify-between text-xs text-slate-600 dark:text-slate-300 font-medium">
                                    <span>{{ __('Scans') }}: <strong class="text-slate-900 dark:text-white font-bold font-mono">{{ $table->qrCode?->scan_count ?? 0 }}</strong></span>
                                    @if($table->qrCode?->last_scanned_at)
                                        <span class="text-[11px] text-slate-500 dark:text-slate-400 font-medium">{{ $table->qrCode->last_scanned_at->diffForHumans() }}</span>
                                    @endif
                                </div>

                                <div class="flex items-center gap-2">
                                    <!-- Change Status Form -->
                                    <form method="POST" action="{{ route('business.tables.update', $table) }}" class="flex-1">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="capacity" value="{{ $table->capacity }}">
                                        <input type="hidden" name="name" value="{{ $table->name }}">
                                        <select name="status" onchange="this.form.submit()" class="w-full text-xs py-1.5 pl-2 pr-6 rounded-lg border-slate-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-slate-800 dark:text-slate-100 font-bold focus:ring-emerald-500 focus:border-emerald-500">
                                            <option value="available" {{ $table->status === 'available' ? 'selected' : '' }}>Available</option>
                                            <option value="occupied" {{ $table->status === 'occupied' ? 'selected' : '' }}>Occupied</option>
                                            <option value="reserved" {{ $table->status === 'reserved' ? 'selected' : '' }}>Reserved</option>
                                        </select>
                                    </form>

                                    <!-- Delete Table -->
                                    <form method="POST" action="{{ route('business.tables.destroy', $table) }}" onsubmit="return confirm('Delete Table {{ $table->table_number }}?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 rounded-lg text-slate-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/40 transition" title="{{ __('Delete Table') }}">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- Modal: Add New Table -->
    <x-modal name="add-table-modal" :show="false" focusable>
        <form method="POST" action="{{ route('business.tables.store') }}" class="p-6">
            @csrf
            <div class="flex items-center justify-between pb-4 border-b border-slate-100 dark:border-gray-700">
                <h3 class="text-lg font-bold text-slate-900 dark:text-white">{{ __('Create New Table & Auto-Generate QR') }}</h3>
                <button type="button" @click="$dispatch('close')" class="text-slate-400 hover:text-slate-600 dark:hover:text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="space-y-4 py-4">
                <div>
                    <x-input-label for="table_number" :value="__('Table Number / Code *')" />
                    <x-text-input id="table_number" name="table_number" type="text" class="mt-1 block w-full font-mono font-bold" placeholder="e.g. 01, VIP-1, T-12" required />
                    <x-input-error :messages="$errors->get('table_number')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="name" :value="__('Display Name / Label (Optional)')" />
                    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" placeholder="e.g. Balcony Garden View" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="capacity" :value="__('Seat Capacity *')" />
                        <x-text-input id="capacity" name="capacity" type="number" min="1" value="4" class="mt-1 block w-full font-bold" required />
                    </div>

                    @if($branches->isNotEmpty())
                        <div>
                            <x-input-label for="branch_id" :value="__('Branch')" />
                            <select id="branch_id" name="branch_id" class="mt-1 block w-full border-slate-300 dark:border-gray-700 dark:bg-gray-900 dark:text-white rounded-xl shadow-sm focus:border-emerald-500 focus:ring-emerald-500 text-sm font-medium">
                                <option value="">Main Branch</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-slate-100 dark:border-gray-700">
                <button type="button" @click="$dispatch('close')" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-gray-700 dark:text-slate-200 rounded-xl text-sm font-bold transition">
                    {{ __('Cancel') }}
                </button>
                <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-sm font-bold transition shadow-sm">
                    {{ __('Create Table') }}
                </button>
            </div>
        </form>
    </x-modal>
</x-business-layout>
