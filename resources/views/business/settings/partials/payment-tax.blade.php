<div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700/70 rounded-2xl p-6 shadow-xs">
    <div class="pb-4 mb-6 border-b border-gray-100 dark:border-gray-700">
        <h3 class="text-base font-black text-slate-900 dark:text-white flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-orange-500"></span>
            <span>Payment &amp; Tax Configuration</span>
        </h3>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
            Set default billing currency, USD-to-KHR exchange rate, VAT rate, and Bakong KHQR merchant details.
        </p>
    </div>

    <form method="POST" action="{{ route('business.settings.payment.update') }}" class="space-y-6">
        @csrf
        @method('PUT')

        <!-- Currency & Tax -->
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">Primary Currency *</label>
                <select name="default_currency" class="w-full text-xs rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3.5 py-2.5 focus:border-orange-500 focus:ring-orange-500 font-bold">
                    <option value="USD" {{ old('default_currency', $business->default_currency) === 'USD' ? 'selected' : '' }}>USD ($ - US Dollar)</option>
                    <option value="KHR" {{ old('default_currency', $business->default_currency) === 'KHR' ? 'selected' : '' }}>KHR (៛ - Khmer Riel)</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">Exchange Rate (1 USD = KHR) *</label>
                <input type="number" step="1" name="exchange_rate_khr" value="{{ old('exchange_rate_khr', (int)$business->exchange_rate_khr) }}" required
                       class="w-full text-sm rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3.5 py-2.5 focus:border-orange-500 focus:ring-orange-500 font-bold">
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">Tax Rate (VAT / %)</label>
                <input type="number" step="0.01" min="0" max="100" name="tax_percent" value="{{ old('tax_percent', $business->tax_percent) }}"
                       class="w-full text-sm rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3.5 py-2.5 focus:border-orange-500 focus:ring-orange-500">
            </div>
        </div>

        <!-- Bakong KHQR & Accepted Payment Methods -->
        <div class="pt-5 border-t border-gray-100 dark:border-gray-700 space-y-5">
            <div>
                <h4 class="text-xs font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider">Bakong KHQR &amp; Checkout Channels</h4>
                <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5">
                    Configure your Bakong account identifier used when generating EMVCo KHQR payment codes on customer checkout and POS.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">Bakong KHQR Account ID</label>
                    <input type="text" name="bakong_account_id"
                           value="{{ old('bakong_account_id', $storedSettings['payment.bakong_account_id'] ?? '') }}"
                           placeholder="e.g. yourshop@aclb or yourshop@wing"
                           class="w-full text-xs rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3.5 py-2.5 focus:border-orange-500 focus:ring-orange-500 font-mono">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">KHQR Display Merchant Name</label>
                    <input type="text" name="bakong_merchant_name"
                           value="{{ old('bakong_merchant_name', $storedSettings['payment.bakong_merchant_name'] ?? $business->name) }}"
                           placeholder="Name shown on banking app"
                           class="w-full text-xs rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3.5 py-2.5 focus:border-orange-500 focus:ring-orange-500">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <label class="flex items-start gap-3 p-3.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-slate-50/70 dark:bg-gray-900/40 cursor-pointer">
                    <input type="checkbox" name="khqr_enabled" value="1"
                           {{ old('khqr_enabled', $storedSettings['payment.khqr_enabled'] ?? true) ? 'checked' : '' }}
                           class="mt-0.5 rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                    <div>
                        <span class="block text-xs font-bold text-slate-900 dark:text-white">Accept Bakong KHQR</span>
                        <span class="block text-[11px] text-slate-500 dark:text-slate-400">Allow scan-to-pay KHQR option on digital menu &amp; POS.</span>
                    </div>
                </label>

                <label class="flex items-start gap-3 p-3.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-slate-50/70 dark:bg-gray-900/40 cursor-pointer">
                    <input type="checkbox" name="cash_enabled" value="1"
                           {{ old('cash_enabled', $storedSettings['payment.cash_enabled'] ?? true) ? 'checked' : '' }}
                           class="mt-0.5 rounded border-gray-300 text-orange-600 focus:ring-orange-500">
                    <div>
                        <span class="block text-xs font-bold text-slate-900 dark:text-white">Accept Cash at Counter / Delivery</span>
                        <span class="block text-[11px] text-slate-500 dark:text-slate-400">Allow USD &amp; KHR cash settlements.</span>
                    </div>
                </label>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">Receipt Footer Message</label>
                <input type="text" name="receipt_footer"
                       value="{{ old('receipt_footer', $storedSettings['payment.receipt_footer'] ?? 'Thank you for dining with us! សូមអរគុណ!') }}"
                       placeholder="Message printed at bottom of 80mm thermal receipts"
                       class="w-full text-xs rounded-xl border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3.5 py-2.5 focus:border-orange-500 focus:ring-orange-500">
            </div>
        </div>

        <div class="flex items-center justify-end pt-5 border-t border-gray-100 dark:border-gray-700">
            <button type="submit" class="px-6 py-2.5 bg-orange-600 hover:bg-orange-700 text-white font-bold text-xs rounded-xl shadow-xs transition active:scale-95">
                Save Payment &amp; Tax Settings
            </button>
        </div>
    </form>
</div>
