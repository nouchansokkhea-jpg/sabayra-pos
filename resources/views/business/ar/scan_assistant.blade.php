<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold tracking-tight text-zinc-900 dark:text-zinc-100">
                    {{ __('3D Scan Assistant & Model Assets') }}
                </h2>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
                    {{ __('Manage verified 3D assets (GLB / GLTF / USDZ) associated with dishes and products for interactive 360° previews.') }}
                </p>
            </div>
            <a href="{{ route('business.ar.cards') }}" class="text-sm font-semibold text-purple-600 hover:text-purple-500">
                &larr; {{ __('Back to AR Cards') }}
            </a>
        </div>
    </x-slot>

    <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        @if (session('status'))
            <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 dark:bg-emerald-950/30 dark:border-emerald-800 dark:text-emerald-300">
                {{ session('status') }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left: Register 3D Asset -->
            <div class="lg:col-span-1">
                <div class="bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700/60 rounded-2xl p-6 shadow-sm">
                    <h3 class="text-base font-bold text-zinc-900 dark:text-zinc-100 mb-3 flex items-center gap-2">
                        <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                        {{ __('Attach 3D Model') }}
                    </h3>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-4">
                        {{ __('Provide a hosted URL to a standard 3D file (.glb, .gltf, or .usdz). Only real, accessible 3D models will render.') }}
                    </p>

                    <form method="POST" action="{{ route('business.ar.store-asset') }}" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-500 mb-1">{{ __('Asset Name') }}</label>
                            <input type="text" name="title" required placeholder="e.g. Signature Beef Lok Lak 3D" class="w-full text-sm rounded-xl border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900 text-zinc-800 dark:text-zinc-200">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-500 mb-1">{{ __('Attach to Product') }}</label>
                            <select name="product_id" class="w-full text-sm rounded-xl border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900 text-zinc-800 dark:text-zinc-200">
                                <option value="">{{ __('-- None / General Asset --') }}</option>
                                @foreach ($products as $p)
                                    <option value="{{ $p->id }}">{{ $p->name }} (${{ number_format($p->price, 2) }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-500 mb-1">{{ __('Format') }}</label>
                            <select name="format" class="w-full text-sm rounded-xl border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900 text-zinc-800 dark:text-zinc-200">
                                <option value="glb">GLB (Binary glTF - Web & Android)</option>
                                <option value="gltf">glTF (JSON text)</option>
                                <option value="usdz">USDZ (Apple QuickLook)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-zinc-500 mb-1">{{ __('3D Asset Hosted URL') }}</label>
                            <input type="url" name="asset_url" required placeholder="https://storage.../model.glb" class="w-full text-sm rounded-xl border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900 text-zinc-800 dark:text-zinc-200">
                        </div>

                        <button type="submit" class="w-full py-2.5 px-4 bg-purple-600 hover:bg-purple-700 text-white font-medium rounded-xl text-sm shadow-sm transition">
                            {{ __('Save 3D Model Asset') }}
                        </button>
                    </form>
                </div>
            </div>

            <!-- Right: Registered 3D Assets List -->
            <div class="lg:col-span-2">
                <div class="bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700/60 rounded-2xl p-6 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-base font-bold text-zinc-900 dark:text-zinc-100">{{ __('Configured 3D Models') }}</h3>
                        <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300">
                            {{ $assets->count() }} {{ __('Assets') }}
                        </span>
                    </div>

                    @if ($assets->isEmpty())
                        <div class="py-12 text-center text-zinc-500 dark:text-zinc-400">
                            <svg class="w-12 h-12 mx-auto text-zinc-300 dark:text-zinc-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M14 10l-2 1m0 0l-2-1m2 1v2.5M20 7l-2 1m2-1l-2-1m2 1v2.5M14 4l-2-1-2 1M4 7l2-1M4 7l2 1M4 7v2.5M12 21l-2-1m2 1l2-1m-2 1v-2.5M6 18l-2-1v-2.5M18 18l2-1v-2.5"/></svg>
                            <p class="text-sm font-medium">{{ __('No 3D assets registered yet.') }}</p>
                            <p class="text-xs text-zinc-400 mt-1">{{ __('Upload your GLB or USDZ photogrammetry scans to provide 3D menu previews to your customers.') }}</p>
                        </div>
                    @else
                        <div class="space-y-3">
                            @foreach ($assets as $asset)
                                <div class="p-4 rounded-xl border border-zinc-200 dark:border-zinc-700 flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-lg bg-purple-100 dark:bg-purple-950/50 flex items-center justify-center text-purple-600 font-bold text-xs uppercase">
                                            {{ $asset->format }}
                                        </div>
                                        <div>
                                            <h4 class="text-sm font-bold text-zinc-900 dark:text-zinc-100">{{ $asset->title }}</h4>
                                            <p class="text-xs text-zinc-500">
                                                {{ $asset->product ? 'Product: ' . $asset->product->name : 'Unassigned Asset' }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <a href="{{ $asset->asset_url }}" target="_blank" class="px-3 py-1.5 text-xs font-medium text-purple-600 hover:text-purple-500 border border-purple-200 dark:border-purple-800 rounded-lg">
                                            {{ __('Inspect Asset') }} &rarr;
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
