<x-business-layout title="Sentiment Radar" heading="Customer Sentiment Radar">
<div class="space-y-6">

    <div class="p-5 bg-gradient-to-r from-cyan-950/40 via-gray-900 to-blue-950/30 border border-cyan-500/30 rounded-2xl shadow-xs text-white">
        <div class="flex items-center space-x-3">
            <span class="text-3xl">📡</span>
            <div>
                <h3 class="text-base font-black text-white">Verified Customer Sentiment Radar</h3>
                <p class="text-xs text-slate-300 mt-1">Real guest review analytics detecting service velocity, food taste compliments, and recurring operational complaints.</p>
            </div>
        </div>
    </div>

    @if ($sentiment['has_data'])
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            <!-- Compliments -->
            <div class="p-6 bg-white dark:bg-gray-800 border border-emerald-200 dark:border-emerald-900/40 rounded-2xl shadow-xs">
                <h4 class="text-xs font-bold text-emerald-600 uppercase tracking-wider mb-4 flex items-center gap-1.5">
                    <span>👍</span> Common Guest Compliments
                </h4>
                <ul class="space-y-2 text-xs text-slate-700 dark:text-slate-200">
                    @forelse ($sentiment['common_compliments'] as $comp)
                        <li class="p-2.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/30 flex items-center gap-2">
                            <span class="text-emerald-500 font-bold">&check;</span>
                            <span>{{ $comp }}</span>
                        </li>
                    @empty
                        <li class="text-slate-400">No recurring compliments logged yet.</li>
                    @endforelse
                </ul>
            </div>

            <!-- Complaints / Areas to Monitor -->
            <div class="p-6 bg-white dark:bg-gray-800 border border-red-200 dark:border-red-900/40 rounded-2xl shadow-xs">
                <h4 class="text-xs font-bold text-red-600 uppercase tracking-wider mb-4 flex items-center gap-1.5">
                    <span>⚠️</span> Areas to Monitor & Improve
                </h4>
                <ul class="space-y-2 text-xs text-slate-700 dark:text-slate-200">
                    @forelse ($sentiment['common_complaints'] as $comp)
                        <li class="p-2.5 rounded-xl bg-red-50 dark:bg-red-950/30 flex items-center gap-2">
                            <span class="text-red-500 font-bold">&excl;</span>
                            <span>{{ $comp }}</span>
                        </li>
                    @empty
                        <li class="text-slate-400">Zero recurring complaints detected across verified guest reviews!</li>
                    @endforelse
                </ul>
            </div>

        </div>
    @else
        <div class="p-12 text-center text-xs text-slate-400 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700/60">
            <p class="font-bold text-slate-700 dark:text-slate-200 text-sm">No Customer Reviews Logged Yet</p>
            <p class="mt-2 max-w-md mx-auto">SABAYRA never fabricates customer opinions. Once diners scan your QR menu and submit star ratings, verified sentiment metrics will display here.</p>
        </div>
    @endif

</div>
</x-business-layout>
