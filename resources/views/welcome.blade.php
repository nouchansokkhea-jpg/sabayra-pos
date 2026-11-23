<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SABAYRA Cambodia | Smarter Business, Simpler Every Day.</title>
    <meta name="description" content="The complete Multi-Tenant SaaS Digital Menu, QR Ordering, Touch POS, Kitchen Screen, and Business Management Platform built for Cambodia.">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}" />
    <!-- Flaticon UIcons CDN for sleek modern icons -->
    <link rel="stylesheet" href="https://cdn-uicons.flaticon.com/2.6.0/uicons-regular-rounded/css/uicons-regular-rounded.css">
    <link rel="stylesheet" href="https://cdn-uicons.flaticon.com/2.6.0/uicons-bold-rounded/css/uicons-bold-rounded.css">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700|kantumruy-pro:400,500,600,700" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        /* Cambodian Bank Payment Marquee */
        .payments-marquee {
            overflow: hidden;
            position: relative;
            width: 100%;
            mask-image: linear-gradient(to right, transparent, black 8%, black 92%, transparent);
            -webkit-mask-image: linear-gradient(to right, transparent, black 8%, black 92%, transparent);
        }
        .payments-track {
            display: flex;
            gap: 14px;
            width: max-content;
            animation: marqueeScroll 36s linear infinite;
        }
        .payments-marquee:hover .payments-track {
            animation-play-state: paused;
        }
        @keyframes marqueeScroll {
            0% { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }
        .dark-pay-chip {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 18px;
            background: rgba(15, 23, 42, 0.75);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 14px;
            backdrop-filter: blur(12px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            font-size: 13px;
            font-weight: 700;
            color: #cbd5e1;
            white-space: nowrap;
            transition: all 0.25s ease;
            user-select: none;
        }
        .dark-pay-chip:hover {
            transform: translateY(-2px);
            border-color: rgba(249, 115, 22, 0.4);
            color: #ffffff;
            box-shadow: 0 8px 24px rgba(249, 115, 22, 0.15);
        }
        .dark-pay-chip img {
            width: 22px;
            height: 22px;
            object-fit: contain;
            border-radius: 4px;
        }

        body { font-family: 'Instrument Sans', 'Kantumruy Pro', sans-serif; }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 antialiased selection:bg-orange-500 selection:text-white"
      x-data="{
        billingAnnual: false,
        activePreviewTab: 'menu',
        exchangeRate: 4100
      }">

    <!-- Top Glow Effect -->
    <div class="absolute top-0 left-1/2 -translate-x-1/2 w-full max-w-7xl h-96 bg-gradient-to-b from-orange-600/15 via-orange-500/5 to-transparent blur-3xl pointer-events-none -z-10"></div>

    <!-- Navigation Bar -->
    <header class="sticky top-0 z-50 backdrop-blur-xl bg-slate-950/80 border-b border-slate-800/80 transition">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-20 flex items-center justify-between">
            <!-- Brand Logo -->
            <a href="{{ route('home') }}" class="flex items-center space-x-3 group">
                <img src="{{ asset('images/sabayra-icon.png') }}" alt="SABAYRA" class="w-10 h-10 rounded-xl object-contain shadow-lg shadow-orange-600/30 group-hover:scale-105 transition" />
                <div>
                    <div class="flex items-center space-x-1.5">
                        <span class="text-xl font-black tracking-tight text-white">SABAYRA</span>
                        <span class="text-xs font-bold text-orange-400 bg-orange-500/10 px-1.5 py-0.5 rounded border border-orange-500/20">CAMBODIA</span>
                    </div>
                    <p class="text-[10px] text-slate-400 tracking-wider uppercase font-semibold">Smarter Business, Simpler Every Day.</p>
                </div>
            </a>

            <!-- Desktop Nav Links -->
            <nav class="hidden md:flex items-center space-x-8 text-sm font-semibold text-slate-300">
                <a href="#features" class="hover:text-orange-400 transition">Features</a>
                <a href="#businesses" class="hover:text-orange-400 transition">Business Types</a>
                <a href="#preview" class="hover:text-orange-400 transition">Live Preview</a>
                @if (config('subscription.plans_enabled', false))
                    <a href="#pricing" class="hover:text-orange-400 transition">Pricing</a>
                @else
                    <a href="#free-access" class="hover:text-emerald-400 text-emerald-400 transition flex items-center gap-1">
                        <span>100% Free</span>
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                    </a>
                @endif
                <a href="#faq" class="hover:text-orange-400 transition">FAQ</a>
            </nav>

            <!-- Right: Language Switcher & Auth Buttons -->
            <div class="flex items-center space-x-3">
                <!-- Bilingual Switcher -->
                <div class="flex items-center bg-slate-900 border border-slate-800 rounded-lg p-0.5 text-xs font-semibold">
                    <a href="{{ route('locale.switch', 'en') }}"
                       class="px-2.5 py-1 rounded-md transition {{ app()->getLocale() === 'en' ? 'bg-orange-600 text-white font-bold' : 'text-slate-400 hover:text-white' }}">
                        EN
                    </a>
                    <a href="{{ route('locale.switch', 'km') }}"
                       class="px-2.5 py-1 rounded-md transition {{ app()->getLocale() === 'km' ? 'bg-orange-600 text-white font-bold' : 'text-slate-400 hover:text-white' }}">
                        ខ្មែរ
                    </a>
                </div>

                @auth
                    @if (Auth::user()->is_super_admin)
                        <a href="{{ route('admin.dashboard') }}"
                           class="hidden sm:inline-flex items-center space-x-1.5 px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs uppercase tracking-wider transition shadow-lg shadow-purple-600/30">
                            <span>Super Admin</span>
                        </a>
                    @else
                        <a href="{{ route('business.dashboard') }}"
                           class="hidden sm:inline-flex items-center space-x-1.5 px-4 py-2 rounded-xl bg-orange-600 hover:bg-orange-500 text-white font-bold text-xs uppercase tracking-wider transition shadow-lg shadow-orange-600/30">
                            <span>My Workspace</span>
                        </a>
                    @endif
                @else
                    <a href="{{ route('login') }}"
                       class="text-xs font-bold text-slate-300 hover:text-white px-3 py-2 rounded-lg transition">
                        {{ __('app.login') }}
                    </a>

                    <a href="{{ route('register') }}"
                       class="px-4 py-2 rounded-xl bg-gradient-to-r from-orange-600 to-amber-500 hover:from-orange-500 hover:to-amber-400 text-white font-bold text-xs uppercase tracking-wider transition shadow-lg shadow-orange-600/30 active:scale-95">
                        {{ __('app.register') }}
                    </a>
                @endauth
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="relative pt-12 pb-20 md:pt-20 md:pb-32 overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <!-- Badge Announcement -->
            <div class="inline-flex items-center space-x-2 bg-gradient-to-r from-orange-500/10 via-amber-500/10 to-transparent border border-orange-500/30 px-3.5 py-1.5 rounded-full mb-8 backdrop-blur-md">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                <span class="text-xs font-bold text-orange-400">Next-Gen Multi-Tenant Platform for Cambodia</span>
                <span class="text-slate-600">•</span>
                <span class="text-xs text-slate-300 font-medium">Bakong KHQR & Dual Currency Ready</span>
            </div>

            <!-- Main Heading -->
            <h1 class="text-4xl sm:text-6xl lg:text-7xl font-black tracking-tight text-white max-w-5xl mx-auto leading-[1.1]">
                One Platform. Every Business.
                <span class="block bg-gradient-to-r from-orange-400 via-amber-300 to-yellow-400 bg-clip-text text-transparent">
                    Smarter Management.
                </span>
            </h1>

            <!-- Subtitle -->
            <p class="mt-6 text-base sm:text-xl text-slate-300 max-w-3xl mx-auto leading-relaxed">
                Empower your café, restaurant, retail shop, or mart with contactless Digital QR Menus, blazing-fast Touch POS, real-time Kitchen Display, and automated recipe inventory deduction.
            </p>

            <!-- Dual CTA Buttons -->
            <div class="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="{{ route('register') }}"
                   class="w-full sm:w-auto px-8 py-4 rounded-2xl bg-gradient-to-r from-orange-600 via-orange-500 to-amber-500 hover:from-orange-500 hover:to-amber-400 text-white font-extrabold text-base shadow-xl shadow-orange-600/30 hover:shadow-orange-600/50 transition-all active:scale-95 flex items-center justify-center space-x-2">
                    <span>{{ __('app.get_started_free') }}</span>
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                    </svg>
                </a>

                @if ($demoBusiness)
                    <a href="{{ url('/' . $demoBusiness->slug) }}" target="_blank"
                       class="w-full sm:w-auto px-8 py-4 rounded-2xl bg-slate-900 hover:bg-slate-800 text-slate-200 border border-slate-700 font-extrabold text-base transition flex items-center justify-center space-x-2">
                        <svg class="w-5 h-5 text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        <span>{{ __('app.view_live_demo') }} ({{ $demoBusiness->name }})</span>
                    </a>
                @endif
            </div>

            <!-- Trust Highlights Badges -->
            <div class="mt-12 flex flex-wrap items-center justify-center gap-6 sm:gap-10 text-xs sm:text-sm text-slate-400 font-medium">
                <div class="flex items-center space-x-2">
                    <svg class="w-4 h-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span>Instant Setup (No Hardware Lock-in)</span>
                </div>
                <div class="flex items-center space-x-2">
                    <svg class="w-4 h-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span>USD ($) & KHR (៛) Dual Currency</span>
                </div>
                <div class="flex items-center space-x-2">
                    <svg class="w-4 h-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span>National Bakong KHQR Integrated</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Real-time Stats & Cambodian Bank Payments Section (Inspired by readmenu.site) -->
    <section class="py-12 bg-slate-950 border-y border-slate-900/80 relative">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- 4 Stats Cards Bar -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-12">
                <div class="p-6 rounded-2xl bg-slate-900/50 border border-slate-800/80 backdrop-blur-md flex items-center space-x-4 hover:border-orange-500/40 transition">
                    <div class="w-12 h-12 rounded-xl bg-orange-500/10 border border-orange-500/20 text-orange-400 flex items-center justify-center text-xl shadow-inner">
                        <i class="fi fi-rr-shop"></i>
                    </div>
                    <div>
                        <div class="text-2xl sm:text-3xl font-black text-white tracking-tight">{{ number_format($liveStats['active_outlets'] ?? 0) }}</div>
                        <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider mt-0.5">Active Outlets</div>
                    </div>
                </div>

                <div class="p-6 rounded-2xl bg-slate-900/50 border border-slate-800/80 backdrop-blur-md flex items-center space-x-4 hover:border-blue-500/40 transition">
                    <div class="w-12 h-12 rounded-xl bg-blue-500/10 border border-blue-500/20 text-blue-400 flex items-center justify-center text-xl shadow-inner">
                        <i class="fi fi-rr-eye"></i>
                    </div>
                    <div>
                        <div class="text-2xl sm:text-3xl font-black text-white tracking-tight">{{ number_format($liveStats['menu_views'] ?? 0) }}</div>
                        <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider mt-0.5">Total Menu Views</div>
                    </div>
                </div>

                <div class="p-6 rounded-2xl bg-slate-900/50 border border-slate-800/80 backdrop-blur-md flex items-center space-x-4 hover:border-purple-500/40 transition">
                    <div class="w-12 h-12 rounded-xl bg-purple-500/10 border border-purple-500/20 text-purple-400 flex items-center justify-center text-xl shadow-inner">
                        <i class="fi fi-rr-box-open"></i>
                    </div>
                    <div>
                        <div class="text-2xl sm:text-3xl font-black text-white tracking-tight">{{ number_format($liveStats['total_products'] ?? 0) }}</div>
                        <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider mt-0.5">Products & Dishes</div>
                    </div>
                </div>

                <div class="p-6 rounded-2xl bg-slate-900/50 border border-slate-800/80 backdrop-blur-md flex items-center space-x-4 hover:border-emerald-500/40 transition">
                    <div class="w-12 h-12 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center text-xl shadow-inner">
                        <i class="fi fi-rr-shopping-bag"></i>
                    </div>
                    <div>
                        <div class="text-2xl sm:text-3xl font-black text-white tracking-tight">{{ number_format($liveStats['total_orders'] ?? 0) }}</div>
                        <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider mt-0.5">Orders Processed</div>
                    </div>
                </div>
            </div>

            <!-- Cambodian Payments Supported Marquee -->
            <div class="text-center mb-6">
                <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-orange-500/10 border border-orange-500/20 text-orange-400 font-bold text-xs uppercase tracking-wider">
                    <i class="fi fi-rr-credit-card"></i> Payments Supported
                </span>
                <h3 class="text-lg sm:text-2xl font-black text-white mt-2">Accept payments from every major bank in Cambodia</h3>
                <p class="text-xs sm:text-sm text-slate-400 mt-1">One QR. Every wallet. Powered by National Bakong KHQR & Dual Currency USD / KHR.</p>
            </div>

            <div class="payments-marquee">
                <div class="payments-track">
                    <!-- Chip Group 1 -->
                    <div class="dark-pay-chip">
                        <img src="{{ asset('images/payments/bakong.png') }}" alt="Bakong KHQR" loading="lazy">
                        <span>Bakong KHQR</span>
                    </div>
                    <div class="dark-pay-chip">
                        <img src="{{ asset('images/payments/aba.png') }}" alt="ABA Bank" loading="lazy">
                        <span>ABA Bank</span>
                    </div>
                    <div class="dark-pay-chip">
                        <img src="{{ asset('images/payments/acleda.jpg') }}" alt="ACLEDA Bank" loading="lazy">
                        <span>ACLEDA Bank</span>
                    </div>
                    <div class="dark-pay-chip">
                        <img src="{{ asset('images/payments/wing.jpg') }}" alt="Wing Bank" loading="lazy">
                        <span>Wing Bank</span>
                    </div>
                    <div class="dark-pay-chip">
                        <img src="{{ asset('images/payments/amk.png') }}" alt="AMK" loading="lazy">
                        <span>AMK Microfinance</span>
                    </div>
                    <div class="dark-pay-chip">
                        <img src="{{ asset('images/payments/prince.jpg') }}" alt="Prince Bank" loading="lazy">
                        <span>Prince Bank</span>
                    </div>
                    <div class="dark-pay-chip">
                        <img src="{{ asset('images/payments/sathapana.jpg') }}" alt="Sathapana" loading="lazy">
                        <span>Sathapana Bank</span>
                    </div>
                    <div class="dark-pay-chip">
                        <img src="{{ asset('images/payments/canadia.png') }}" alt="Canadia" loading="lazy">
                        <span>Canadia Bank</span>
                    </div>
                    <div class="dark-pay-chip">
                        <img src="{{ asset('images/payments/vattanac.webp') }}" alt="Vattanac" loading="lazy">
                        <span>Vattanac Bank</span>
                    </div>
                    <div class="dark-pay-chip">
                        <img src="{{ asset('images/payments/chipmong.jpg') }}" alt="Chip Mong" loading="lazy">
                        <span>Chip Mong Bank</span>
                    </div>
                    <div class="dark-pay-chip">
                        <img src="https://api.iconify.design/lucide/banknote.svg?color=%2316a34a" alt="Cash" loading="lazy">
                        <span>Cash (USD & ៛)</span>
                    </div>

                    <!-- Chip Group 2 (Repeated for seamless loop) -->
                    <div class="dark-pay-chip">
                        <img src="{{ asset('images/payments/bakong.png') }}" alt="Bakong KHQR" loading="lazy">
                        <span>Bakong KHQR</span>
                    </div>
                    <div class="dark-pay-chip">
                        <img src="{{ asset('images/payments/aba.png') }}" alt="ABA Bank" loading="lazy">
                        <span>ABA Bank</span>
                    </div>
                    <div class="dark-pay-chip">
                        <img src="{{ asset('images/payments/acleda.jpg') }}" alt="ACLEDA Bank" loading="lazy">
                        <span>ACLEDA Bank</span>
                    </div>
                    <div class="dark-pay-chip">
                        <img src="{{ asset('images/payments/wing.jpg') }}" alt="Wing Bank" loading="lazy">
                        <span>Wing Bank</span>
                    </div>
                    <div class="dark-pay-chip">
                        <img src="{{ asset('images/payments/amk.png') }}" alt="AMK" loading="lazy">
                        <span>AMK Microfinance</span>
                    </div>
                    <div class="dark-pay-chip">
                        <img src="{{ asset('images/payments/prince.jpg') }}" alt="Prince Bank" loading="lazy">
                        <span>Prince Bank</span>
                    </div>
                    <div class="dark-pay-chip">
                        <img src="{{ asset('images/payments/sathapana.jpg') }}" alt="Sathapana" loading="lazy">
                        <span>Sathapana Bank</span>
                    </div>
                    <div class="dark-pay-chip">
                        <img src="{{ asset('images/payments/canadia.png') }}" alt="Canadia" loading="lazy">
                        <span>Canadia Bank</span>
                    </div>
                    <div class="dark-pay-chip">
                        <img src="{{ asset('images/payments/vattanac.webp') }}" alt="Vattanac" loading="lazy">
                        <span>Vattanac Bank</span>
                    </div>
                    <div class="dark-pay-chip">
                        <img src="{{ asset('images/payments/chipmong.jpg') }}" alt="Chip Mong" loading="lazy">
                        <span>Chip Mong Bank</span>
                    </div>
                    <div class="dark-pay-chip">
                        <img src="https://api.iconify.design/lucide/banknote.svg?color=%2316a34a" alt="Cash" loading="lazy">
                        <span>Cash (USD & ៛)</span>
                    </div>
                </div>
            </div>
        </div>
    </section>


    <!-- Interactive Live Preview Tabs -->
    <section id="preview" class="py-16 bg-slate-900/50 border-y border-slate-800 relative">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-10">
                <h2 class="text-xs font-bold uppercase tracking-wider text-orange-400 mb-2">Omnichannel Architecture</h2>
                <p class="text-3xl font-black text-white sm:text-4xl">Everything You Need To Run Smarter</p>
                <p class="mt-3 text-slate-400 text-sm">Switch between platform modes to see how customers order, cashiers ring up sales, kitchens receive tickets, and smart tables connect.</p>

                <!-- Mode Switcher Tabs -->
                <div class="mt-8 inline-flex p-1.5 rounded-2xl bg-slate-950 border border-slate-800 space-x-1 text-xs sm:text-sm font-bold">
                    <button @click="activePreviewTab = 'menu'"
                            :class="activePreviewTab === 'menu' ? 'bg-orange-600 text-white shadow-md' : 'text-slate-400 hover:text-white'"
                            class="px-4 py-2.5 rounded-xl transition flex items-center space-x-2">
                        <span>📱 Digital QR Menu</span>
                    </button>
                    <button @click="activePreviewTab = 'pos'"
                            :class="activePreviewTab === 'pos' ? 'bg-orange-600 text-white shadow-md' : 'text-slate-400 hover:text-white'"
                            class="px-4 py-2.5 rounded-xl transition flex items-center space-x-2">
                        <span>💻 Touch POS Terminal</span>
                    </button>
                    <button @click="activePreviewTab = 'kds'"
                            :class="activePreviewTab === 'kds' ? 'bg-orange-600 text-white shadow-md' : 'text-slate-400 hover:text-white'"
                            class="px-4 py-2.5 rounded-xl transition flex items-center space-x-2">
                        <span>🍳 Kitchen Display (KDS)</span>
                    </button>
                    <button @click="activePreviewTab = 'table'"
                            :class="activePreviewTab === 'table' ? 'bg-orange-600 text-white shadow-md' : 'text-slate-400 hover:text-white'"
                            class="px-4 py-2.5 rounded-xl transition flex items-center space-x-2">
                        <span>🏷️ Smart QR Standee</span>
                    </button>
                </div>
            </div>

            <!-- Tab Content 1: Digital Menu on Mobile Frame -->
            <div x-show="activePreviewTab === 'menu'" x-cloak class="flex justify-center transition-all">
                <div class="w-full max-w-sm rounded-3xl bg-slate-900 border-4 border-slate-700 shadow-2xl p-4 overflow-hidden">
                    <!-- Mobile Header Mockup -->
                    <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                        <div>
                            <h4 class="font-bold text-white text-sm">Romdoul Khmer Kitchen</h4>
                            <p class="text-[11px] text-emerald-400 font-semibold">Table 05 • Open Now</p>
                        </div>
                        <span class="text-xs font-mono font-bold bg-slate-800 px-2 py-1 rounded text-orange-400">$ / ៛ Dual</span>
                    </div>

                    <!-- Category Pills -->
                    <div class="flex space-x-1.5 py-3 overflow-x-auto text-[11px] font-bold">
                        <span class="px-3 py-1 rounded-full bg-orange-600 text-white shrink-0">Signature</span>
                        <span class="px-3 py-1 rounded-full bg-slate-800 text-slate-300 shrink-0">Coffee & Drinks</span>
                        <span class="px-3 py-1 rounded-full bg-slate-800 text-slate-300 shrink-0">Khmer Specials</span>
                    </div>

                    <!-- Food Items List -->
                    <div class="space-y-2.5">
                        <div class="p-2.5 rounded-xl bg-slate-950 border border-slate-800/80 flex items-center justify-between">
                            <div>
                                <p class="text-xs font-bold text-white">Traditional Fish Amok</p>
                                <p class="text-[10px] text-slate-400">Steamed coconut curry in banana leaf</p>
                                <p class="text-xs font-mono font-extrabold text-orange-400 mt-1">$6.50 <span class="text-[10px] text-slate-400 font-normal">(26,700 ៛)</span></p>
                            </div>
                            <button class="w-7 h-7 rounded-lg bg-orange-600 hover:bg-orange-500 text-white font-bold flex items-center justify-center text-sm shadow">+</button>
                        </div>

                        <div class="p-2.5 rounded-xl bg-slate-950 border border-slate-800/80 flex items-center justify-between">
                            <div>
                                <p class="text-xs font-bold text-white">Iced Brown Milk Coffee</p>
                                <p class="text-[10px] text-slate-400">Condensed milk, double espresso</p>
                                <p class="text-xs font-mono font-extrabold text-orange-400 mt-1">$2.25 <span class="text-[10px] text-slate-400 font-normal">(9,200 ៛)</span></p>
                            </div>
                            <button class="w-7 h-7 rounded-lg bg-orange-600 hover:bg-orange-500 text-white font-bold flex items-center justify-center text-sm shadow">+</button>
                        </div>
                    </div>

                    <!-- Checkout Floating Bar -->
                    <div class="mt-4 p-3 rounded-2xl bg-orange-600 text-white flex items-center justify-between font-bold text-xs shadow-lg shadow-orange-600/40">
                        <span>Cart (2 items)</span>
                        <span class="font-mono text-sm">$8.75 (35,900 ៛)</span>
                    </div>
                </div>
            </div>

            <!-- Tab Content 2: Touch POS Terminal Frame -->
            <div x-show="activePreviewTab === 'pos'" x-cloak class="transition-all">
                <div class="w-full max-w-4xl mx-auto rounded-3xl bg-slate-900 border-4 border-slate-700 shadow-2xl p-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- POS Products Quick Grid (2 cols) -->
                        <div class="md:col-span-2 space-y-4">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold uppercase tracking-wider text-slate-400 font-mono">TOUCH POS TERMINAL #01</span>
                                <span class="text-xs font-mono font-bold text-emerald-400">Cash Register OPEN</span>
                            </div>
                            <div class="grid grid-cols-3 gap-2.5">
                                <div class="p-3 rounded-xl bg-slate-950 border border-slate-800 hover:border-orange-500 cursor-pointer">
                                    <p class="text-xs font-bold text-white truncate">Espresso</p>
                                    <p class="text-xs font-mono font-bold text-orange-400 mt-1">$1.75</p>
                                </div>
                                <div class="p-3 rounded-xl bg-slate-950 border border-slate-800 hover:border-orange-500 cursor-pointer">
                                    <p class="text-xs font-bold text-white truncate">Brown Coffee</p>
                                    <p class="text-xs font-mono font-bold text-orange-400 mt-1">$2.25</p>
                                </div>
                                <div class="p-3 rounded-xl bg-slate-950 border border-slate-800 hover:border-orange-500 cursor-pointer">
                                    <p class="text-xs font-bold text-white truncate">Fish Amok</p>
                                    <p class="text-xs font-mono font-bold text-orange-400 mt-1">$6.50</p>
                                </div>
                                <div class="p-3 rounded-xl bg-slate-950 border border-slate-800 hover:border-orange-500 cursor-pointer">
                                    <p class="text-xs font-bold text-white truncate">Beef Lok Lak</p>
                                    <p class="text-xs font-mono font-bold text-orange-400 mt-1">$5.75</p>
                                </div>
                                <div class="p-3 rounded-xl bg-slate-950 border border-slate-800 hover:border-orange-500 cursor-pointer">
                                    <p class="text-xs font-bold text-white truncate">Green Tea Latte</p>
                                    <p class="text-xs font-mono font-bold text-orange-400 mt-1">$2.50</p>
                                </div>
                                <div class="p-3 rounded-xl bg-slate-950 border border-slate-800 hover:border-orange-500 cursor-pointer">
                                    <p class="text-xs font-bold text-white truncate">Passion Soda</p>
                                    <p class="text-xs font-mono font-bold text-orange-400 mt-1">$2.00</p>
                                </div>
                            </div>
                        </div>

                        <!-- POS Register Cart & Tender (1 col) -->
                        <div class="bg-slate-950 rounded-2xl p-4 border border-slate-800 flex flex-col justify-between">
                            <div>
                                <h5 class="text-xs font-bold uppercase text-slate-400 mb-2">Current Ticket</h5>
                                <div class="text-xs space-y-1.5 border-b border-slate-800 pb-2">
                                    <div class="flex justify-between"><span>1x Brown Coffee</span><span class="font-mono">$2.25</span></div>
                                    <div class="flex justify-between"><span>1x Beef Lok Lak</span><span class="font-mono">$5.75</span></div>
                                </div>
                                <div class="mt-2 text-right">
                                    <p class="text-xs text-slate-400">Total Due</p>
                                    <p class="text-2xl font-black font-mono text-orange-400">$8.00</p>
                                    <p class="text-xs font-mono text-slate-400 font-semibold">32,800 KHR</p>
                                </div>
                            </div>

                            <div class="mt-4 space-y-2">
                                <div class="grid grid-cols-2 gap-1.5">
                                    <button class="py-2 bg-emerald-600 text-white rounded-lg font-bold text-xs">💵 Cash USD/៛</button>
                                    <button class="py-2 bg-red-600 text-white rounded-lg font-bold text-xs">🇰🇭 Bakong KHQR</button>
                                </div>
                                <button class="w-full py-2.5 bg-orange-600 text-white rounded-xl font-bold text-xs uppercase tracking-wider">Fast Checkout</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab Content 3: Kitchen Display System (KDS) Frame -->
            <div x-show="activePreviewTab === 'kds'" x-cloak class="transition-all">
                <div class="w-full max-w-4xl mx-auto rounded-3xl bg-slate-900 border-4 border-slate-700 shadow-2xl p-6">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-800 mb-4">
                        <div class="flex items-center space-x-2">
                            <span class="text-red-400 font-mono font-bold text-xs bg-red-500/20 px-2 py-0.5 rounded">KDS LIVE QUEUE</span>
                            <span class="text-xs text-slate-400">3 Tickets Pending</span>
                        </div>
                        <span class="text-xs font-mono font-bold text-slate-300">12:35:40 Phnom Penh</span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Ticket 1 -->
                        <div class="p-3.5 rounded-2xl bg-slate-950 border border-amber-500/50 shadow-lg">
                            <div class="flex justify-between items-center text-xs pb-2 border-b border-slate-800">
                                <span class="font-mono font-black text-amber-400">#0012</span>
                                <span class="font-bold text-indigo-400">Table 03</span>
                                <span class="font-mono text-slate-400">3m ago</span>
                            </div>
                            <div class="py-2 text-xs space-y-1">
                                <p class="font-bold text-white">2x Traditional Fish Amok</p>
                                <p class="text-slate-400 pl-2 text-[11px]">+ Extra Jasmine Rice</p>
                                <p class="font-bold text-white">1x Beef Lok Lak</p>
                            </div>
                            <button class="w-full mt-2 py-1.5 bg-blue-600 text-white rounded-lg font-bold text-xs uppercase">Start Cooking</button>
                        </div>

                        <!-- Ticket 2 -->
                        <div class="p-3.5 rounded-2xl bg-slate-950 border border-blue-500/50 shadow-lg">
                            <div class="flex justify-between items-center text-xs pb-2 border-b border-slate-800">
                                <span class="font-mono font-black text-blue-400">#0011</span>
                                <span class="font-bold text-emerald-400">Takeaway</span>
                                <span class="font-mono text-amber-400">9m ago</span>
                            </div>
                            <div class="py-2 text-xs space-y-1">
                                <p class="font-bold text-white">2x Brown Milk Coffee</p>
                                <p class="text-slate-400 pl-2 text-[11px]">+ Less Sweet 50%</p>
                                <p class="font-bold text-white">1x Green Tea Frappe</p>
                            </div>
                            <button class="w-full mt-2 py-1.5 bg-emerald-600 text-white rounded-lg font-bold text-xs uppercase">Mark Ready</button>
                        </div>

                        <!-- Ticket 3 -->
                        <div class="p-3.5 rounded-2xl bg-slate-950 border border-emerald-500/50 shadow-lg">
                            <div class="flex justify-between items-center text-xs pb-2 border-b border-slate-800">
                                <span class="font-mono font-black text-emerald-400">#0010</span>
                                <span class="font-bold text-indigo-400">Table 01</span>
                                <span class="font-mono text-emerald-400">Ready</span>
                            </div>
                            <div class="py-2 text-xs space-y-1">
                                <p class="font-bold text-white">1x Grilled Pork Rice</p>
                                <p class="font-bold text-white">1x Fresh Passion Soda</p>
                            </div>
                            <button class="w-full mt-2 py-1.5 bg-slate-700 text-white rounded-lg font-bold text-xs uppercase">Served / Done</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab Content 4: Smart QR Standee Frame -->
            <div x-show="activePreviewTab === 'table'" x-cloak class="flex justify-center transition-all">
                <div class="w-full max-w-sm rounded-3xl bg-slate-900 border-4 border-slate-700 shadow-2xl p-6 text-center">
                    <div class="p-6 rounded-2xl bg-white text-slate-900 shadow-xl border border-slate-200">
                        <p class="text-xs font-black tracking-widest uppercase text-orange-600">SCAN TO VIEW MENU & ORDER</p>
                        <h4 class="text-lg font-black mt-1">Romdoul Khmer Kitchen</h4>

                        <!-- QR Code Illustration Placeholder -->
                        <div class="my-4 mx-auto w-40 h-40 bg-slate-100 p-2 rounded-xl border-2 border-slate-800 flex items-center justify-center">
                            <svg class="w-32 h-32 text-slate-900" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M2 2h8v8H2V2zm2 2v4h4V4H4zm10-2h8v8h-8V2zm2 2v4h4V4h-4zM2 14h8v8H2v-8zm2 2v4h4v-4H4zm14 0h4v4h-4v-4zm-4 4h4v4h-4v-4zm0-4h4v-4h-4v4zm4-4h4v4h-4v-4z"/>
                            </svg>
                        </div>

                        <div class="inline-block bg-orange-600 text-white text-xs font-mono font-black px-4 py-1.5 rounded-full shadow">
                            TABLE NUMBER: 05
                        </div>

                        <p class="text-[11px] text-slate-500 mt-3">No App Download Required • Point Camera to Scan</p>
                    </div>
                    <p class="text-xs text-slate-400 mt-4">One-click print ready 80mm table standees & QR sticker exports.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Supported Business Types Section -->
    <section id="businesses" class="py-20 bg-slate-950">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <h2 class="text-xs font-bold uppercase tracking-wider text-orange-400 mb-2">Versatile Multi-Tenant Scope</h2>
                <p class="text-3xl font-black text-white sm:text-4xl">{{ __('app.supported_businesses') }}</p>
                <p class="mt-3 text-slate-400 text-sm">Whether you run a fast-paced bubble tea shop in BKK1 or a supermarket chain in Siem Reap, SABAYRA tailors specifically to your operational flow.</p>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6">
                <!-- 1. Restaurant -->
                <div class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800 hover:border-orange-500/60 transition group hover:-translate-y-1">
                    <div class="w-10 h-10 rounded-xl bg-orange-500/10 text-orange-400 flex items-center justify-center font-bold text-lg mb-3 group-hover:bg-orange-600 group-hover:text-white transition">🍽️</div>
                    <h3 class="font-bold text-white text-base">Restaurants</h3>
                    <p class="text-xs text-slate-400 mt-1">Dine-in tables, digital orders, bill splitting, and kitchen tickets.</p>
                </div>

                <!-- 2. Cafe -->
                <div class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800 hover:border-orange-500/60 transition group hover:-translate-y-1">
                    <div class="w-10 h-10 rounded-xl bg-amber-500/10 text-amber-400 flex items-center justify-center font-bold text-lg mb-3 group-hover:bg-amber-600 group-hover:text-white transition">☕</div>
                    <h3 class="font-bold text-white text-base">Cafés & Coffee</h3>
                    <p class="text-xs text-slate-400 mt-1">Roast options, milk alternatives, syrup modifiers, and loyalty.</p>
                </div>

                <!-- 3. Milk Tea -->
                <div class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800 hover:border-orange-500/60 transition group hover:-translate-y-1">
                    <div class="w-10 h-10 rounded-xl bg-pink-500/10 text-pink-400 flex items-center justify-center font-bold text-lg mb-3 group-hover:bg-pink-600 group-hover:text-white transition">🧋</div>
                    <h3 class="font-bold text-white text-base">Milk Tea Shops</h3>
                    <p class="text-xs text-slate-400 mt-1">Ice % and sugar % level selection, bobas, pearls, and jelly add-ons.</p>
                </div>

                <!-- 4. Bakery -->
                <div class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800 hover:border-orange-500/60 transition group hover:-translate-y-1">
                    <div class="w-10 h-10 rounded-xl bg-yellow-500/10 text-yellow-400 flex items-center justify-center font-bold text-lg mb-3 group-hover:bg-yellow-600 group-hover:text-white transition">🥐</div>
                    <h3 class="font-bold text-white text-base">Bakeries & Pastry</h3>
                    <p class="text-xs text-slate-400 mt-1">Daily fresh batches, customized celebration cakes, and pre-orders.</p>
                </div>

                <!-- 5. Fast Food -->
                <div class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800 hover:border-orange-500/60 transition group hover:-translate-y-1">
                    <div class="w-10 h-10 rounded-xl bg-red-500/10 text-red-400 flex items-center justify-center font-bold text-lg mb-3 group-hover:bg-red-600 group-hover:text-white transition">🍔</div>
                    <h3 class="font-bold text-white text-base">Fast Food</h3>
                    <p class="text-xs text-slate-400 mt-1">Combo builder, rapid counter POS checkout, and queue order numbers.</p>
                </div>

                <!-- 6. Supermarkets -->
                <div class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800 hover:border-orange-500/60 transition group hover:-translate-y-1">
                    <div class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center font-bold text-lg mb-3 group-hover:bg-emerald-600 group-hover:text-white transition">🛒</div>
                    <h3 class="font-bold text-white text-base">Supermarkets</h3>
                    <p class="text-xs text-slate-400 mt-1">Barcode scanning, thousands of SKUs, weighted scale integration.</p>
                </div>

                <!-- 7. Mini Marts -->
                <div class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800 hover:border-orange-500/60 transition group hover:-translate-y-1">
                    <div class="w-10 h-10 rounded-xl bg-cyan-500/10 text-cyan-400 flex items-center justify-center font-bold text-lg mb-3 group-hover:bg-cyan-600 group-hover:text-white transition">🏪</div>
                    <h3 class="font-bold text-white text-base">Mini Marts</h3>
                    <p class="text-xs text-slate-400 mt-1">High-speed transactions, dual currency Riel & Dollar cash changes.</p>
                </div>

                <!-- 8. Retail & Clothing -->
                <div class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800 hover:border-orange-500/60 transition group hover:-translate-y-1">
                    <div class="w-10 h-10 rounded-xl bg-indigo-500/10 text-indigo-400 flex items-center justify-center font-bold text-lg mb-3 group-hover:bg-indigo-600 group-hover:text-white transition">👗</div>
                    <h3 class="font-bold text-white text-base">Clothing & Retail</h3>
                    <p class="text-xs text-slate-400 mt-1">Size and color matrices, lookbook catalog, social share links.</p>
                </div>

                <!-- 9. Beauty & Salon -->
                <div class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800 hover:border-orange-500/60 transition group hover:-translate-y-1">
                    <div class="w-10 h-10 rounded-xl bg-fuchsia-500/10 text-fuchsia-400 flex items-center justify-center font-bold text-lg mb-3 group-hover:bg-fuchsia-600 group-hover:text-white transition">💄</div>
                    <h3 class="font-bold text-white text-base">Beauty & Cosmetics</h3>
                    <p class="text-xs text-slate-400 mt-1">Product lines, shade variants, skincare treatment menus.</p>
                </div>

                <!-- 10. Services -->
                <div class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800 hover:border-orange-500/60 transition group hover:-translate-y-1">
                    <div class="w-10 h-10 rounded-xl bg-teal-500/10 text-teal-400 flex items-center justify-center font-bold text-lg mb-3 group-hover:bg-teal-600 group-hover:text-white transition">🛠️</div>
                    <h3 class="font-bold text-white text-base">Service Businesses</h3>
                    <p class="text-xs text-slate-400 mt-1">Hourly rates, car wash, repairs, spa packages, direct bookings.</p>
                </div>

                <!-- 11. Food Courts -->
                <div class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800 hover:border-orange-500/60 transition group hover:-translate-y-1">
                    <div class="w-10 h-10 rounded-xl bg-purple-500/10 text-purple-400 flex items-center justify-center font-bold text-lg mb-3 group-hover:bg-purple-600 group-hover:text-white transition">🎪</div>
                    <h3 class="font-bold text-white text-base">Pop-ups & Markets</h3>
                    <p class="text-xs text-slate-400 mt-1">Phone-only POS, no cords needed, instant Bakong QR payments.</p>
                </div>

                <!-- 12. Universal -->
                <div class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800 hover:border-orange-500/60 transition group hover:-translate-y-1">
                    <div class="w-10 h-10 rounded-xl bg-slate-700 text-slate-200 flex items-center justify-center font-bold text-lg mb-3 group-hover:bg-orange-600 group-hover:text-white transition">🌐</div>
                    <h3 class="font-bold text-white text-base">Any Other Business</h3>
                    <p class="text-xs text-slate-400 mt-1">Custom product catalogs, price lists, and digital inquiries.</p>
                </div>
            </div>
        </div>
    </section>


    <!-- 3-Step "How It Works" Section (from readmenu.site, styled for dark theme) -->
    <section class="py-20 bg-slate-950/80 border-t border-slate-900 relative">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-orange-500/10 border border-orange-500/20 text-orange-400 font-bold text-xs uppercase tracking-wider">
                    <i class="fi fi-rr-bolt"></i> Fast Onboarding
                </span>
                <h2 class="text-3xl sm:text-4xl font-black text-white mt-3">Get your business running in 3 simple steps</h2>
                <p class="text-sm sm:text-base text-slate-400 mt-2">Go digital in less than 5 minutes without expensive hardware or technical complexity.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 relative">
                <!-- Step 1 -->
                <div class="p-8 rounded-3xl bg-slate-900/60 border border-slate-800/80 hover:border-orange-500/50 transition relative group hover:-translate-y-1">
                    <div class="w-10 h-10 rounded-full bg-orange-600 text-white font-black text-sm flex items-center justify-center mb-6 shadow-lg shadow-orange-600/30">
                        1
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-orange-500/10 text-orange-400 flex items-center justify-center text-2xl mb-4 group-hover:scale-110 transition">
                        <i class="fi fi-rr-user-add"></i>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-2">Create Workspace</h3>
                    <p class="text-sm text-slate-400 leading-relaxed">
                        Sign up in 30 seconds. Choose your business type (Restaurant, Café, Mart), set default currency, and claim your custom URL slug.
                    </p>
                </div>

                <!-- Step 2 -->
                <div class="p-8 rounded-3xl bg-slate-900/60 border border-slate-800/80 hover:border-orange-500/50 transition relative group hover:-translate-y-1">
                    <div class="w-10 h-10 rounded-full bg-amber-500 text-white font-black text-sm flex items-center justify-center mb-6 shadow-lg shadow-amber-500/30">
                        2
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-amber-500/10 text-amber-400 flex items-center justify-center text-2xl mb-4 group-hover:scale-110 transition">
                        <i class="fi fi-rr-restaurant"></i>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-2">Add Products & Tables</h3>
                    <p class="text-sm text-slate-400 leading-relaxed">
                        Upload appetizing photos, define categories, set prices in USD/KHR, and generate table QR standees ready for one-click printing.
                    </p>
                </div>

                <!-- Step 3 -->
                <div class="p-8 rounded-3xl bg-slate-900/60 border border-slate-800/80 hover:border-orange-500/50 transition relative group hover:-translate-y-1">
                    <div class="w-10 h-10 rounded-full bg-emerald-500 text-white font-black text-sm flex items-center justify-center mb-6 shadow-lg shadow-emerald-500/30">
                        3
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 text-emerald-400 flex items-center justify-center text-2xl mb-4 group-hover:scale-110 transition">
                        <i class="fi fi-rr-paper-plane"></i>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-2">Scan & Receive Orders</h3>
                    <p class="text-sm text-slate-400 leading-relaxed">
                        Customers scan to order. Orders fire straight to your Touch POS and Kitchen Screen (KDS), with instant Bakong KHQR confirmation.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Deep-Dive Features Pillars -->
    <section id="features" class="py-20 bg-slate-900/30 border-t border-slate-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <h2 class="text-xs font-bold uppercase tracking-wider text-orange-400 mb-2">Built For Performance</h2>
                <p class="text-3xl font-black text-white sm:text-4xl">Engineered For Cambodian Commerce</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Feature 1 -->
                <div class="p-8 rounded-3xl bg-slate-900 border border-slate-800 hover:border-orange-500/50 transition">
                    <div class="w-12 h-12 rounded-2xl bg-orange-500/20 text-orange-400 flex items-center justify-center mb-6">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-2">QR Digital Ordering</h3>
                    <p class="text-slate-400 text-sm leading-relaxed">
                        Let customers scan and order directly from their phone. No app installation needed. Real-time cart synchronization, modifiers, and instant order routing to kitchen and POS.
                    </p>
                </div>

                <!-- Feature 2 -->
                <div class="p-8 rounded-3xl bg-slate-900 border border-slate-800 hover:border-orange-500/50 transition">
                    <div class="w-12 h-12 rounded-2xl bg-red-500/20 text-red-400 flex items-center justify-center mb-6">
                        <span class="font-black text-lg">KHQR</span>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-2">Bakong KHQR Payments</h3>
                    <p class="text-slate-400 text-sm leading-relaxed">
                        Generate official EMVCo-compliant Bakong KHQR dynamic codes per order in USD or KHR. Customers scan and pay instantly using ABA, ACLEDA, Wing, or any Cambodian bank app.
                    </p>
                </div>

                <!-- Feature 3 -->
                <div class="p-8 rounded-3xl bg-slate-900 border border-slate-800 hover:border-orange-500/50 transition">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center mb-6">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-2">Recipe-Level Inventory</h3>
                    <p class="text-slate-400 text-sm leading-relaxed">
                        Automate raw material tracking. When a beverage or dish is ordered, ingredients like coffee beans, syrups, and milk are automatically calculated and decremented from live stock.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Section (Toggleable via Secret PIN: RESTORE-SUBSCRIPTION-PLANS) -->
    @if (config('subscription.plans_enabled', false))
        <section id="pricing" class="py-20 bg-slate-950">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-3xl mx-auto mb-16">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-orange-400 mb-2">{{ __('app.pricing') }}</h2>
                    <p class="text-3xl font-black text-white sm:text-4xl">Simple, Transparent Investment</p>
                    <p class="mt-3 text-slate-400 text-sm">{{ __('app.pricing_subtitle') }}</p>

                    <!-- Billing Cycle Toggle -->
                    <div class="mt-8 inline-flex items-center space-x-3 bg-slate-900 p-1.5 rounded-2xl border border-slate-800">
                        <button @click="billingAnnual = false"
                                :class="!billingAnnual ? 'bg-orange-600 text-white font-bold shadow' : 'text-slate-400 hover:text-white'"
                                class="px-4 py-2 rounded-xl text-xs sm:text-sm transition">
                            Monthly Billing
                        </button>
                        <button @click="billingAnnual = true"
                                :class="billingAnnual ? 'bg-orange-600 text-white font-bold shadow' : 'text-slate-400 hover:text-white'"
                                class="px-4 py-2 rounded-xl text-xs sm:text-sm transition flex items-center space-x-1.5">
                            <span>Yearly Billing</span>
                            <span class="bg-emerald-500 text-slate-950 text-[10px] font-bold px-1.5 py-0.5 rounded-full uppercase">Save 2 Months</span>
                        </button>
                    </div>
                </div>

                <!-- Dynamic Plans Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    @foreach ($plans as $plan)
                        <div class="rounded-3xl p-6 flex flex-col justify-between transition-all duration-300 {{ $plan->is_popular ? 'bg-gradient-to-b from-slate-900 to-slate-950 border-2 border-orange-500 shadow-xl shadow-orange-500/10 relative scale-105' : 'bg-slate-900/60 border border-slate-800 hover:border-slate-700' }}">

                            @if ($plan->is_popular)
                                <div class="absolute -top-3.5 left-1/2 -translate-x-1/2 bg-gradient-to-r from-orange-600 to-amber-500 text-white text-[10px] font-extrabold uppercase px-3 py-1 rounded-full tracking-wider shadow">
                                    Most Popular
                                </div>
                            @endif

                            <div>
                                <!-- Plan Name & Description -->
                                <div class="flex items-center justify-between">
                                    <h3 class="font-black text-xl text-white">{{ $plan->name }}</h3>
                                    <span class="text-[10px] font-mono text-slate-400 uppercase">{{ $plan->slug }}</span>
                                </div>
                                <p class="text-xs text-slate-400 mt-2 min-h-[32px]">{{ $plan->description }}</p>

                                <!-- Pricing Display -->
                                <div class="my-6">
                                    <div class="flex items-baseline space-x-1">
                                        <span class="text-3xl sm:text-4xl font-black font-mono text-white"
                                              x-text="billingAnnual ? '${{ number_format($plan->yearly_price / 12, 0) }}' : '${{ number_format($plan->monthly_price, 0) }}'">
                                        </span>
                                        <span class="text-xs text-slate-400">/ month</span>
                                    </div>
                                    <p class="text-[11px] text-slate-400 font-mono mt-1"
                                       x-text="'≈ ' + (billingAnnual ? '{{ number_format(($plan->yearly_price / 12) * 4100, 0) }}' : '{{ number_format($plan->monthly_price * 4100, 0) }}') + ' ៛ KHR'">
                                    </p>
                                </div>

                                <!-- Features List -->
                                <ul class="space-y-3 text-xs text-slate-300 border-t border-slate-800/80 pt-6">
                                    <li class="flex items-center space-x-2">
                                        <svg class="w-4 h-4 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        <span><strong>{{ $plan->product_limit >= 9999 ? 'Unlimited' : $plan->product_limit }}</strong> Products / Items</span>
                                    </li>
                                    <li class="flex items-center space-x-2">
                                        <svg class="w-4 h-4 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        <span><strong>{{ $plan->branch_limit }}</strong> Business Branch(es)</span>
                                    </li>
                                    <li class="flex items-center space-x-2">
                                        <svg class="w-4 h-4 text-emerald-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        <span><strong>{{ $plan->staff_limit }}</strong> Staff User Accounts</span>
                                    </li>
                                    <li class="flex items-center space-x-2">
                                        <svg class="w-4 h-4 {{ $plan->has_pos ? 'text-emerald-400' : 'text-slate-600' }} shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            @if ($plan->has_pos)
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            @else
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            @endif
                                        </svg>
                                        <span class="{{ $plan->has_pos ? '' : 'text-slate-500 line-through' }}">Touch POS Terminal</span>
                                    </li>
                                    <li class="flex items-center space-x-2">
                                        <svg class="w-4 h-4 {{ $plan->has_inventory ? 'text-emerald-400' : 'text-slate-600' }} shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            @if ($plan->has_inventory)
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            @else
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            @endif
                                        </svg>
                                        <span class="{{ $plan->has_inventory ? '' : 'text-slate-500 line-through' }}">Recipe & Inventory Tracking</span>
                                    </li>
                                    <li class="flex items-center space-x-2">
                                        <svg class="w-4 h-4 {{ $plan->has_analytics ? 'text-emerald-400' : 'text-slate-600' }} shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            @if ($plan->has_analytics)
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            @else
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            @endif
                                        </svg>
                                        <span class="{{ $plan->has_analytics ? '' : 'text-slate-500 line-through' }}">Financial Analytics & Export</span>
                                    </li>
                                </ul>
                            </div>

                            <!-- CTA Button -->
                            <div class="mt-8">
                                <a href="{{ route('register') }}"
                                   class="w-full py-3 px-4 rounded-xl font-bold text-xs uppercase tracking-wider flex items-center justify-center transition shadow {{ $plan->is_popular ? 'bg-orange-600 hover:bg-orange-500 text-white shadow-orange-600/30' : 'bg-slate-800 hover:bg-slate-700 text-slate-200' }}">
                                    Get Started
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @else
        <!-- 100% Free Community Edition Section -->
        <section id="free-access" class="py-20 bg-gradient-to-b from-slate-950 via-slate-900 to-slate-950 relative overflow-hidden">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top,_var(--tw-gradient-stops))] from-orange-500/10 via-transparent to-transparent pointer-events-none"></div>
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                <div class="text-center max-w-3xl mx-auto mb-16">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 mb-4">
                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        100% Free for Everyone &bull; No Subscription Fees
                    </span>
                    <h2 class="text-3xl sm:text-5xl font-black text-white tracking-tight">
                        Empowering Every Business in Cambodia
                    </h2>
                    <p class="mt-4 text-base sm:text-lg text-slate-300 leading-relaxed">
                        SABAYRA is completely free to use. No monthly subscription fees, no credit card required, and no hidden charges. Every merchant gets unlimited access to all platform features.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 hover:border-orange-500/50 transition duration-300">
                        <div class="w-12 h-12 rounded-2xl bg-orange-500/10 text-orange-400 flex items-center justify-center text-2xl mb-4">
                            🍽️
                        </div>
                        <div class="text-emerald-400 font-bold text-xs uppercase tracking-wider mb-1">100% Free</div>
                        <h3 class="text-lg font-black text-white mb-2">Unlimited Products & Menus</h3>
                        <p class="text-xs text-slate-400 leading-relaxed">
                            Create unlimited categories, dishes, drink variants, modifier addons, and dual USD/KHR pricing with zero restrictions.
                        </p>
                    </div>

                    <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 hover:border-orange-500/50 transition duration-300">
                        <div class="w-12 h-12 rounded-2xl bg-orange-500/10 text-orange-400 flex items-center justify-center text-2xl mb-4">
                            🖥️
                        </div>
                        <div class="text-emerald-400 font-bold text-xs uppercase tracking-wider mb-1">100% Free</div>
                        <h3 class="text-lg font-black text-white mb-2">Touch POS & Kitchen Display</h3>
                        <p class="text-xs text-slate-400 leading-relaxed">
                            Full-featured fast checkout POS terminal with 80mm receipt printing and real-time live Kitchen Display System (KDS).
                        </p>
                    </div>

                    <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 hover:border-orange-500/50 transition duration-300">
                        <div class="w-12 h-12 rounded-2xl bg-orange-500/10 text-orange-400 flex items-center justify-center text-2xl mb-4">
                            📱
                        </div>
                        <div class="text-emerald-400 font-bold text-xs uppercase tracking-wider mb-1">100% Free</div>
                        <h3 class="text-lg font-black text-white mb-2">Table QRs & Bakong KHQR</h3>
                        <p class="text-xs text-slate-400 leading-relaxed">
                            Printable standee QRs for each dining table and official National Bank of Cambodia EMVCo KHQR payment integration.
                        </p>
                    </div>

                    <div class="bg-slate-900/80 border border-slate-800 rounded-3xl p-6 hover:border-orange-500/50 transition duration-300">
                        <div class="w-12 h-12 rounded-2xl bg-orange-500/10 text-orange-400 flex items-center justify-center text-2xl mb-4">
                            🏪
                        </div>
                        <div class="text-emerald-400 font-bold text-xs uppercase tracking-wider mb-1">100% Free</div>
                        <h3 class="text-lg font-black text-white mb-2">Multi-Branch & Team Roles</h3>
                        <p class="text-xs text-slate-400 leading-relaxed">
                            Manage multiple outlets and invite unlimited managers, cashiers, waitstaff, and inventory operators without paying extra.
                        </p>
                    </div>
                </div>

                <div class="mt-12 text-center">
                    <a href="{{ route('register') }}"
                       class="inline-flex items-center gap-2 px-8 py-4 rounded-2xl font-black text-sm uppercase tracking-wider bg-orange-600 hover:bg-orange-500 text-white shadow-xl shadow-orange-600/30 transition transform hover:-translate-y-0.5">
                        <span>Get Started 100% Free — No Credit Card Required</span>
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
                    </a>
                </div>
            </div>
        </section>
    @endif

    <!-- Frequently Asked Questions -->
    <section id="faq" class="py-20 bg-slate-900/40 border-t border-slate-800">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-xs font-bold uppercase tracking-wider text-orange-400 mb-2">Got Questions?</h2>
                <p class="text-3xl font-black text-white sm:text-4xl">Frequently Asked Questions</p>
            </div>

            <div class="space-y-4" x-data="{ openFaq: null }">
                <!-- Q1 -->
                <div class="rounded-2xl bg-slate-900 border border-slate-800 p-5 cursor-pointer"
                     @click="openFaq = openFaq === 1 ? null : 1">
                    <div class="flex items-center justify-between">
                        <h4 class="font-bold text-white text-sm sm:text-base">Do I need special hardware to run SABAYRA POS?</h4>
                        <span class="text-orange-400 font-bold text-lg" x-text="openFaq === 1 ? '−' : '+'"></span>
                    </div>
                    <div x-show="openFaq === 1" x-cloak class="mt-3 text-xs sm:text-sm text-slate-400 leading-relaxed border-t border-slate-800/80 pt-3">
                        No! SABAYRA works seamlessly on any browser on your iPad, Android tablet, smartphone, laptop, or desktop computer. You can also connect standard ESC/POS 80mm thermal receipt printers via browser or network.
                    </div>
                </div>

                <!-- Q2 -->
                <div class="rounded-2xl bg-slate-900 border border-slate-800 p-5 cursor-pointer"
                     @click="openFaq = openFaq === 2 ? null : 2">
                    <div class="flex items-center justify-between">
                        <h4 class="font-bold text-white text-sm sm:text-base">How does National Bank of Cambodia Bakong KHQR work?</h4>
                        <span class="text-orange-400 font-bold text-lg" x-text="openFaq === 2 ? '−' : '+'"></span>
                    </div>
                    <div x-show="openFaq === 2" x-cloak class="mt-3 text-xs sm:text-sm text-slate-400 leading-relaxed border-t border-slate-800/80 pt-3">
                        SABAYRA incorporates the official National Bank of Cambodia EMVCo KHQR standard. For each order or POS checkout, a dynamic QR is generated with the exact bill amount in USD or KHR. Customers scan and pay with any Cambodian bank app (ABA Bank, ACLEDA, Wing, Chip Mong, etc.).
                    </div>
                </div>

                <!-- Q3 -->
                <div class="rounded-2xl bg-slate-900 border border-slate-800 p-5 cursor-pointer"
                     @click="openFaq = openFaq === 3 ? null : 3">
                    <div class="flex items-center justify-between">
                        <h4 class="font-bold text-white text-sm sm:text-base">Can I manage multiple branches under one merchant account?</h4>
                        <span class="text-orange-400 font-bold text-lg" x-text="openFaq === 3 ? '−' : '+'"></span>
                    </div>
                    <div x-show="openFaq === 3" x-cloak class="mt-3 text-xs sm:text-sm text-slate-400 leading-relaxed border-t border-slate-800/80 pt-3">
                        Yes! With our Pro and Business plans, you can add multiple branches (e.g., Phnom Penh Main Branch, BKK Branch, Toul Kork Branch) and toggle between them smoothly from your merchant workspace.
                    </div>
                </div>

                <!-- Q4 -->
                <div class="rounded-2xl bg-slate-900 border border-slate-800 p-5 cursor-pointer"
                     @click="openFaq = openFaq === 4 ? null : 4">
                    <div class="flex items-center justify-between">
                        <h4 class="font-bold text-white text-sm sm:text-base">Is there full Khmer language support?</h4>
                        <span class="text-orange-400 font-bold text-lg" x-text="openFaq === 4 ? '−' : '+'"></span>
                    </div>
                    <div x-show="openFaq === 4" x-cloak class="mt-3 text-xs sm:text-sm text-slate-400 leading-relaxed border-t border-slate-800/80 pt-3">
                        Absolutely. Both customer-facing digital menus and merchant operations (POS, KDS, Orders, Inventory, Reports) can toggle effortlessly between English and Khmer (ភាសាខ្មែរ).
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Bottom CTA Banner -->
    <section class="py-20 bg-gradient-to-tr from-orange-950/40 via-slate-950 to-amber-950/20 border-t border-slate-800 text-center relative overflow-hidden">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl sm:text-5xl font-black text-white">Ready to elevate your business?</h2>
            <p class="mt-4 text-slate-300 text-base max-w-xl mx-auto">
                Join hundreds of forward-thinking merchants across Cambodia who trust SABAYRA for their day-to-day operations.
            </p>
            <div class="mt-8 flex flex-col sm:flex-row items-center justify-center gap-4">
                <a href="{{ route('register') }}"
                   class="w-full sm:w-auto px-8 py-4 rounded-2xl bg-gradient-to-r from-orange-600 to-amber-500 hover:from-orange-500 hover:to-amber-400 text-white font-black text-base shadow-xl shadow-orange-600/30 transition active:scale-95">
                    Start Your Free Trial Now
                </a>
                <a href="{{ route('login') }}"
                   class="w-full sm:w-auto px-8 py-4 rounded-2xl bg-slate-900 hover:bg-slate-800 text-slate-300 border border-slate-700 font-bold text-base transition">
                    Merchant Login
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-slate-950 border-t border-slate-900 py-12 text-slate-500 text-xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row items-center justify-between gap-6">
            <div class="flex items-center space-x-3">
                <img src="{{ asset('images/sabayra-icon.png') }}" alt="SABAYRA" class="w-9 h-9 rounded-xl object-contain shadow-md shadow-orange-600/30" />
                <div>
                    <div class="flex items-center space-x-2">
                        <span class="font-black text-white text-sm">SABAYRA</span>
                        <span class="text-[9px] uppercase font-bold tracking-widest px-1.5 py-0.5 rounded bg-orange-500/20 text-orange-400 border border-orange-500/30">CAMBODIA</span>
                    </div>
                    <p class="text-[10px] text-slate-500">Phnom Penh, Cambodia &bull; Asia/Phnom_Penh</p>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-center gap-6 text-slate-400 font-semibold">
                <a href="#features" class="hover:text-white transition">Features</a>
                <a href="#businesses" class="hover:text-white transition">Business Types</a>
                @if (config('subscription.plans_enabled', false))
                    <a href="#pricing" class="hover:text-white transition">Pricing</a>
                @else
                    <a href="#free-access" class="hover:text-white transition text-emerald-400 font-bold">100% Free Access</a>
                @endif
                <a href="{{ route('login') }}" class="hover:text-white transition">Merchant Portal</a>
                <a href="{{ route('register') }}" class="hover:text-white transition">Register</a>
            </div>

            <div>
                <p>&copy; {{ date('Y') }} SABAYRA Cambodia. All rights reserved.</p>
            </div>
        </div>
    </footer>

</body>
</html>
