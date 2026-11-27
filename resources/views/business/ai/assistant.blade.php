<x-business-layout title="AI Business Assistant" heading="AI Center — Business Assistant">
    @php
        $initialMessages = $messages->map(static fn ($m): array => [
            'id' => $m->id,
            'role' => $m->role,
            'content' => $m->content,
            'provider' => $m->provider,
            'model' => $m->model,
            'data_sources' => $m->data_sources_json ?? [],
            'created_at' => $m->created_at?->diffForHumans(),
        ])->values()->all();

        $quickPrompts = [
            ['label' => 'លក់សប្តាហ៍នេះ (7d Sales)', 'prompt' => 'តើសប្តាហ៍នេះហាងយើងលក់បានប៉ុន្មាន និងប្រៀបធៀបជាមួយសប្តាហ៍មុនយ៉ាងណាដែរ?'],
            ['label' => 'ទំនិញជិតអស់ស្តុក (Low Stock)', 'prompt' => 'តើមានទំនិញ ឬគ្រឿងផ្សំណាខ្លះដែលជិតអស់ស្តុកនៅថ្ងៃនេះ?'],
            ['label' => 'Top Sellers (30d)', 'prompt' => 'What are my top-selling products over the last 30 days and how much revenue did they generate?'],
            ['label' => 'Slow Movers (30d)', 'prompt' => 'Which active menu items are selling slowly or not at all over the last 30 days?'],
            ['label' => 'Profit & Expenses', 'prompt' => 'Summarize my revenue, recorded expenses, and net operating cash flow for the past 30 days.'],
            ['label' => 'Peak Hours & KHQR', 'prompt' => 'What are our busiest hours of the day, and how do KHQR/ABA digital payments compare to cash?'],
        ];
    @endphp

    <div class="max-w-7xl mx-auto space-y-6" x-data="sabayraAssistantComponent()">
        @include('business.ai._nav')

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Left Sidebar: Conversations & Quick Prompts -->
            <div class="lg:col-span-4 space-y-4">
                <div class="rounded-2xl bg-white border border-slate-200 p-4 shadow-xs dark:bg-gray-800 dark:border-gray-700/60 space-y-3">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900 dark:text-white">Conversations</h3>
                        <a href="{{ route('business.ai.assistant', ['conversation' => 'new']) }}"
                           class="rounded-xl bg-orange-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-orange-700 transition">
                            + New Chat
                        </a>
                    </div>

                    <div class="space-y-1.5 max-h-64 overflow-y-auto">
                        @forelse($conversations as $conv)
                            <div class="flex items-center justify-between gap-2 rounded-xl px-3 py-2 text-xs transition {{ ($activeConversation?->id === $conv->id) ? 'bg-[#0F1E3A] text-white font-bold' : 'bg-slate-50 text-slate-800 hover:bg-slate-100 dark:bg-gray-900/60 dark:text-gray-200 dark:hover:bg-gray-900' }}">
                                <a href="{{ route('business.ai.assistant', ['conversation' => $conv->id]) }}" class="truncate flex-1">
                                    <span class="block truncate">{{ $conv->title }}</span>
                                    <span class="text-[10px] opacity-75">{{ $conv->last_message_at?->diffForHumans() }}</span>
                                </a>
                                <form method="POST" action="{{ route('business.ai.assistant.destroy', $conv) }}" onsubmit="return confirm('Delete this conversation?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-[11px] opacity-60 hover:opacity-100 hover:text-rose-400 px-1 cursor-pointer" title="Delete chat">&times;</button>
                                </form>
                            </div>
                        @empty
                            <p class="text-xs text-slate-500 dark:text-gray-400 py-3 text-center">
                                Start your first Khmer or English question below.
                            </p>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-2xl bg-white border border-slate-200 p-4 shadow-xs dark:bg-gray-800 dark:border-gray-700/60 space-y-3">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900 dark:text-white">Quick Business Prompts</h3>
                    <div class="flex flex-wrap gap-2">
                        @foreach($quickPrompts as $qp)
                            <button type="button"
                                    @click="usePrompt({{ \Illuminate\Support\Js::from($qp['prompt']) }})"
                                    class="text-left rounded-xl border border-slate-200 bg-slate-50 px-3 py-1.5 text-[11px] font-semibold text-slate-800 hover:border-orange-400 hover:bg-orange-50 dark:border-gray-700 dark:bg-gray-900/60 dark:text-gray-200 dark:hover:border-orange-500/60 dark:hover:bg-gray-900 transition cursor-pointer">
                                {{ $qp['label'] }}
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Right Panel: Live Conversation Stream & Composer -->
            <div class="lg:col-span-8 rounded-2xl bg-white border border-slate-200 shadow-xs dark:bg-gray-800 dark:border-gray-700/60 flex flex-col min-h-[540px]">
                <div class="px-5 py-3.5 border-b border-slate-100 dark:border-gray-700/60 flex items-center justify-between gap-3">
                    <div>
                        <p class="text-sm font-bold text-slate-900 dark:text-white">
                            {{ $activeConversation?->title ?? 'New Business Assistant Session' }}
                        </p>
                        <p class="text-[11px] text-slate-500 dark:text-gray-400">
                            Verified read-only access to orders, products, stock, expenses &amp; payments · Language: {{ strtoupper($featureSettings->preferred_language ?? 'auto') }}
                        </p>
                    </div>
                    <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                        <span x-text="remainingCredits">{{ $monthlySummary['remaining_credits'] }}</span> credits left
                    </span>
                </div>

                <!-- Messages Feed -->
                <div id="assistant-chat-feed" class="flex-1 p-5 space-y-4 overflow-y-auto max-h-[520px]">
                    <template x-if="messages.length === 0">
                        <div class="rounded-2xl border border-dashed border-slate-200 dark:border-gray-700 p-8 text-center space-y-2">
                            <p class="text-sm font-bold text-slate-900 dark:text-white">Ask Anything About {{ $business->name }}</p>
                            <p class="text-xs text-slate-500 dark:text-gray-400 max-w-md mx-auto">
                                Ask in ភាសាខ្មែរ (Khmer), English, or mixed Khmer-English. Every metric is queried strictly from your store's live PostgreSQL records.
                            </p>
                        </div>
                    </template>

                    <template x-for="msg in messages" :key="msg.id">
                        <div :class="msg.role === 'user' ? 'flex justify-end' : 'flex justify-start'">
                            <div :class="msg.role === 'user'
                                ? 'max-w-[85%] rounded-2xl bg-[#0F1E3A] text-white px-4 py-3 text-sm shadow-xs'
                                : 'max-w-[90%] rounded-2xl border border-slate-200 bg-slate-50 text-slate-900 px-4 py-3 text-sm dark:border-gray-700 dark:bg-gray-900/80 dark:text-gray-100'">
                                <div class="flex items-center gap-2 mb-1 text-[10px] font-bold uppercase tracking-wider opacity-75">
                                    <span x-text="msg.role === 'user' ? 'You' : '✨ SABAYRA AI'"></span>
                                    <template x-if="msg.role === 'assistant' && msg.model">
                                        <span x-text="'· ' + msg.model"></span>
                                    </template>
                                </div>
                                <div class="whitespace-pre-line leading-relaxed" x-text="msg.content"></div>

                                <template x-if="msg.role === 'assistant' && Array.isArray(msg.data_sources) && msg.data_sources.length > 0">
                                    <div class="mt-2.5 pt-2 border-t border-slate-200/80 dark:border-gray-700 flex flex-wrap items-center gap-1.5">
                                        <span class="text-[10px] font-bold text-slate-500 dark:text-gray-400">Verified tools:</span>
                                        <template x-for="(src, idx) in msg.data_sources" :key="idx">
                                            <span class="px-2 py-0.5 rounded-md bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300 text-[10px] font-mono"
                                                  x-text="src.tool"></span>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>

                    <div x-show="loading" x-cloak class="flex justify-start">
                        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs font-semibold text-amber-900 dark:border-amber-700/50 dark:bg-amber-950/30 dark:text-amber-200 animate-pulse">
                            Checking live store metrics &amp; preparing answer...
                        </div>
                    </div>
                </div>

                <!-- Error Feedback -->
                <div x-show="errorMessage" x-cloak class="mx-5 mb-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-xs font-medium text-rose-800 dark:border-rose-800/60 dark:bg-rose-950/30 dark:text-rose-200" x-text="errorMessage"></div>

                <!-- Composer -->
                <form @submit.prevent="sendMessage()" class="p-4 border-t border-slate-100 dark:border-gray-700/60 bg-slate-50/70 dark:bg-gray-900/40 rounded-b-2xl">
                    <div class="flex flex-col sm:flex-row gap-2.5">
                        <textarea x-model="draft" rows="2" maxlength="2000"
                                  @keydown.enter.prevent="if(!$event.shiftKey) sendMessage()"
                                  placeholder="សួរជាភាសាខ្មែរ ឬ English... e.g. What sold best this week?"
                                  class="flex-1 rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 focus:border-amber-500 focus:ring-amber-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"></textarea>
                        <button type="submit" :disabled="loading || draft.trim() === ''"
                                class="shrink-0 rounded-xl bg-orange-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-orange-700 transition cursor-pointer disabled:opacity-50">
                            <span x-text="loading ? 'Thinking...' : 'Ask AI'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function sabayraAssistantComponent() {
            return {
                conversationId: {{ \Illuminate\Support\Js::from($activeConversation?->id) }},
                messages: {{ \Illuminate\Support\Js::from($initialMessages) }},
                remainingCredits: {{ (int) ($monthlySummary['remaining_credits'] ?? 0) }},
                draft: '',
                loading: false,
                errorMessage: '',
                usePrompt(text) {
                    this.draft = text;
                },
                async sendMessage() {
                    const clean = this.draft.trim();
                    if (!clean || this.loading) return;

                    this.loading = true;
                    this.errorMessage = '';
                    const requestUuid = (window.crypto && window.crypto.randomUUID)
                        ? window.crypto.randomUUID()
                        : ('req_' + Date.now() + '_' + Math.random().toString(16).slice(2));

                    try {
                        const res = await fetch({{ \Illuminate\Support\Js::from(route('business.ai.assistant.ask')) }}, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                            },
                            body: JSON.stringify({
                                message: clean,
                                conversation_id: this.conversationId,
                                request_uuid: requestUuid,
                            }),
                        });
                        const data = await res.json();
                        if (data.monthly_summary && typeof data.monthly_summary.remaining_credits === 'number') {
                            this.remainingCredits = data.monthly_summary.remaining_credits;
                        }
                        if (!res.ok || !data.ok) {
                            this.errorMessage = data.message || 'Unable to process AI Business Assistant request.';
                            return;
                        }
                        this.conversationId = data.conversation.id;
                        this.messages.push(data.user_message);
                        this.messages.push(data.assistant_message);
                        this.draft = '';
                        this.$nextTick(() => {
                            const feed = document.getElementById('assistant-chat-feed');
                            if (feed) feed.scrollTop = feed.scrollHeight;
                        });
                    } catch (err) {
                        this.errorMessage = 'Network error communicating with AI Business Assistant.';
                    } finally {
                        this.loading = false;
                    }
                },
            };
        }
    </script>
</x-business-layout>
