<x-business-layout title="Customer Reviews" heading="Customer Reviews & Ratings">
<div class="space-y-6">

    @if (session('status'))
        <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-xs font-bold text-emerald-800 dark:text-emerald-200">
            {{ session('status') }}
        </div>
    @endif

    <!-- Review Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
        <div class="p-5 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-2xl shadow-xs">
            <span class="text-xs font-bold text-slate-400 uppercase">Average Rating</span>
            <p class="text-2xl font-black text-amber-500 mt-1">{{ $stats['average_rating'] }} / 5.0 ★</p>
        </div>
        <div class="p-5 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-2xl shadow-xs">
            <span class="text-xs font-bold text-slate-400 uppercase">Total Reviews</span>
            <p class="text-2xl font-black text-gray-900 dark:text-white mt-1">{{ $stats['total_reviews'] }}</p>
        </div>
        <div class="p-5 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-2xl shadow-xs">
            <span class="text-xs font-bold text-slate-400 uppercase">5-Star Feedback</span>
            <p class="text-2xl font-black text-emerald-600 mt-1">{{ $stats['five_star_count'] }}</p>
        </div>
        <div class="p-5 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-2xl shadow-xs">
            <span class="text-xs font-bold text-slate-400 uppercase">Pending Reply</span>
            <p class="text-2xl font-black text-blue-500 mt-1">{{ $stats['pending_responses'] }}</p>
        </div>
    </div>

    <!-- Reviews Listing -->
    <div class="space-y-4">
        @forelse ($reviews as $rev)
            <div class="p-6 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-xs space-y-3">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="font-bold text-gray-900 dark:text-white text-sm">{{ $rev->customer_name }}</span>
                        <span class="text-xs text-amber-500 font-bold ml-2">{{ str_repeat('★', $rev->rating) }}{{ str_repeat('☆', 5 - $rev->rating) }}</span>
                    </div>
                    <span class="text-[10px] text-slate-400">{{ $rev->created_at->diffForHumans() }}</span>
                </div>

                <p class="text-xs text-slate-700 dark:text-slate-300 leading-relaxed">{{ $rev->comment }}</p>

                <!-- Merchant Response Section -->
                @if ($rev->merchant_response)
                    <div class="p-3.5 rounded-xl bg-slate-50 dark:bg-gray-900/60 border border-gray-100 dark:border-gray-700/40 text-xs">
                        <span class="font-bold text-orange-500 text-[10px] uppercase">Your Response &bull; {{ $rev->responded_at ? $rev->responded_at->diffForHumans() : '' }}</span>
                        <p class="text-slate-600 dark:text-slate-300 mt-1">{{ $rev->merchant_response }}</p>
                    </div>
                @else
                    <form method="POST" action="{{ route('business.reviews.respond', $rev) }}" class="pt-2 flex gap-2">
                        @csrf
                        <input type="text" name="merchant_response" placeholder="Reply to customer publicly..." required class="flex-1 rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-3 py-1.5 text-xs text-gray-900 dark:text-white" />
                        <button type="submit" class="px-4 py-1.5 bg-orange-600 hover:bg-orange-500 text-white rounded-xl text-xs font-bold shadow-xs">
                            Reply
                        </button>
                    </form>
                @endif
            </div>
        @empty
            <div class="p-12 text-center text-xs text-slate-400 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700/60">
                No customer reviews yet. Guests can leave ratings directly on your digital storefront!
            </div>
        @endforelse
    </div>

</div>
</x-business-layout>
