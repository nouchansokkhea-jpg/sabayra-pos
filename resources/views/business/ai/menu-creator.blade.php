<x-business-layout title="AI Menu Creator" heading="AI Center — Menu Creator & Khmer Translator">
    @php
        $productsJs = $products->map(static fn ($p): array => [
            'id' => $p->id,
            'name_en' => $p->name_en,
            'name_km' => $p->name_km ?? '',
            'category' => $p->category?->name_en ?? '',
            'price_usd' => (float) $p->base_price,
            'has_khmer' => filled($p->name_km) && filled($p->description_km),
        ])->values()->all();
    @endphp

    <div class="max-w-7xl mx-auto space-y-6" x-data="sabayraMenuCreatorComponent()">
        @include('business.ai._nav')

        @if(session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800 dark:border-emerald-800/60 dark:bg-emerald-950/30 dark:text-emerald-300">
                {{ session('success') }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Left Form -->
            <div class="lg:col-span-5 rounded-2xl bg-white border border-slate-200 p-5 sm:p-6 shadow-xs dark:bg-gray-800 dark:border-gray-700/60 space-y-4">
                <div class="flex items-center justify-between gap-2 pb-3 border-b border-slate-100 dark:border-gray-700/60">
                    <div>
                        <h3 class="text-sm font-bold uppercase tracking-wider text-slate-900 dark:text-white">Menu Copy &amp; Combo Studio</h3>
                        <p class="text-xs text-slate-500 dark:text-gray-400 mt-0.5">Generate bilingual Khmer &amp; English product copy or bundle ideas.</p>
                    </div>
                    <a href="{{ route('business.ai.menu-builder') }}" class="text-[11px] font-bold text-orange-600 dark:text-orange-400 hover:underline shrink-0">
                        Bulk Menu Importer &rarr;
                    </a>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-gray-300 mb-1.5">Generation Mode</label>
                    <select x-model="form.action"
                            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                        <option value="description">Write New Bilingual Description (KH + EN)</option>
                        <option value="improve">Improve Existing Item Copy</option>
                        <option value="translate">Translate Menu Item (Khmer &harr; English)</option>
                        <option value="combos_and_pricing">Suggest Combos, Bundles &amp; Pricing</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-gray-300 mb-1.5">Link Existing Product (Optional)</label>
                    <select x-model="form.product_id" @change="prefillFromProduct()"
                            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                        <option value="">-- Enter new item manually --</option>
                        @foreach($products as $product)
                            <option value="{{ $product->id }}">
                                {{ $product->name_en }} (${{ number_format((float) $product->base_price, 2) }}) {{ filled($product->description_km) ? '' : '[Missing Khmer]' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3" x-show="form.action !== 'combos_and_pricing'">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-gray-300 mb-1.5">Item Name (EN)</label>
                        <input type="text" x-model="form.item_name" placeholder="e.g. Kampot Pepper Beef Lok Lak"
                               class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-gray-300 mb-1.5">ឈ្មោះមុខទំនិញ (KH)</label>
                        <input type="text" x-model="form.item_name_km" placeholder="ឧ. ឡុកឡាក់សាច់គោម្រេចកំពត"
                               class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-gray-300 mb-1.5">Language</label>
                        <select x-model="form.target_language"
                                class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs text-slate-900 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                            <option value="bilingual">Bilingual (KH + EN)</option>
                            <option value="km">Khmer First</option>
                            <option value="en">English First</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-gray-300 mb-1.5">Tone</label>
                        <select x-model="form.tone"
                                class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs text-slate-900 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                            <option value="friendly">Friendly</option>
                            <option value="premium">Premium</option>
                            <option value="energetic">Energetic</option>
                            <option value="concise">Short &amp; Direct</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-gray-300 mb-1.5">Price (USD)</label>
                        <input type="number" step="0.01" min="0" x-model="form.price_usd"
                               class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs text-slate-900 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-gray-300 mb-1.5">Ingredients / Highlights / Goal</label>
                    <textarea x-model="form.notes" rows="3" placeholder="e.g. Fresh roasted Cambodian coffee beans, condensed milk, served iced; highlight crispy baguette pairing."
                              class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 dark:border-gray-700 dark:bg-gray-900 dark:text-white"></textarea>
                </div>

                <div x-show="errorMessage" x-cloak class="rounded-xl border border-rose-200 bg-rose-50 px-3.5 py-2.5 text-xs text-rose-800 dark:border-rose-800/60 dark:bg-rose-950/30 dark:text-rose-200" x-text="errorMessage"></div>

                <button type="button" @click="generateMenuCopy()" :disabled="loading"
                        class="w-full rounded-xl bg-orange-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-orange-700 transition cursor-pointer disabled:opacity-50">
                    <span x-text="loading ? 'Generating Khmer & English Copy...' : '✨ Generate Menu Copy (1 Credit)'"></span>
                </button>
            </div>

            <!-- Right Live Preview & Apply Panel -->
            <div class="lg:col-span-7 space-y-5">
                <div class="rounded-2xl bg-white border border-slate-200 p-5 sm:p-6 shadow-xs dark:bg-gray-800 dark:border-gray-700/60 space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-bold uppercase tracking-wider text-slate-900 dark:text-white">AI-Generated Preview</h3>
                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                            Requires Review Before Applying
                        </span>
                    </div>

                    <template x-if="!result">
                        <div class="rounded-xl border border-dashed border-slate-200 dark:border-gray-700 p-8 text-center text-xs text-slate-500 dark:text-gray-400">
                            Choose an item or mode on the left and click Generate. You can review every Khmer &amp; English sentence before saving it to your menu.
                        </div>
                    </template>

                    <template x-if="result">
                        <div class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-gray-700 dark:bg-gray-900/60 space-y-2">
                                    <p class="text-[11px] font-bold uppercase tracking-wider text-orange-600 dark:text-orange-400">ភាសាខ្មែរ (Khmer Output)</p>
                                    <p class="text-sm font-bold text-slate-900 dark:text-white" x-text="result.structured.name_km || '—'"></p>
                                    <p class="text-xs text-slate-700 dark:text-gray-200 leading-relaxed whitespace-pre-line" x-text="result.structured.description_km || ''"></p>
                                </div>

                                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-gray-700 dark:bg-gray-900/60 space-y-2">
                                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 dark:text-gray-400">English Output</p>
                                    <p class="text-sm font-bold text-slate-900 dark:text-white" x-text="result.structured.name_en || '—'"></p>
                                    <p class="text-xs text-slate-700 dark:text-gray-200 leading-relaxed whitespace-pre-line" x-text="result.structured.description_en || ''"></p>
                                </div>
                            </div>

                            <div x-show="result.structured.marketing_hook" class="rounded-xl border border-amber-200 bg-amber-50/70 px-4 py-3 text-xs text-amber-900 dark:border-amber-700/50 dark:bg-amber-950/30 dark:text-amber-200">
                                <strong>Menu Highlight Tip:</strong> <span x-text="result.structured.marketing_hook"></span>
                            </div>

                            <template x-if="Array.isArray(result.structured.suggestions) && result.structured.suggestions.length > 0">
                                <div class="space-y-2">
                                    <p class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-gray-300">Combo &amp; Pricing Recommendations</p>
                                    <template x-for="(sug, index) in result.structured.suggestions" :key="index">
                                        <div class="rounded-xl border border-slate-200 p-3 dark:border-gray-700 text-xs flex items-start justify-between gap-3">
                                            <div>
                                                <p class="font-bold text-slate-900 dark:text-white" x-text="sug.title + (sug.title_km ? ' / ' + sug.title_km : '')"></p>
                                                <p class="text-slate-600 dark:text-gray-300 mt-0.5" x-text="sug.rationale"></p>
                                            </div>
                                            <span class="shrink-0 font-black text-emerald-600 dark:text-emerald-400" x-text="'$' + Number(sug.suggested_price_usd || 0).toFixed(2)"></span>
                                        </div>
                                    </template>
                                </div>
                            </template>

                            @if($products->isNotEmpty())
                                <form method="POST" :action="result.apply_url" class="rounded-xl border border-slate-200 bg-slate-50/80 p-4 dark:border-gray-700 dark:bg-gray-900/50 space-y-3">
                                    @csrf
                                    <p class="text-xs font-bold uppercase tracking-wider text-slate-800 dark:text-gray-200">Apply Draft to Store Product</p>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <select name="product_id" x-model="applyProductId" required
                                                class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs text-slate-900 dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                                            @foreach($products as $product)
                                                <option value="{{ $product->id }}">{{ $product->name_en }}</option>
                                            @endforeach
                                        </select>
                                        <button type="submit"
                                                class="rounded-xl bg-[#0F1E3A] px-4 py-2 text-xs font-bold text-white hover:bg-[#192F59] transition cursor-pointer">
                                            Apply Selected Fields to Product
                                        </button>
                                    </div>
                                    <div class="flex flex-wrap gap-4 text-xs text-slate-700 dark:text-gray-300">
                                        <label class="inline-flex items-center gap-1.5"><input type="checkbox" name="apply_name_km" value="1" checked class="rounded text-orange-600"> Update Khmer Name</label>
                                        <label class="inline-flex items-center gap-1.5"><input type="checkbox" name="apply_description_km" value="1" checked class="rounded text-orange-600"> Update Khmer Description</label>
                                        <label class="inline-flex items-center gap-1.5"><input type="checkbox" name="apply_description_en" value="1" checked class="rounded text-orange-600"> Update English Description</label>
                                        <label class="inline-flex items-center gap-1.5"><input type="checkbox" name="apply_name_en" value="1" class="rounded text-orange-600"> Update English Name</label>
                                    </div>
                                </form>
                            @endif
                        </div>
                    </template>
                </div>

                <!-- Recent Drafts -->
                <div class="rounded-2xl bg-white border border-slate-200 p-5 shadow-xs dark:bg-gray-800 dark:border-gray-700/60">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900 dark:text-white mb-3">Recent Menu Drafts</h3>
                    @forelse($recentDrafts as $draft)
                        <div class="py-2.5 border-b last:border-b-0 border-slate-100 dark:border-gray-700/60 flex items-center justify-between text-xs gap-2">
                            <div class="min-w-0">
                                <p class="font-bold text-slate-900 dark:text-white truncate">{{ $draft->title }} · {{ ucwords(str_replace('_', ' ', $draft->content_type)) }}</p>
                                <p class="text-[11px] text-slate-500 dark:text-gray-400 truncate">{{ $draft->structured_output['description_km'] ?? $draft->output_text }}</p>
                            </div>
                            <span class="shrink-0 px-2 py-0.5 rounded-full text-[10px] font-bold {{ $draft->status === 'applied' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300' : 'bg-slate-100 text-slate-700 dark:bg-gray-700 dark:text-gray-300' }}">
                                {{ ucfirst($draft->status) }}
                            </span>
                        </div>
                    @empty
                        <p class="text-xs text-slate-500 dark:text-gray-400">No saved menu drafts yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <script>
        window.sabayraMenuProducts = {{ \Illuminate\Support\Js::from($productsJs) }};
        function sabayraMenuCreatorComponent() {
            return {
                form: {
                    action: 'description',
                    product_id: '',
                    item_name: '',
                    item_name_km: '',
                    category_name: '',
                    price_usd: '',
                    notes: '',
                    target_language: 'bilingual',
                    tone: {{ \Illuminate\Support\Js::from($featureSettings->preferred_tone ?? 'friendly') }},
                },
                loading: false,
                errorMessage: '',
                result: null,
                applyProductId: {{ \Illuminate\Support\Js::from($products->first()?->id ?? '') }},
                prefillFromProduct() {
                    const found = window.sabayraMenuProducts.find((p) => p.id === this.form.product_id);
                    if (!found) return;
                    this.form.item_name = found.name_en;
                    this.form.item_name_km = found.name_km || '';
                    this.form.category_name = found.category || '';
                    this.form.price_usd = found.price_usd;
                    this.applyProductId = found.id;
                },
                async generateMenuCopy() {
                    if (this.loading) return;
                    this.loading = true;
                    this.errorMessage = '';
                    const requestUuid = (window.crypto && window.crypto.randomUUID)
                        ? window.crypto.randomUUID()
                        : ('menu_' + Date.now() + '_' + Math.random().toString(16).slice(2));

                    try {
                        const res = await fetch({{ \Illuminate\Support\Js::from(route('business.ai.menu-creator.generate')) }}, {
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
                            this.errorMessage = data.message || 'Unable to generate menu copy right now.';
                            return;
                        }
                        this.result = data.content;
                        if (data.content.product_id) {
                            this.applyProductId = data.content.product_id;
                        }
                    } catch (err) {
                        this.errorMessage = 'Network error generating menu content.';
                    } finally {
                        this.loading = false;
                    }
                },
            };
        }
    </script>
</x-business-layout>
