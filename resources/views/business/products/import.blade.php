<x-business-layout title="Bulk Product Import" heading="Bulk Product Import">
<div class="space-y-6">

    @if (session('status'))
        <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-xs font-bold text-emerald-800 dark:text-emerald-200">
            {{ session('status') }}
        </div>
    @endif

    <div class="p-5 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-2xl shadow-xs">
        <h3 class="text-sm font-bold text-gray-900 dark:text-white">Import Products via CSV Spreadsheet</h3>
        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
            Easily upload hundreds of products, barcoded retail items, or supermarket groceries in one single file.
        </p>
    </div>

    <!-- Step 1: Download Template -->
    <div class="p-5 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-2xl shadow-xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider">Step 1: Download Official CSV Template</h4>
            <p class="text-xs text-slate-600 dark:text-slate-300 mt-1">Contains properly formatted column headers for bilingual names, categories, prices, SKUs, and barcodes.</p>
        </div>
        <a href="{{ route('business.products.import.template') }}" class="inline-flex items-center px-4 py-2.5 bg-slate-800 hover:bg-slate-700 text-white rounded-xl text-xs font-bold border border-slate-700 transition">
            <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
            Download CSV Template
        </a>
    </div>

    <!-- Step 2: Upload CSV -->
    <div class="p-6 bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 rounded-2xl shadow-xs">
        <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-4">Step 2: Upload Your Filled CSV File</h4>

        <form method="POST" action="{{ route('business.products.import.store') }}" enctype="multipart/form-data">
            @csrf

            <div class="p-8 border-2 border-dashed border-gray-300 dark:border-gray-700 rounded-2xl text-center">
                <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <div class="mt-4 flex justify-center text-xs text-slate-600 dark:text-slate-300">
                    <label for="csv_file" class="relative cursor-pointer rounded-md font-bold text-orange-500 hover:text-orange-400 focus-within:outline-hidden">
                        <span>Upload a CSV file</span>
                        <input id="csv_file" name="csv_file" type="file" accept=".csv,text/csv" class="sr-only" required>
                    </label>
                    <p class="pl-1">or drag and drop</p>
                </div>
                <p class="text-[11px] text-slate-400 mt-1">Up to 5MB (thousands of catalog rows supported)</p>
            </div>

            <div class="mt-6 flex justify-end">
                <button type="submit" class="px-6 py-2.5 bg-orange-600 hover:bg-orange-500 text-white rounded-xl text-xs font-bold shadow-xs transition cursor-pointer">
                    Validate & Start Bulk Import
                </button>
            </div>
        </form>
    </div>

</div>
</x-business-layout>
