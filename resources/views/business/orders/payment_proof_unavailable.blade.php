<x-business-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div class="flex items-center gap-3">
                <a href="{{ route('business.orders.show', $order) }}" class="p-2 rounded-xl border border-slate-200 dark:border-gray-700 text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-gray-800 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </a>
                <div>
                    <h2 class="font-bold text-xl text-slate-900 dark:text-white tracking-tight">
                        Payment Proof Record — <span class="font-mono">{{ $order->order_number }}</span>
                    </h2>
                    <p class="text-xs text-slate-600 dark:text-slate-300 mt-0.5">
                        Uploaded {{ $proof->created_at?->format('M d, Y \a\t h:i A') ?? '—' }}
                    </p>
                </div>
            </div>

            <a href="{{ route('business.orders.show', $order) }}" class="inline-flex items-center gap-2 px-4 py-2 bg-slate-900 hover:bg-slate-800 dark:bg-orange-600 dark:hover:bg-orange-700 text-white rounded-xl text-xs font-bold transition shadow-sm">
                &larr; Back to Order Details
            </a>
        </div>
    </x-slot>

    <div class="max-w-2xl mx-auto space-y-6">
        <div class="rounded-2xl border border-amber-200 bg-amber-50 dark:border-amber-800/60 dark:bg-amber-950/30 p-6 space-y-3">
            <div class="flex items-start gap-3">
                <div class="w-10 h-10 rounded-xl bg-amber-100 dark:bg-amber-900/50 text-amber-700 dark:text-amber-300 flex items-center justify-center shrink-0 font-black text-lg">
                    !
                </div>
                <div class="space-y-1">
                    <h3 class="font-bold text-base text-amber-950 dark:text-amber-200">
                        Original Screenshot Expired on Temporary Container Storage
                    </h3>
                    <p class="text-xs text-amber-900/90 dark:text-amber-300/90 leading-relaxed">
                        This payment proof was uploaded before persistent database binary storage was enabled, and the temporary container file was cleared during hosting redeployment. All payment proof metadata and reference details remain intact below, and all new customer uploads are now permanently preserved in PostgreSQL across deployments.
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-slate-200/80 dark:border-gray-700/70 p-6 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-gray-700 pb-3">
                <h4 class="font-bold text-sm text-slate-900 dark:text-white">Preserved Proof Metadata</h4>
                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold uppercase {{ $proof->status === 'approved' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300' : ($proof->status === 'rejected' ? 'bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300') }}">
                    {{ $proof->status }}
                </span>
            </div>

            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                <div>
                    <dt class="text-slate-500 dark:text-slate-400">Order Number</dt>
                    <dd class="font-mono font-bold text-slate-900 dark:text-white mt-0.5">{{ $order->order_number }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500 dark:text-slate-400">Order Amount</dt>
                    <dd class="font-mono font-bold text-emerald-700 dark:text-emerald-400 mt-0.5">${{ number_format((float) $order->total_amount, 2) }} ({{ number_format((float) $order->total_khr) }} ៛)</dd>
                </div>
                <div>
                    <dt class="text-slate-500 dark:text-slate-400">Payment Method</dt>
                    <dd class="font-semibold text-slate-900 dark:text-white mt-0.5">{{ $order->paymentMethodLabel() }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500 dark:text-slate-400">Transfer Reference</dt>
                    <dd class="font-mono font-semibold text-slate-900 dark:text-white mt-0.5">{{ $proof->customer_reference ?: ($order->payment_reference ?: '—') }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500 dark:text-slate-400">Original Filename</dt>
                    <dd class="font-mono text-slate-800 dark:text-slate-200 mt-0.5 break-all">{{ $proof->original_filename }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500 dark:text-slate-400">File Type &amp; Size</dt>
                    <dd class="font-mono text-slate-800 dark:text-slate-200 mt-0.5">{{ $proof->mime_type }} • {{ number_format(max(0, (int) $proof->file_size) / 1024, 1) }} KB</dd>
                </div>
                @if($proof->customer_note)
                    <div class="sm:col-span-2">
                        <dt class="text-slate-500 dark:text-slate-400">Customer Note</dt>
                        <dd class="text-slate-800 dark:text-slate-200 mt-0.5">{{ $proof->customer_note }}</dd>
                    </div>
                @endif
                @if($proof->reviewedBy || $proof->review_note)
                    <div class="sm:col-span-2 pt-2 border-t border-slate-100 dark:border-gray-700">
                        <dt class="text-slate-500 dark:text-slate-400">Staff Review</dt>
                        <dd class="text-slate-800 dark:text-slate-200 mt-0.5">
                            @if($proof->reviewedBy) Reviewed by <strong>{{ $proof->reviewedBy->name }}</strong> @endif
                            @if($proof->reviewed_at) on {{ $proof->reviewed_at->format('M d, Y h:i A') }} @endif
                            @if($proof->review_note) — {{ $proof->review_note }} @endif
                        </dd>
                    </div>
                @endif
            </dl>

            @if($order->payment_status !== 'paid')
                <div class="pt-4 border-t border-slate-100 dark:border-gray-700 space-y-2">
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">
                        Customer Order Tracking &amp; Proof Re-upload Link
                    </label>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400">
                        Share this secure link with the customer if you need them to re-upload their receipt image, or verify the transfer reference directly from your bank app on the order page.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-2">
                        <input type="text" readonly id="reupload-url-input" value="{{ $customerTrackingUrl }}"
                               class="flex-1 text-xs font-mono rounded-xl border border-slate-300 bg-slate-50 text-slate-900 dark:border-gray-700 dark:bg-gray-900 dark:text-slate-200 px-3 py-2">
                        <button type="button"
                                onclick="navigator.clipboard.writeText(document.getElementById('reupload-url-input').value); this.textContent = 'Copied!'; setTimeout(() => this.textContent = 'Copy Link', 2000);"
                                class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition cursor-pointer">
                            Copy Link
                        </button>
                        <a href="{{ $customerTrackingUrl }}" target="_blank" rel="noopener noreferrer"
                           class="px-4 py-2 rounded-xl border border-slate-300 dark:border-gray-700 text-slate-800 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-gray-700 text-xs font-bold text-center transition">
                            Open Page
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-business-layout>
