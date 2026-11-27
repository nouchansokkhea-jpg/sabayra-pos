<x-business-layout :title="$moduleTitle" :heading="'AI Center — ' . $moduleTitle">
    <div class="max-w-5xl mx-auto space-y-6">
        @include('business.ai._nav')

        <div class="rounded-2xl bg-white border border-slate-200 p-8 text-center shadow-xs dark:bg-gray-800 dark:border-gray-700/60 space-y-4">
            <div class="mx-auto w-12 h-12 rounded-2xl bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300 flex items-center justify-center text-xl font-black">
                ✨
            </div>
            <h3 class="text-lg font-extrabold text-slate-900 dark:text-white">{{ $moduleTitle }}</h3>
            <p class="text-sm text-slate-600 dark:text-gray-300 max-w-xl mx-auto">{{ $moduleSubtitle }}</p>
            <div class="pt-2">
                <a href="{{ route('business.ai.overview') }}"
                   class="inline-flex items-center rounded-xl bg-[#0F1E3A] px-4 py-2 text-xs font-bold text-white hover:bg-[#192F59] transition">
                    &larr; Back to AI Overview
                </a>
            </div>
        </div>
    </div>
</x-business-layout>
