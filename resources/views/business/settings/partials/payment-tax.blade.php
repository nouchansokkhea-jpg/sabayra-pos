@php
    $cfg = $paymentConfig ?? \App\Models\BusinessPaymentConfig::resolveForBusiness($business);
    $platformBakongOn = $platformBakongEnabled ?? true;
    $platformAbaOn = $platformAbaEnabled ?? true;
    $bakongTokenReady = $platformBakongTokenConfigured ?? false;
@endphp

<div class="space-y-6" x-data="paymentTaxSettingsApp()">
    <form method="POST" action="{{ route('business.settings.payment.update') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        <!-- Billing Currency & Receipt Footer -->
        <div class="bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700/70 rounded-2xl p-6 shadow-xs space-y-5">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 pb-4 border-b border-slate-100 dark:border-gray-700">
                <div>
                    <h3 class="text-base font-black text-[#0F1E3A] dark:text-white flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-orange-500"></span>
                        <span>Currency &amp; Receipt Preferences</span>
                    </h3>
                    <p class="text-xs text-slate-600 dark:text-slate-300 mt-0.5">
                        Configure primary storefront billing currency, exchange rate, and printed receipt footer.
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                <div>
                    <label class="block text-xs font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider mb-1.5">Primary Currency *</label>
                    <select name="default_currency" class="w-full text-sm rounded-xl border border-slate-300 bg-white text-slate-900 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3.5 py-2.5 focus:border-orange-500 focus:ring-orange-500 font-bold">
                        <option value="USD" {{ old('default_currency', $business->default_currency) === 'USD' ? 'selected' : '' }}>USD ($ - US Dollar)</option>
                        <option value="KHR" {{ old('default_currency', $business->default_currency) === 'KHR' ? 'selected' : '' }}>KHR (៛ - Khmer Riel)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider mb-1.5">Exchange Rate (1 USD = KHR) *</label>
                    <input type="number" step="1" min="1000" max="100000" name="exchange_rate_khr" value="{{ old('exchange_rate_khr', (int) $business->exchange_rate_khr) }}" required
                           class="w-full text-sm rounded-xl border border-slate-300 bg-white text-slate-900 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3.5 py-2.5 focus:border-orange-500 focus:ring-orange-500 font-bold">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider mb-1.5">Receipt Footer Message</label>
                    <input type="text" name="receipt_footer"
                           value="{{ old('receipt_footer', $storedSettings['payment.receipt_footer'] ?? 'Thank you for dining with us! សូមអរគុណ!') }}"
                           placeholder="Message printed at bottom of receipts"
                           class="w-full text-sm rounded-xl border border-slate-300 bg-white text-slate-900 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3.5 py-2.5 focus:border-orange-500 focus:ring-orange-500">
                </div>
            </div>
        </div>

        <!-- 1. TAX SETTINGS -->
        <div class="bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700/70 rounded-2xl p-6 shadow-xs space-y-5">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 pb-4 border-b border-slate-100 dark:border-gray-700">
                <div>
                    <h3 class="text-base font-black text-[#0F1E3A] dark:text-white flex items-center gap-2">
                        <span class="px-2 py-0.5 rounded-md bg-[#0F1E3A] text-amber-400 text-[11px] font-extrabold">1</span>
                        <span>Tax Settings</span>
                    </h3>
                    <p class="text-xs text-slate-600 dark:text-slate-300 mt-1">
                        Set tax label, percentage rate, and exclusive or inclusive pricing. Every order saves a permanent tax snapshot.
                    </p>
                </div>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold"
                      :class="Number(taxRate) > 0 ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300' : 'bg-slate-100 text-slate-700 dark:bg-gray-700 dark:text-slate-300'"
                      x-text="Number(taxRate) > 0 ? (taxName || 'VAT') + ' Active (' + Number(taxRate).toFixed(2) + '%)' : 'Tax Disabled (0%)'">
                </span>
            </div>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider mb-1">Tax Name</label>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mb-2">Label displayed to customers (e.g. VAT, GST, Sales Tax).</p>
                    <input type="text" name="tax_name" x-model="taxName" maxlength="40"
                           placeholder="VAT"
                           class="w-full text-sm rounded-xl border border-slate-300 bg-white text-slate-900 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3.5 py-2.5 focus:border-orange-500 focus:ring-orange-500 font-semibold">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider mb-1">Tax Rate (%)</label>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mb-2">Set to 0 to disable tax.</p>
                    <input type="number" step="0.01" min="0" max="100" name="tax_percent" x-model="taxRate"
                           class="w-full text-sm rounded-xl border border-slate-300 bg-white text-slate-900 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3.5 py-2.5 focus:border-orange-500 focus:ring-orange-500 font-semibold">
                </div>
            </div>

            <label class="flex items-start gap-3 p-4 rounded-xl border border-slate-200 dark:border-gray-700 bg-slate-50/70 dark:bg-gray-900/40 cursor-pointer">
                <input type="hidden" name="tax_inclusive" value="0">
                <input type="checkbox" name="tax_inclusive" value="1" x-model="taxInclusive"
                       class="mt-0.5 h-4 w-4 rounded border-slate-300 text-orange-600 focus:ring-orange-500">
                <div>
                    <span class="block text-sm font-bold text-slate-900 dark:text-white">Tax Inclusive (prices already include tax)</span>
                    <span class="block text-xs text-slate-600 dark:text-slate-400 mt-0.5">
                        When enabled, tax is included in product prices and shown as a breakdown.
                    </span>
                </div>
            </label>

            <!-- Live Sample Tax & Service Preview ($10.00 item) -->
            <div class="rounded-xl bg-[#0F1E3A]/5 dark:bg-gray-900/70 border border-slate-200 dark:border-gray-700 p-4 text-xs space-y-1.5">
                <div class="font-bold text-[#0F1E3A] dark:text-amber-300 uppercase tracking-wider text-[11px]">
                    Calculation Preview ($10.00 product sample)
                </div>
                <div class="flex justify-between text-slate-700 dark:text-slate-300">
                    <span x-text="taxInclusive ? 'Subtotal including tax:' : 'Subtotal:'"></span>
                    <span class="font-mono font-bold">$10.00</span>
                </div>
                <div class="flex justify-between text-slate-700 dark:text-slate-300" x-show="serviceChargeEnabled && Number(serviceChargeRate) > 0">
                    <span x-text="'Service Charge (' + Number(serviceChargeRate).toFixed(2) + '%):'"></span>
                    <span class="font-mono font-bold" x-text="'$' + sampleServiceCharge().toFixed(2)"></span>
                </div>
                <div class="flex justify-between text-slate-700 dark:text-slate-300">
                    <span x-text="(taxName || 'VAT') + (taxInclusive ? ' component (' + Number(taxRate || 0).toFixed(2) + '%):' : ' (' + Number(taxRate || 0).toFixed(2) + '%):')"></span>
                    <span class="font-mono font-bold" x-text="'$' + sampleTaxAmount().toFixed(2)"></span>
                </div>
                <div class="flex justify-between font-black text-slate-900 dark:text-white pt-1.5 border-t border-slate-200 dark:border-gray-700">
                    <span>Total Charged to Customer:</span>
                    <span class="font-mono text-orange-600 dark:text-amber-400" x-text="'$' + sampleGrandTotal().toFixed(2)"></span>
                </div>
            </div>
        </div>

        <!-- 2. SERVICE CHARGE -->
        <div class="bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700/70 rounded-2xl p-6 shadow-xs space-y-5">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 pb-4 border-b border-slate-100 dark:border-gray-700">
                <div>
                    <h3 class="text-base font-black text-[#0F1E3A] dark:text-white flex items-center gap-2">
                        <span class="px-2 py-0.5 rounded-md bg-[#0F1E3A] text-amber-400 text-[11px] font-extrabold">2</span>
                        <span>Service Charge</span>
                    </h3>
                    <p class="text-xs text-slate-600 dark:text-slate-300 mt-1">
                        Add a service charge to customer orders. Calculated on the net pre-tax subtotal (after discounts) across checkout, POS, orders, receipts, and reports.
                    </p>
                </div>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold"
                      :class="serviceChargeEnabled ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300' : 'bg-slate-100 text-slate-700 dark:bg-gray-700 dark:text-slate-300'"
                      x-text="serviceChargeEnabled ? 'Enabled (' + Number(serviceChargeRate || 0).toFixed(2) + '%)' : 'Disabled'">
                </span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 items-start">
                <label class="flex items-start gap-3 p-4 rounded-xl border border-slate-200 dark:border-gray-700 bg-slate-50/70 dark:bg-gray-900/40 cursor-pointer">
                    <input type="hidden" name="service_charge_enabled" value="0">
                    <input type="checkbox" name="service_charge_enabled" value="1" x-model="serviceChargeEnabled"
                           class="mt-0.5 h-4 w-4 rounded border-slate-300 text-orange-600 focus:ring-orange-500">
                    <div>
                        <span class="block text-sm font-bold text-slate-900 dark:text-white">Enable Service Charge</span>
                        <span class="block text-xs text-slate-600 dark:text-slate-400 mt-0.5">
                            Add a service charge to customer orders.
                        </span>
                    </div>
                </label>

                <div>
                    <label class="block text-xs font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider mb-1">Service Charge (%)</label>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 mb-2">Percentage applied to net order subtotal before tax.</p>
                    <input type="number" step="0.01" min="0" max="100" name="service_charge_rate" x-model="serviceChargeRate"
                           class="w-full text-sm rounded-xl border border-slate-300 bg-white text-slate-900 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3.5 py-2.5 focus:border-orange-500 focus:ring-orange-500 font-semibold">
                </div>
            </div>
        </div>

        <!-- 3. PAYMENT METHODS -->
        <div class="bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700/70 rounded-2xl p-6 shadow-xs space-y-6">
            <div class="pb-4 border-b border-slate-100 dark:border-gray-700">
                <h3 class="text-base font-black text-[#0F1E3A] dark:text-white flex items-center gap-2">
                    <span class="px-2 py-0.5 rounded-md bg-[#0F1E3A] text-amber-400 text-[11px] font-extrabold">3</span>
                    <span>Payment Methods</span>
                </h3>
                <p class="text-xs text-slate-600 dark:text-slate-300 mt-1">
                    Choose which payment methods customers can use at checkout. Only active and properly configured methods appear on your storefront checkout.
                </p>
            </div>

            <!-- 3.1 Bank Transfer -->
            <div class="rounded-2xl border border-slate-200 dark:border-gray-700 p-5 space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <h4 class="text-sm font-black text-slate-900 dark:text-white">Bank Transfer</h4>
                            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold {{ $cfg->bank_transfer_enabled ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300' : 'bg-slate-100 text-slate-700 dark:bg-gray-700 dark:text-slate-300' }}">
                                {{ $cfg->bank_transfer_enabled ? 'Enabled' : 'Disabled' }}
                            </span>
                        </div>
                        <p class="text-xs text-slate-600 dark:text-slate-400 mt-0.5">Allow customers to pay by bank transfer and send proof.</p>
                    </div>
                    <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                        <input type="hidden" name="bank_transfer_enabled" value="0">
                        <input type="checkbox" name="bank_transfer_enabled" value="1"
                               @checked(old('bank_transfer_enabled', $cfg->bank_transfer_enabled))
                               class="h-4 w-4 rounded border-slate-300 text-orange-600 focus:ring-orange-500">
                        <span class="text-xs font-bold text-slate-800 dark:text-slate-200">Enable Bank Transfer</span>
                    </label>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Bank Name</label>
                        <input type="text" name="bank_name" value="{{ old('bank_name', $cfg->bank_name) }}" placeholder="e.g. ABA Bank / ACLEDA"
                               class="w-full text-sm rounded-xl border border-slate-300 bg-white text-slate-900 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 focus:border-orange-500 focus:ring-orange-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Account Holder Name</label>
                        <input type="text" name="bank_account_holder" value="{{ old('bank_account_holder', $cfg->bank_account_holder) }}" placeholder="Exact account name"
                               class="w-full text-sm rounded-xl border border-slate-300 bg-white text-slate-900 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 focus:border-orange-500 focus:ring-orange-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Account Number</label>
                        <input type="text" name="bank_account_number" value="{{ old('bank_account_number', $cfg->bank_account_number) }}" placeholder="000 123 456"
                               class="w-full text-sm font-mono rounded-xl border border-slate-300 bg-white text-slate-900 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 focus:border-orange-500 focus:ring-orange-500">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Bank Transfer Instructions (Optional)</label>
                        <textarea name="bank_instructions" rows="2" placeholder="Transfer exact amount and upload screenshot below."
                                  class="w-full text-xs rounded-xl border border-slate-300 bg-white text-slate-900 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 focus:border-orange-500 focus:ring-orange-500">{{ old('bank_instructions', $cfg->bank_instructions) }}</textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Payment Reference Instructions (Optional)</label>
                        <textarea name="bank_reference_instructions" rows="2" placeholder="Include your Order Number in the bank transfer note."
                                  class="w-full text-xs rounded-xl border border-slate-300 bg-white text-slate-900 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 focus:border-orange-500 focus:ring-orange-500">{{ old('bank_reference_instructions', $cfg->bank_reference_instructions) }}</textarea>
                    </div>
                </div>
            </div>

            <!-- 3.2 Static QR Payment -->
            <div class="rounded-2xl border border-slate-200 dark:border-gray-700 p-5 space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <h4 class="text-sm font-black text-slate-900 dark:text-white">QR Payment (Static QR)</h4>
                            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold {{ $cfg->static_qr_enabled ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300' : 'bg-slate-100 text-slate-700 dark:bg-gray-700 dark:text-slate-300' }}">
                                {{ $cfg->static_qr_enabled ? 'Enabled' : 'Disabled' }}
                            </span>
                        </div>
                        <p class="text-xs text-slate-600 dark:text-slate-400 mt-0.5">Show a static QR code for manual payment confirmation.</p>
                    </div>
                    <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                        <input type="hidden" name="static_qr_enabled" value="0">
                        <input type="checkbox" name="static_qr_enabled" value="1"
                               @checked(old('static_qr_enabled', $cfg->static_qr_enabled))
                               class="h-4 w-4 rounded border-slate-300 text-orange-600 focus:ring-orange-500">
                        <span class="text-xs font-bold text-slate-800 dark:text-slate-200">Enable QR Payment</span>
                    </label>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">QR Payment Label</label>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mb-1.5">This label appears in checkout for the QR option.</p>
                        <input type="text" name="static_qr_label" value="{{ old('static_qr_label', $cfg->static_qr_label ?: 'QR Payment') }}" placeholder="e.g. KHQR Payment / Bank QR Payment"
                               class="w-full text-sm rounded-xl border border-slate-300 bg-white text-slate-900 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 focus:border-orange-500 focus:ring-orange-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">QR Image</label>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mb-1.5">Upload your payment QR image (PNG/JPG/WebP).</p>
                        <input type="file" name="static_qr_image" accept="image/png,image/jpeg,image/webp"
                               class="block w-full text-xs text-slate-700 dark:text-slate-300 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-[#0F1E3A] file:text-white hover:file:bg-[#192F59]">
                    </div>
                </div>

                @if($cfg->static_qr_image_path)
                    <div class="flex flex-wrap items-center gap-4 p-3 rounded-xl bg-slate-50 dark:bg-gray-900/50 border border-slate-200 dark:border-gray-700">
                        <img src="{{ $cfg->static_qr_image_path }}" alt="Configured Static QR" class="h-24 w-24 object-contain rounded-lg border border-slate-200 bg-white p-1">
                        <div class="space-y-1.5">
                            <p class="text-xs font-bold text-slate-800 dark:text-white">Current Static QR Image</p>
                            <label class="inline-flex items-center gap-1.5 text-xs text-rose-600 dark:text-rose-400 cursor-pointer font-semibold">
                                <input type="checkbox" name="remove_static_qr_image" value="1" class="rounded border-slate-300 text-rose-600">
                                Remove current QR image
                            </label>
                        </div>
                    </div>
                @endif

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-center">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Instructions (Optional)</label>
                        <input type="text" name="static_qr_instructions" value="{{ old('static_qr_instructions', $cfg->static_qr_instructions) }}" placeholder="Scan QR and attach receipt screenshot."
                               class="w-full text-xs rounded-xl border border-slate-300 bg-white text-slate-900 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 focus:border-orange-500 focus:ring-orange-500">
                    </div>

                    <label class="inline-flex items-center gap-2 pt-4 cursor-pointer select-none">
                        <input type="hidden" name="static_qr_require_proof" value="0">
                        <input type="checkbox" name="static_qr_require_proof" value="1"
                               @checked(old('static_qr_require_proof', $cfg->static_qr_require_proof))
                               class="h-4 w-4 rounded border-slate-300 text-orange-600 focus:ring-orange-500">
                        <span class="text-xs font-semibold text-slate-800 dark:text-slate-200">Prompt customers to upload proof after scanning</span>
                    </label>
                </div>
            </div>

            <!-- 3.3 Cash on Delivery & Cash at Counter -->
            <div class="rounded-2xl border border-slate-200 dark:border-gray-700 p-5 space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <h4 class="text-sm font-black text-slate-900 dark:text-white">Cash on Delivery &amp; In-Store Cash</h4>
                            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold {{ $cfg->cod_enabled ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300' : 'bg-slate-100 text-slate-700 dark:bg-gray-700 dark:text-slate-300' }}">
                                COD {{ $cfg->cod_enabled ? 'Enabled' : 'Disabled' }}
                            </span>
                        </div>
                        <p class="text-xs text-slate-600 dark:text-slate-400 mt-0.5">Allow customers to pay cash when they receive their order (starts as Pending Collection until rider/staff confirms).</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <label class="flex items-start gap-3 p-3.5 rounded-xl border border-slate-200 dark:border-gray-700 bg-slate-50/70 dark:bg-gray-900/40 cursor-pointer">
                        <input type="hidden" name="cod_enabled" value="0">
                        <input type="checkbox" name="cod_enabled" value="1"
                               @checked(old('cod_enabled', $cfg->cod_enabled))
                               class="mt-0.5 h-4 w-4 rounded border-slate-300 text-orange-600 focus:ring-orange-500">
                        <div>
                            <span class="block text-xs font-bold text-slate-900 dark:text-white">Enable Cash on Delivery (Delivery orders)</span>
                            <span class="block text-[11px] text-slate-600 dark:text-slate-400">Marked Pending Collection until staff records collection.</span>
                        </div>
                    </label>

                    <label class="flex items-start gap-3 p-3.5 rounded-xl border border-slate-200 dark:border-gray-700 bg-slate-50/70 dark:bg-gray-900/40 cursor-pointer">
                        <input type="hidden" name="cash_enabled" value="0">
                        <input type="checkbox" name="cash_enabled" value="1"
                               @checked(old('cash_enabled', $cfg->cash_enabled))
                               class="mt-0.5 h-4 w-4 rounded border-slate-300 text-orange-600 focus:ring-orange-500">
                        <div>
                            <span class="block text-xs font-bold text-slate-900 dark:text-white">Enable Cash at Counter (Dine-in &amp; Takeaway)</span>
                            <span class="block text-[11px] text-slate-600 dark:text-slate-400">Allow counter cash payment on store menu &amp; POS.</span>
                        </div>
                    </label>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Cash on Delivery Instructions (Optional)</label>
                    <input type="text" name="cod_instructions" value="{{ old('cod_instructions', $cfg->cod_instructions) }}" placeholder="e.g. Please prepare exact cash for driver."
                           class="w-full text-xs rounded-xl border border-slate-300 bg-white text-slate-900 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 focus:border-orange-500 focus:ring-orange-500">
                </div>
            </div>

            <!-- 3.4 ABA PayWay (Dynamic KHQR) -->
            <div class="rounded-2xl border border-slate-200 dark:border-gray-700 p-5 space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <img src="{{ asset('images/payments/aba.png') }}" alt="ABA PayWay" class="h-9 w-9 rounded-lg object-contain bg-white border border-slate-200 p-1">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h4 class="text-sm font-black text-slate-900 dark:text-white">ABA PayWay (Dynamic KHQR)</h4>
                                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold {{ $cfg->aba_payway_enabled ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300' : 'bg-slate-100 text-slate-700 dark:bg-gray-700 dark:text-slate-300' }}">
                                    {{ $cfg->aba_payway_enabled ? 'Enabled' : 'Disabled' }}
                                </span>
                                @php
                                    $abaStatusLabel = match ($cfg->aba_test_status) {
                                        'valid', 'connected' => 'API Connected',
                                        'link_only' => 'Link Mode Ready',
                                        'failed' => 'Connection Failed',
                                        default => $cfg->hasAbaMerchantApiCredentials() ? 'API Credentials Stored' : ($cfg->hasValidAbaPaywayLink() ? 'Link Configured' : 'Not Configured'),
                                    };
                                    $abaStatusClass = match ($cfg->aba_test_status) {
                                        'valid', 'connected' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300',
                                        'link_only' => 'bg-blue-100 text-blue-800 dark:bg-blue-950/50 dark:text-blue-300',
                                        'failed' => 'bg-rose-100 text-rose-800 dark:bg-rose-950/50 dark:text-rose-300',
                                        default => 'bg-amber-100 text-amber-800 dark:bg-amber-950/50 dark:text-amber-300',
                                    };
                                @endphp
                                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold {{ $abaStatusClass }}">
                                    {{ $abaStatusLabel }}
                                </span>
                            </div>
                            <p class="text-xs text-slate-600 dark:text-slate-400 mt-0.5">
                                Official ABA PayWay integration supporting Dynamic KHQR / server callback verification or merchant PayWay payment link.
                            </p>
                        </div>
                    </div>

                    <label class="inline-flex items-center gap-2 cursor-pointer select-none shrink-0">
                        <input type="hidden" name="aba_payway_enabled" value="0">
                        <input type="checkbox" name="aba_payway_enabled" value="1"
                               @checked(old('aba_payway_enabled', $cfg->aba_payway_enabled))
                               class="h-4 w-4 rounded border-slate-300 text-orange-600 focus:ring-orange-500">
                        <span class="text-xs font-bold text-slate-800 dark:text-slate-200">Enable ABA PayWay</span>
                    </label>
                </div>

                @if(!$platformAbaOn)
                    <div class="p-3 rounded-xl border border-amber-200 bg-amber-50 text-xs text-amber-900 dark:border-amber-800/50 dark:bg-amber-950/30 dark:text-amber-200 font-medium">
                        ABA PayWay checkout is currently paused platform-wide by Super Admin. Your configuration is preserved here.
                    </div>
                @endif

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">ABA PayWay Link</label>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mb-1.5">Leave empty to disable ABA PayWay. When set, customers will see Pay with ABA at checkout.</p>
                        <input type="url" id="aba_payway_link_input" name="aba_payway_link"
                               value="{{ old('aba_payway_link', $cfg->aba_payway_link) }}"
                               placeholder="https://link.payway.com.kh/..."
                               class="w-full text-sm rounded-xl border border-slate-300 bg-white text-slate-900 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 focus:border-orange-500 focus:ring-orange-500 font-mono">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Environment</label>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mb-1.5">Select Sandbox for testing or Production for live settlements.</p>
                        <select id="aba_environment_input" name="aba_environment"
                                class="w-full text-sm rounded-xl border border-slate-300 bg-white text-slate-900 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 focus:border-orange-500 focus:ring-orange-500 font-semibold">
                            <option value="sandbox" {{ old('aba_environment', $cfg->aba_environment ?: 'sandbox') === 'sandbox' ? 'selected' : '' }}>Sandbox (checkout-sandbox.payway.com.kh)</option>
                            <option value="production" {{ old('aba_environment', $cfg->aba_environment) === 'production' ? 'selected' : '' }}>Production (checkout.payway.com.kh)</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Official Merchant ID (for Dynamic KHQR API)</label>
                        <input type="text" id="aba_merchant_id_input" name="aba_merchant_id"
                               value="{{ old('aba_merchant_id', $cfg->aba_merchant_id) }}"
                               placeholder="e.g. ec000001"
                               class="w-full text-sm font-mono rounded-xl border border-slate-300 bg-white text-slate-900 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 focus:border-orange-500 focus:ring-orange-500">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">
                            Merchant API Key
                            <span class="font-normal text-[11px] text-slate-500 dark:text-slate-400">
                                ({{ $cfg->hasAbaMerchantApiCredentials() ? 'Encrypted key stored' : 'Not stored' }})
                            </span>
                        </label>
                        <input type="password" id="aba_api_key_input" name="aba_api_key" autocomplete="new-password"
                               placeholder="{{ $cfg->hasAbaMerchantApiCredentials() ? 'Leave blank to keep stored encrypted API key' : 'Paste official ABA PayWay API Key' }}"
                               class="w-full text-sm rounded-xl border border-slate-300 bg-white text-slate-900 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 focus:border-orange-500 focus:ring-orange-500">
                        @if($cfg->hasAbaMerchantApiCredentials())
                            <label class="inline-flex items-center gap-1.5 mt-1 text-[11px] text-rose-600 dark:text-rose-400 cursor-pointer">
                                <input type="checkbox" name="clear_aba_api_key" value="1" class="rounded border-slate-300 text-rose-600">
                                Clear stored ABA API key
                            </label>
                        @endif
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pt-2">
                    <div class="text-xs text-slate-600 dark:text-slate-300">
                        @if($cfg->aba_last_tested_at)
                            <span class="font-semibold">Last tested:</span> {{ $cfg->aba_last_tested_at->format('M d, Y h:i A') }}
                            @if($cfg->aba_test_message)
                                — <span class="text-slate-700 dark:text-slate-200">{{ $cfg->aba_test_message }}</span>
                            @endif
                        @else
                            Use Test Connection to verify Merchant API credentials or validate your PayWay Link.
                        @endif
                    </div>

                    <button type="button" @click="submitAbaTest()"
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl border border-slate-300 bg-white text-slate-800 hover:bg-slate-50 dark:border-gray-600 dark:bg-gray-900 dark:text-white dark:hover:bg-gray-700 text-xs font-bold cursor-pointer transition shrink-0">
                        Test ABA Connection
                    </button>
                </div>
            </div>

            <!-- 3.5 Bakong KHQR -->
            <div class="rounded-2xl border border-slate-200 dark:border-gray-700 p-5 space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <img src="{{ asset('images/payments/bakong.png') }}" alt="Bakong KHQR" class="h-9 w-9 rounded-lg object-contain bg-white border border-slate-200 p-1">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h4 class="text-sm font-black text-slate-900 dark:text-white">Bakong KHQR</h4>
                                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold {{ $cfg->bakong_enabled ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300' : 'bg-slate-100 text-slate-700 dark:bg-gray-700 dark:text-slate-300' }}">
                                    {{ $cfg->bakong_enabled ? 'Enabled' : 'Disabled' }}
                                </span>
                                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold {{ $cfg->bakong_verified ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300' : 'bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300' }}">
                                    {{ $cfg->bakong_verified ? 'Verified' : 'Not Verified' }}
                                </span>
                                @if($cfg->bakong_verified && $cfg->bakong_verified_at)
                                    <span class="text-[11px] text-slate-500 dark:text-slate-400">
                                        Verified {{ $cfg->bakong_verified_at->format('M d, Y h:i A') }}
                                    </span>
                                @endif
                            </div>
                            <p class="text-xs text-slate-600 dark:text-slate-400 mt-0.5">
                                Official National Bank of Cambodia Bakong KHQR integration. Only verified Bakong accounts are enabled at customer checkout.
                            </p>
                        </div>
                    </div>

                    <label class="inline-flex items-center gap-2 cursor-pointer select-none shrink-0">
                        <input type="hidden" name="bakong_enabled" value="0">
                        <input type="checkbox" name="bakong_enabled" value="1"
                               @checked(old('bakong_enabled', $cfg->bakong_enabled))
                               class="h-4 w-4 rounded border-slate-300 text-orange-600 focus:ring-orange-500">
                        <span class="text-xs font-bold text-slate-800 dark:text-slate-200">Enable Bakong KHQR</span>
                    </label>
                </div>

                @if(!$platformBakongOn)
                    <div class="p-3.5 rounded-xl border border-amber-300 bg-amber-50 text-xs text-amber-950 dark:border-amber-800/60 dark:bg-amber-950/40 dark:text-amber-200 font-semibold">
                        Bakong checkout is currently turned off platform-wide. Your setting here is saved and will apply again if Bakong is re-enabled.
                    </div>
                @elseif(!$bakongTokenReady)
                    <div class="p-3.5 rounded-xl border border-slate-200 bg-slate-50 text-xs text-slate-700 dark:border-gray-700 dark:bg-gray-900/50 dark:text-slate-300">
                        Platform NBC Bakong Open API access token (`BAKONG_OPEN_API_TOKEN` / Super Admin Platform Settings) is not configured yet.
                        Account verification will stay <strong>Not Verified</strong> and Bakong checkout remains inactive until verified with NBC Bakong Open API.
                    </div>
                @endif

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-end">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">Bakong Account ID</label>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mb-1.5">Format: name@bank (e.g. sokpheng@aclb, dara@acleda).</p>
                        <div class="flex gap-2">
                            <input type="text" id="bakong_account_id_input" name="bakong_account_id"
                                   value="{{ old('bakong_account_id', $cfg->bakong_account_id) }}"
                                   placeholder="e.g. sokpheng@aclb"
                                   class="flex-1 text-sm font-mono rounded-xl border border-slate-300 bg-white text-slate-900 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 focus:border-orange-500 focus:ring-orange-500">
                            <button type="button" @click="submitBakongVerify()"
                                    class="px-4 py-2 rounded-xl bg-[#0F1E3A] hover:bg-[#192F59] text-white text-xs font-bold transition cursor-pointer shrink-0">
                                Verify
                            </button>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1">KHQR Display Merchant Name</label>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400 mb-1.5">Merchant name shown inside banking apps.</p>
                        <input type="text" id="bakong_merchant_name_input" name="bakong_merchant_name"
                               value="{{ old('bakong_merchant_name', $cfg->bakong_merchant_name ?: $business->name) }}"
                               placeholder="Store Name"
                               class="w-full text-sm rounded-xl border border-slate-300 bg-white text-slate-900 dark:border-gray-700 dark:bg-gray-900 dark:text-white px-3 py-2 focus:border-orange-500 focus:ring-orange-500">
                    </div>
                </div>

                @if($cfg->bakong_verification_message)
                    <p class="text-xs {{ $cfg->bakong_verified ? 'text-emerald-700 dark:text-emerald-300' : 'text-rose-700 dark:text-rose-300' }} font-medium">
                        {{ $cfg->bakong_verification_message }}
                    </p>
                @endif
            </div>
        </div>

        <div class="flex items-center justify-end">
            <button type="submit"
                    class="px-7 py-3 bg-[#0F1E3A] hover:bg-[#192F59] text-white font-bold text-sm rounded-xl shadow-sm transition cursor-pointer">
                Save All Payment &amp; Tax Settings
            </button>
        </div>
    </form>

    <!-- Helper POST forms (kept outside main form to keep HTML valid without nested forms) -->
    <form id="bakong-verify-post" method="POST" action="{{ route('business.settings.payment.verify-bakong') }}" class="hidden">
        @csrf
        <input type="hidden" name="bakong_account_id" id="hidden_bakong_account_id">
        <input type="hidden" name="bakong_merchant_name" id="hidden_bakong_merchant_name">
    </form>

    <form id="aba-test-post" method="POST" action="{{ route('business.settings.payment.test-aba') }}" class="hidden">
        @csrf
        <input type="hidden" name="aba_payway_link" id="hidden_aba_payway_link">
        <input type="hidden" name="aba_environment" id="hidden_aba_environment">
        <input type="hidden" name="aba_merchant_id" id="hidden_aba_merchant_id">
        <input type="hidden" name="aba_api_key" id="hidden_aba_api_key">
    </form>
</div>

<script>
    function paymentTaxSettingsApp() {
        return {
            taxName: @json(old('tax_name', $business->tax_name ?: 'VAT')),
            taxRate: {{ old('tax_percent', (float) ($business->tax_percent ?? 0)) }},
            taxInclusive: {{ old('tax_inclusive', $business->tax_inclusive ? true : false) ? 'true' : 'false' }},
            serviceChargeEnabled: {{ old('service_charge_enabled', $business->service_charge_enabled ? true : false) ? 'true' : 'false' }},
            serviceChargeRate: {{ old('service_charge_rate', (float) ($business->service_charge_rate ?? 0)) }},

            sampleServiceCharge() {
                if (!this.serviceChargeEnabled || Number(this.serviceChargeRate) <= 0) {
                    return 0;
                }
                return Math.round((10 * (Number(this.serviceChargeRate) / 100)) * 100) / 100;
            },

            sampleTaxAmount() {
                const rate = Number(this.taxRate || 0);
                if (rate <= 0) {
                    return 0;
                }
                if (this.taxInclusive) {
                    return Math.round((10 * (rate / (100 + rate))) * 100) / 100;
                }
                return Math.round((10 * (rate / 100)) * 100) / 100;
            },

            sampleGrandTotal() {
                const svc = this.sampleServiceCharge();
                if (this.taxInclusive) {
                    return Math.round((10 + svc) * 100) / 100;
                }
                return Math.round((10 + svc + this.sampleTaxAmount()) * 100) / 100;
            },

            submitBakongVerify() {
                document.getElementById('hidden_bakong_account_id').value = document.getElementById('bakong_account_id_input')?.value || '';
                document.getElementById('hidden_bakong_merchant_name').value = document.getElementById('bakong_merchant_name_input')?.value || '';
                document.getElementById('bakong-verify-post').submit();
            },

            submitAbaTest() {
                document.getElementById('hidden_aba_payway_link').value = document.getElementById('aba_payway_link_input')?.value || '';
                document.getElementById('hidden_aba_environment').value = document.getElementById('aba_environment_input')?.value || 'sandbox';
                document.getElementById('hidden_aba_merchant_id').value = document.getElementById('aba_merchant_id_input')?.value || '';
                document.getElementById('hidden_aba_api_key').value = document.getElementById('aba_api_key_input')?.value || '';
                document.getElementById('aba-test-post').submit();
            },
        };
    }
</script>
