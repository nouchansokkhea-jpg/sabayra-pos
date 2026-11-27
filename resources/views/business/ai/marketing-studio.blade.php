<x-business-layout title="AI Marketing Studio" heading="AI Center — Marketing Studio">
    <div class="max-w-7xl mx-auto space-y-6" x-data="sabayraMarketingComponent()">
        @include('business.ai._nav')

        @if(session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 dark:border-emerald-800/60 dark:bg-emerald-950/30 dark:text-emerald-300">
                {{ session('success') }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Left Campaign Builder -->
            <div class="lg:col-span-5 rounded-2xl bg-white border border-slate-200 p-5 sm:p-6 shadow-xs dark:bg-gray-800 dark:border-gray-700/60 space-y-4">
                <div class="pb-3 border-b border-slate-100 dark:border-gray-700/60">
                    <h3 class="text-sm font-bold uppercase tracking-wider text-slate-900 dark:text-white">Cambodian Campaign Builder</h3>
                    <p class="text-xs text-slate-500 dark:text-gray-400 mt-0.5">Craft localized Facebook posts, TikTok scripts, and Telegram offers in seconds.</p>
                </div>

                <div class="grid grid-cols-2 gap-2.5">
                    @foreach([
                        ['id' => 'facebook', 'label' => 'Facebook Page'],
                        ['id' => 'tiktok', 'label' => 'TikTok / Reels Script'],
                        ['id' => 'telegram', 'label' => 'Telegram Promo'],
                        ['id' => 'general', 'label' => 'In-Store / Flyer Copy'],
                    ] as $ch)
                        <button type="button" @click="form.channel = '{{ $ch['id'] }}'"
                                :class="form.channel === '{{ $ch['id'] }}' ? 'bg-[#0F1E3A] text-white border-[#0F1E3A]' : 'bg-slate-50 text-slate-800 border-slate-200 dark:bg-gray-900/60 dark:text-gray-200 dark:border-gray-700'"
                                class="rounded-xl border px-3 py-2.5 text-xs font-bold transition cursor-pointer text-center">
                            {{ $ch['label'] }}
                        </button>
                    @endforeach
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-gray-300 mb-1.5">Campaign Focus</label>
                    <select x-model="form.campaign_type"
                            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                        <option value="product_spotlight">Signature Product Spotlight</option>
                        <option value="combo_promo">Meal / Drink Combo Promotion</option>
                        <option value="holiday_khmer">Khmer Holiday (Khmer New Year / Pchum Ben / Water Festival)</option>
                        <option value="flash_discount">Weekend / Rainy-Day Flash Offer</option>
                        <option value="retention">Customer Loyalty &amp; Repeat Visit</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-gray-300 mb-1.5">Featured Product (Optional)</label>
                    <select x-model="form.product_id"
                            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                        <option value="">-- Whole Store / General Promo --</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}">
                                {{ $product->name_en }} (${{ number_format((float) $product->effective_price, 2) }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-gray-300 mb-1.5">Output Language</label>
                        <select x-model="form.target_language"
                                class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs text-slate-900 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                            <option value="bilingual">Bilingual (KH + EN)</option>
                            <option value="km">ភាសាខ្មែរ (Khmer Only)</option>
                            <option value="en">English</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-gray-300 mb-1.5">Brand Voice</label>
                        <select x-model="form.tone"
                                class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs text-slate-900 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                            <option value="friendly">Friendly &amp; Local</option>
                            <option value="energetic">Energetic Promo</option>
                            <option value="premium">Upscale Hospitality</option>
                            <option value="concise">Short &amp; Punchy</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-gray-300 mb-1.5">Occasion / Theme (Optional)</label>
                    <input type="text" x-model="form.occasion" placeholder="e.g. Khmer New Year Family Treat / Payday Friday"
                           class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-gray-300 mb-1.5">Offer / Discount Details (Optional)</label>
                    <textarea x-model="form.offer_details" rows="2" placeholder="e.g. Buy 2 Iced Lattes get 1 Free Croissant from 2PM–5PM. Accept Bakong KHQR."
                              class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 dark:border-gray-700 dark:bg-gray-900 dark:text-white"></textarea>
                </div>

                <div x-show="errorMessage" x-cloak class="rounded-xl border border-rose-200 bg-rose-50 px-3.5 py-2.5 text-xs text-rose-800 dark:border-rose-800/60 dark:bg-rose-950/30 dark:text-rose-200" x-text="errorMessage"></div>

                <button type="button" @click="generateCampaign()" :disabled="loading"
                        class="w-full rounded-xl bg-orange-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-orange-700 transition cursor-pointer disabled:opacity-50">
                    <span x-text="loading ? 'Generating Campaign...' : '✨ Generate Marketing Copy (1 Credit)'"></span>
                </button>
            </div>

            <!-- Right Campaign Output & History -->
            <div class="lg:col-span-7 space-y-5">
                <div class="rounded-2xl bg-white border border-slate-200 p-5 sm:p-6 shadow-xs dark:bg-gray-800 dark:border-gray-700/60 space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-bold uppercase tracking-wider text-slate-900 dark:text-white">Generated Campaign Draft</h3>
                        <span x-show="copiedNotice" x-cloak class="text-xs font-bold text-emerald-600 dark:text-emerald-400" x-text="copiedNotice"></span>
                    </div>

                    <template x-if="!campaign">
                        <div class="rounded-xl border border-dashed border-slate-200 dark:border-gray-700 p-8 text-center text-xs text-slate-500 dark:text-gray-400">
                            Select a channel (Facebook, TikTok, or Telegram) and click Generate to preview localized Khmer &amp; English marketing posts.
                        </div>
                    </template>

                    <template x-if="campaign">
                        <div class="space-y-4">
                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-gray-700 dark:bg-gray-900/60 space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-[11px] font-bold uppercase tracking-wider text-orange-600 dark:text-orange-400">Khmer Copy (ភាសាខ្មែរ)</span>
                                    <button type="button" @click="copyText((campaign.structured.headline_km || '') + '\n\n' + (campaign.structured.post_body_km || ''), 'Copied Khmer post!')"
                                            class="text-[11px] font-bold text-[#0F1E3A] dark:text-amber-300 hover:underline cursor-pointer">Copy Khmer</button>
                                </div>
                                <p class="text-sm font-bold text-slate-900 dark:text-white" x-text="campaign.structured.headline_km"></p>
                                <p class="text-xs text-slate-700 dark:text-gray-200 whitespace-pre-line leading-relaxed" x-text="campaign.structured.post_body_km"></p>
                            </div>

                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-gray-700 dark:bg-gray-900/60 space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-gray-400">English Copy</span>
                                    <button type="button" @click="copyText((campaign.structured.headline_en || '') + '\n\n' + (campaign.structured.post_body_en || ''), 'Copied English post!')"
                                            class="text-[11px] font-bold text-[#0F1E3A] dark:text-amber-300 hover:underline cursor-pointer">Copy English</button>
                                </div>
                                <p class="text-sm font-bold text-slate-900 dark:text-white" x-text="campaign.structured.headline_en"></p>
                                <p class="text-xs text-slate-700 dark:text-gray-200 whitespace-pre-line leading-relaxed" x-text="campaign.structured.post_body_en"></p>
                            </div>

                            <div x-show="campaign.structured.video_script" class="rounded-xl border border-amber-200 bg-amber-50/70 p-4 dark:border-amber-700/50 dark:bg-amber-950/30 space-y-1.5">
                                <p class="text-[11px] font-bold uppercase tracking-wider text-amber-800 dark:text-amber-300">Video / TikTok Reel Script &amp; Visuals</p>
                                <p class="text-xs text-slate-800 dark:text-gray-100 whitespace-pre-line leading-relaxed" x-text="campaign.structured.video_script"></p>
                            </div>

                            <div class="flex flex-wrap items-center justify-between gap-2 text-xs pt-2 border-t border-slate-100 dark:border-gray-700/60">
                                <p class="text-slate-600 dark:text-gray-300">
                                    <strong>CTA:</strong> <span x-text="campaign.structured.cta_text"></span> ·
                                    <strong>Schedule:</strong> <span x-text="campaign.structured.suggested_promo_schedule"></span>
                                </p>
                                <p class="font-mono text-[11px] text-orange-600 dark:text-orange-400"
                                   x-text="Array.isArray(campaign.structured.hashtags) ? campaign.structured.hashtags.join(' ') : ''"></p>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Saved Drafts -->
                <div class="rounded-2xl bg-white border border-slate-200 p-5 shadow-xs dark:bg-gray-800 dark:border-gray-700/60">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900 dark:text-white mb-3">Saved Marketing Drafts</h3>
                    @forelse($recentCampaigns as $camp)
                        <div class="py-2.5 border-b last:border-b-0 border-slate-100 dark:border-gray-700/60 flex items-center justify-between text-xs gap-3">
                            <div class="min-w-0">
                                <p class="font-bold text-slate-900 dark:text-white truncate">
                                    [{{ strtoupper($camp->platform) }}] {{ $camp->title }}
                                </p>
                                <p class="text-[11px] text-slate-500 dark:text-gray-400 truncate">{{ $camp->structured_output['headline_km'] ?? $camp->output_text }}</p>
                            </div>
                            <form method="POST" action="{{ route('business.ai.marketing.destroy', $camp) }}" onsubmit="return confirm('Delete this marketing draft?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-[11px] font-bold text-rose-600 hover:underline cursor-pointer">Delete</button>
                            </form>
                        </div>
                    @empty
                        <p class="text-xs text-slate-500 dark:text-gray-400">No marketing drafts generated yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <script>
        function sabayraMarketingComponent() {
            return {
                form: {
                    channel: 'facebook',
                    campaign_type: 'product_spotlight',
                    product_id: '',
                    occasion: '',
                    offer_details: '',
                    target_language: 'bilingual',
                    tone: {{ \Illuminate\Support\Js::from($featureSettings->preferred_tone ?? 'friendly') }},
                },
                loading: false,
                errorMessage: '',
                copiedNotice: '',
                campaign: null,
                async generateCampaign() {
                    if (this.loading) return;
                    this.loading = true;
                    this.errorMessage = '';
                    const requestUuid = (window.crypto && window.crypto.randomUUID)
                        ? window.crypto.randomUUID()
                        : ('mkt_' + Date.now() + '_' + Math.random().toString(16).slice(2));

                    try {
                        const res = await fetch({{ \Illuminate\Support\Js::from(route('business.ai.marketing.generate')) }}, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                            },
                            body: JSON.stringify({ ...this.form, request_uuid: requestUuid }),
                        });
                        const data = await res.json();
                        if (!res.ok || !data.ok) {
                            this.errorMessage = data.message || 'Unable to generate marketing draft.';
                            return;
                        }
                        this.campaign = data.campaign;
                    } catch (err) {
                        this.errorMessage = 'Network error generating marketing draft.';
                    } finally {
                        this.loading = false;
                    }
                },
                async copyText(text, label) {
                    try {
                        await navigator.clipboard.writeText(text);
                        this.copiedNotice = label;
                        setTimeout(() => { this.copiedNotice = ''; }, 2500);
                    } catch (e) {}
                },
            };
        }
    </script>
</x-business-layout>
