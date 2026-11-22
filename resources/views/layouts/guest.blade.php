<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'SABAYRA Cambodia') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}" />

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Kantumruy+Pro:wght@400;500;600;700&family=Battambang:wght@400;700&display=swap" rel="stylesheet">

    <!-- Flaticon UIcons CDN -->
    <link rel="stylesheet" href="https://cdn-uicons.flaticon.com/2.6.0/uicons-regular-rounded/css/uicons-regular-rounded.css">

    <!-- Scripts & Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }
        body {
            font-family: 'Inter', 'Kantumruy Pro', 'Battambang', sans-serif;
            background: radial-gradient(circle at 50% 12%, #201a45 0%, #110e28 50%, #080616 100%);
            min-height: 100vh;
        }
        /* Top glow orb */
        .ambient-glow {
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 700px;
            height: 400px;
            background: radial-gradient(circle, rgba(249,115,22,0.18) 0%, rgba(99,102,241,0.12) 50%, transparent 70%);
            filter: blur(80px);
            pointer-events: none;
            z-index: 0;
        }
    </style>
</head>
<body class="antialiased text-slate-100 flex flex-col justify-between min-h-screen relative selection:bg-orange-500 selection:text-white">

    <!-- Ambient background light -->
    <div class="ambient-glow"></div>

    <!-- Top Navigation Bar for Auth -->
    <header class="relative z-10 max-w-6xl w-full mx-auto px-4 sm:px-6 py-6 flex items-center justify-between">
        <!-- Back to Home -->
        <a href="{{ route('home') }}" class="inline-flex items-center gap-2 text-xs font-semibold text-slate-300 hover:text-white transition px-3 py-1.5 rounded-lg bg-white/5 hover:bg-white/10 border border-white/10 backdrop-blur-md">
            <svg class="w-4 h-4 text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            <span>Back to Home</span>
        </a>

        <!-- Language Switcher Pill (similar to readmenu top-right) -->
        <div class="relative" x-data="{ open: false }">
            <button type="button" @click="open = !open" class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-white/5 hover:bg-white/10 border border-white/10 text-xs font-semibold text-slate-200 backdrop-blur-md transition">
                <span class="w-4 h-3 overflow-hidden rounded-xs inline-flex items-center">
                    @if(app()->getLocale() === 'km')
                        <img src="{{ asset('images/flags/kh.svg') }}" alt="Khmer" class="w-full h-full object-cover">
                    @else
                        <img src="{{ asset('images/flags/us.svg') }}" alt="English" class="w-full h-full object-cover">
                    @endif
                </span>
                <span class="uppercase font-bold tracking-wider">{{ app()->getLocale() }}</span>
                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            <div x-show="open" @click.outside="open = false" x-cloak class="absolute right-0 mt-2 w-36 bg-slate-900 border border-slate-800 rounded-xl shadow-2xl py-1 text-xs z-50">
                <a href="{{ route('locale.switch', 'en') }}" class="flex items-center justify-between px-3 py-2 text-slate-300 hover:bg-slate-800 hover:text-white transition {{ app()->getLocale() === 'en' ? 'text-orange-400 font-bold bg-slate-800/50' : '' }}">
                    <span class="flex items-center gap-2">
                        <img src="{{ asset('images/flags/us.svg') }}" alt="English" class="w-4 h-3 rounded-xs object-cover">
                        <span>English</span>
                    </span>
                    @if(app()->getLocale() === 'en')
                        <svg class="w-3.5 h-3.5 text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    @endif
                </a>
                <a href="{{ route('locale.switch', 'km') }}" class="flex items-center justify-between px-3 py-2 text-slate-300 hover:bg-slate-800 hover:text-white transition {{ app()->getLocale() === 'km' ? 'text-orange-400 font-bold bg-slate-800/50' : '' }}">
                    <span class="flex items-center gap-2">
                        <img src="{{ asset('images/flags/kh.svg') }}" alt="Khmer" class="w-4 h-3 rounded-xs object-cover">
                        <span class="font-khmer">ភាសាខ្មែរ</span>
                    </span>
                    @if(app()->getLocale() === 'km')
                        <svg class="w-3.5 h-3.5 text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    @endif
                </a>
            </div>
        </div>
    </header>

    <!-- Centered Card Container -->
    <main class="relative z-10 flex-1 flex items-center justify-center px-4 py-8 sm:py-12">
        <div class="w-full max-w-[440px]">
            {{ $slot }}
        </div>
    </main>

    <!-- Auth Footer -->
    <footer class="relative z-10 py-6 text-center text-xs text-slate-500">
        <p>&copy; {{ date('Y') }} SABAYRA Cambodia. All rights reserved.</p>
    </footer>

</body>
</html>
