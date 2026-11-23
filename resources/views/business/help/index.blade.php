<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                    {{ __('Help Center & Support') }}
                </h2>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                    {{ __('Frequently asked questions, localized Cambodia support, and direct ticket submission.') }}
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a href="https://t.me/sabayra_support" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold bg-sky-50 text-sky-700 dark:bg-sky-950/40 dark:text-sky-300 rounded-xl border border-sky-200 dark:border-sky-800 hover:bg-sky-100 transition">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.37.75-.56 2.93-1.28 4.88-2.12 5.86-2.54 2.79-1.16 3.37-1.36 3.75-1.36.08 0 .28.02.4.12.1.08.13.19.14.27-.01.07.01.24 0 .31z"/></svg>
                    {{ __('Telegram Hotline') }}
                </a>
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
            <!-- Left: FAQs -->
            <div class="lg:col-span-2 space-y-4">
                <div class="bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700/60 rounded-2xl p-6 shadow-sm">
                    <h3 class="text-lg font-bold text-zinc-900 dark:text-zinc-100 mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        {{ __('Frequently Asked Questions') }}
                    </h3>

                    <div class="space-y-4" x-data="{ openFaq: null }">
                        <!-- FAQ 1 -->
                        <div class="border border-zinc-200 dark:border-zinc-700 rounded-xl p-4">
                            <button @click="openFaq = (openFaq === 1 ? null : 1)" class="w-full flex items-center justify-between text-left font-semibold text-sm text-zinc-900 dark:text-zinc-100">
                                <span>{{ __('How does Bakong KHQR dynamic payment work?') }}</span>
                                <svg class="w-4 h-4 transform transition-transform" :class="openFaq === 1 ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="openFaq === 1" x-collapse class="mt-2 text-xs text-zinc-600 dark:text-zinc-400 leading-relaxed">
                                {{ __('When a customer places an order or checks out via POS, SABAYRA generates a standard Bakong KHQR payload containing your merchant Bakong ID, order ID, and exact amount in either USD or KHR. Customers scan with ABA, Wing, ACLEDA, or any Bakong banking app.') }}
                            </div>
                        </div>

                        <!-- FAQ 2 -->
                        <div class="border border-zinc-200 dark:border-zinc-700 rounded-xl p-4">
                            <button @click="openFaq = (openFaq === 2 ? null : 2)" class="w-full flex items-center justify-between text-left font-semibold text-sm text-zinc-900 dark:text-zinc-100">
                                <span>{{ __('Can I connect thermal receipt printers?') }}</span>
                                <svg class="w-4 h-4 transform transition-transform" :class="openFaq === 2 ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="openFaq === 2" x-collapse class="mt-2 text-xs text-zinc-600 dark:text-zinc-400 leading-relaxed">
                                {{ __('Yes! Under Operations > Printer Management, you can configure standard ESC/POS 58mm and 80mm network (LAN/WiFi), USB, and Bluetooth receipt printers for both cashier receipts and kitchen order slips.') }}
                            </div>
                        </div>

                        <!-- FAQ 3 -->
                        <div class="border border-zinc-200 dark:border-zinc-700 rounded-xl p-4">
                            <button @click="openFaq = (openFaq === 3 ? null : 3)" class="w-full flex items-center justify-between text-left font-semibold text-sm text-zinc-900 dark:text-zinc-100">
                                <span>{{ __('How do ingredient deductions work for restaurants?') }}</span>
                                <svg class="w-4 h-4 transform transition-transform" :class="openFaq === 3 ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="openFaq === 3" x-collapse class="mt-2 text-xs text-zinc-600 dark:text-zinc-400 leading-relaxed">
                                {{ __('Under Management > Recipes & Ingredients, associate inventory items with dishes (e.g. 200g beef, 1 cup rice). When the kitchen marks an order completed, stock counts automatically deduct and trigger low-stock alerts when thresholds are reached.') }}
                            </div>
                        </div>

                        <!-- FAQ 4 -->
                        <div class="border border-zinc-200 dark:border-zinc-700 rounded-xl p-4">
                            <button @click="openFaq = (openFaq === 4 ? null : 4)" class="w-full flex items-center justify-between text-left font-semibold text-sm text-zinc-900 dark:text-zinc-100">
                                <span>{{ __('Are there transaction fees or limits on products?') }}</span>
                                <svg class="w-4 h-4 transform transition-transform" :class="openFaq === 4 ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="openFaq === 4" x-collapse class="mt-2 text-xs text-zinc-600 dark:text-zinc-400 leading-relaxed">
                                {{ __('Currently all SABAYRA merchant tools, POS terminals, and digital menu features are completely free with unlimited products, orders, and branches.') }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Existing Support Tickets -->
                <div class="bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700/60 rounded-2xl p-6 shadow-sm">
                    <h3 class="text-base font-bold text-zinc-900 dark:text-zinc-100 mb-3">{{ __('Your Support Inquiries') }}</h3>
                    @if ($tickets->isEmpty())
                        <p class="text-xs text-zinc-500">{{ __('You have not submitted any support tickets yet.') }}</p>
                    @else
                        <div class="space-y-3">
                            @foreach ($tickets as $t)
                                <div class="p-3 rounded-xl border border-zinc-200 dark:border-zinc-700 flex items-center justify-between">
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-bold text-zinc-900 dark:text-zinc-100">{{ $t->subject }}</span>
                                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full {{ $t->status === 'resolved' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                                {{ ucfirst($t->status) }}
                                            </span>
                                        </div>
                                        <p class="text-[11px] text-zinc-400 mt-0.5">{{ $t->created_at->diffForHumans() }} • Category: {{ ucfirst($t->category) }}</p>
                                    </div>
                                    <span class="text-xs text-zinc-400 font-mono">#{{ substr($t->id, 0, 8) }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <!-- Right: Submit Support Ticket Form -->
            <div class="lg:col-span-1">
                <div class="bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700/60 rounded-2xl p-6 shadow-sm">
                    <h3 class="text-base font-bold text-zinc-900 dark:text-zinc-100 mb-3 flex items-center gap-2">
                        <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        {{ __('Submit In-App Ticket') }}
                    </h3>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-4">
                        {{ __('Our technical support team based in Phnom Penh responds within 2-4 business hours.') }}
                    </p>

                    <form method="POST" action="{{ route('business.help.store-ticket') }}" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-500 mb-1">{{ __('Subject') }}</label>
                            <input type="text" name="subject" required placeholder="e.g. Printer connection question" class="w-full text-sm rounded-xl border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900 text-zinc-800 dark:text-zinc-200">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-500 mb-1">{{ __('Category') }}</label>
                            <select name="category" class="w-full text-sm rounded-xl border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900 text-zinc-800 dark:text-zinc-200">
                                <option value="technical">{{ __('Technical / Hardware') }}</option>
                                <option value="billing">{{ __('Billing / Account') }}</option>
                                <option value="general">{{ __('General Question') }}</option>
                                <option value="feature_request">{{ __('Feature Request') }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-500 mb-1">{{ __('Message') }}</label>
                            <textarea name="message" rows="4" required placeholder="Describe what you need assistance with..." class="w-full text-sm rounded-xl border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900 text-zinc-800 dark:text-zinc-200"></textarea>
                        </div>

                        <button type="submit" class="w-full py-2.5 px-4 bg-orange-600 hover:bg-orange-700 text-white font-medium rounded-xl text-sm shadow-sm transition">
                            {{ __('Send Ticket') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
