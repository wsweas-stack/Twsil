<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'Tawsili'))</title>
    {{ \Illuminate\Support\Facades\Vite::fonts() }}
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""/>
    @vite(['resources/css/app.css', 'resources/css/landing.css', 'resources/js/app.js', 'resources/js/landing.js'])
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    @include('components.ai-assistant-assets')
</head>
<body class="{{ request()->routeIs('home') ? 'home-page' : '' }}">
    {{-- Header Navigation (Exact Match to User Mockup) --}}
    <header class="tawsili-header">
        <div class="header-container">
            {{-- Brand Logo --}}
            <a class="header-brand" href="{{ route('home') }}" aria-label="{{ __('app.brand.name') }}">
                <img src="{{ asset('images/' . (app()->getLocale() === 'ar' ? 'tawsili-brand-logo.svg' : 'tawsili-brand-logo-en.svg')) }}" alt="{{ app()->getLocale() === 'ar' ? 'توصيلي - منصة إدارة الشحنات' : 'Tawsili - Delivery & Shipment Management Platform' }}" width="136" height="40">
            </a>

            {{-- Main Navigation Links --}}
            <nav class="header-nav-wrapper" aria-label="{{ __('app.nav.primary') }}">
                <ul class="header-nav">
                    <li><a class="{{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}#hero">{{ app()->getLocale() === 'ar' ? 'الرئيسية' : 'Home' }}</a></li>
                    <li><a href="{{ route('home') }}#services">{{ app()->getLocale() === 'ar' ? 'خدماتنا' : 'Services' }}</a></li>
                    <li><a href="{{ route('home') }}#how-it-works">{{ app()->getLocale() === 'ar' ? 'كيف نعمل؟' : 'How It Works' }}</a></li>
                    @auth
                        <li><a href="{{ route('tracking.form') }}">{{ __('app.nav.track') }}</a></li>
                    @endauth
                    <li><a href="{{ route('home') }}#reviews">{{ app()->getLocale() === 'ar' ? 'التقييمات' : 'Reviews' }}</a></li>
                    <li><a href="{{ route('home') }}#contact">{{ app()->getLocale() === 'ar' ? 'تواصل معنا' : 'Contact' }}</a></li>
                </ul>
            </nav>

            {{-- Actions: Sign In / Dashboard & Language Pill --}}
            <div class="header-actions">
                {{-- Sign In / Dashboard Pill Button --}}
                @guest
                    <a class="btn-header-login" href="{{ route('login') }}" aria-label="{{ app()->getLocale() === 'ar' ? 'دخول الحساب' : 'Sign In' }}">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
                        </svg>
                        <span>{{ app()->getLocale() === 'ar' ? 'دخول الحساب' : 'Sign In' }}</span>
                    </a>
                @else
                    @php
                        $headerUser = auth()->user();
                        $headerIsStaff = $headerUser->hasAnyRole([
                            \App\Enums\UserRoles::Driver->value,
                            \App\Enums\UserRoles::Employee->value,
                        ]);
                        $headerIsAdmin = $headerUser->hasRole(\App\Enums\UserRoles::Admin->value);
                        $headerDashboardUrl = $headerIsAdmin
                            || ($headerIsStaff && $headerUser->approval_status === \App\Enums\ApprovalStatus::Approved)
                            ? url('/admin')
                            : ($headerIsStaff
                                ? route('approval.status')
                                : route('account.dashboard'));
                        $headerDashboardLabel = $headerIsStaff
                            && $headerUser->approval_status !== \App\Enums\ApprovalStatus::Approved
                            ? __('app.auth.approval_title')
                            : __('app.dashboard.title');
                    @endphp
                    <a class="btn-header-login" href="{{ $headerDashboardUrl }}" aria-label="{{ $headerDashboardLabel }}">
                        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
                        </svg>
                        <span>{{ $headerDashboardLabel }}</span>
                    </a>
                @endguest

                {{-- Language Toggle Pill --}}
                <form method="post" action="{{ route('language.update') }}" class="lang-form-inline">
                    @csrf
                    <input type="hidden" name="locale" value="{{ app()->getLocale() === 'ar' ? 'en' : 'ar' }}">
                    <button class="btn-lang-pill" type="submit" aria-label="Toggle language">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/>
                            <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                        </svg>
                        <span>{{ app()->getLocale() === 'ar' ? 'EN' : 'عربي' }}</span>
                    </button>
                </form>

                @auth
                    <form method="post" action="{{ route('logout') }}" class="logout-form-inline">
                        @csrf
                        <button class="btn-header-logout" type="submit" aria-label="{{ __('app.auth.logout') }}" title="{{ __('app.auth.logout') }}">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M10 17l5-5-5-5"/>
                                <path d="M15 12H3"/>
                                <path d="M12 3h7a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-7"/>
                            </svg>
                        </button>
                    </form>
                @endauth

                {{-- Mobile Navigation Toggle --}}
                <details class="mobile-menu">
                    <summary aria-label="{{ app()->getLocale() === 'ar' ? 'القائمة' : 'Menu' }}" aria-controls="primary-mobile-navigation">
                        <svg class="mobile-menu-burger-icon" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
                        </svg>
                        <svg class="mobile-menu-close-icon" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                        </svg>
                    </summary>
                    <nav id="primary-mobile-navigation" class="mobile-menu-panel" aria-label="{{ __('app.nav.primary') }}">
                        <a href="{{ route('home') }}#hero">{{ app()->getLocale() === 'ar' ? 'الرئيسية' : 'Home' }}</a>
                        <a href="{{ route('home') }}#services">{{ app()->getLocale() === 'ar' ? 'خدماتنا' : 'Services' }}</a>
                        <a href="{{ route('home') }}#how-it-works">{{ app()->getLocale() === 'ar' ? 'كيف نعمل؟' : 'How It Works' }}</a>
                        @auth
                            <a href="{{ route('tracking.form') }}">{{ __('app.nav.track') }}</a>
                        @endauth
                        <a href="{{ route('home') }}#reviews">{{ app()->getLocale() === 'ar' ? 'التقييمات' : 'Reviews' }}</a>
                        <a href="{{ route('home') }}#why-us">{{ app()->getLocale() === 'ar' ? 'لماذا توصيلي؟' : 'Why Us' }}</a>
                        <a href="{{ route('help') }}">{{ app()->getLocale() === 'ar' ? 'المساعدة' : 'Help' }}</a>
                        <a href="{{ route('home') }}#contact">{{ app()->getLocale() === 'ar' ? 'تواصل معنا' : 'Contact' }}</a>
                        @auth
                            <a class="mobile-menu-cta" href="{{ route('account.shipments.create') }}">{{ app()->getLocale() === 'ar' ? 'شحنة جديدة' : 'New Shipment' }}</a>
                        @endauth
                    </nav>
                </details>
            </div>
        </div>
    </header>

    {{-- Dimmed backdrop behind the open mobile menu --}}
    <div class="mobile-menu-backdrop" aria-hidden="true"></div>

    {{-- Main Content --}}
    <main class="page-main{{ request()->routeIs('home') ? ' page-main-home' : '' }}">
        @yield('content')
    </main>

    {{-- Exact Dark Modern Footer from Mockup --}}
    <footer class="tawsili-footer">
        <div class="footer-container">
            {{-- Brand Column (Right in RTL, Left in LTR) --}}
            <div class="footer-brand-col">
                <a href="{{ route('home') }}" class="footer-brand-link">
                    <img src="{{ asset('images/' . (app()->getLocale() === 'ar' ? 'tawsili-brand-logo-white.svg' : 'tawsili-brand-logo-white-en.svg')) }}" alt="{{ app()->getLocale() === 'ar' ? 'توصيلي' : 'Tawsili' }}" width="136" height="40">
                </a>
                <p class="footer-tagline">{{ app()->getLocale() === 'ar' ? 'منصة إدارة الشحنات وخدمات التوصيل' : 'Shipment management and delivery services platform' }}</p>
            </div>

            {{-- Quick Links --}}
            <div class="footer-links-col">
                <h3 class="footer-col-title">{{ app()->getLocale() === 'ar' ? 'روابط سريعة' : 'Quick Links' }}</h3>
                <ul class="footer-links-list">
                    <li><a href="{{ route('home') }}#hero">{{ app()->getLocale() === 'ar' ? 'الرئيسية' : 'Home' }}</a></li>
                    <li><a href="{{ route('home') }}#services">{{ app()->getLocale() === 'ar' ? 'خدماتنا' : 'Services' }}</a></li>
                    <li><a href="{{ route('home') }}#how-it-works">{{ app()->getLocale() === 'ar' ? 'كيف نعمل؟' : 'How It Works' }}</a></li>
                    <li><a href="{{ route('home') }}#contact">{{ app()->getLocale() === 'ar' ? 'تواصل معنا' : 'Contact' }}</a></li>
                </ul>
            </div>

            {{-- Customer Care Links --}}
            <div class="footer-links-col">
                <h3 class="footer-col-title">{{ app()->getLocale() === 'ar' ? 'خدمات العملاء' : 'Customer Care' }}</h3>
                <ul class="footer-links-list">
                    @auth
                        <li><a href="{{ route('tracking.form') }}">{{ __('app.footer.cta') }}</a></li>
                    @endauth
                    <li><a href="{{ route('help') }}">{{ app()->getLocale() === 'ar' ? 'الأسئلة الشائعة' : 'FAQ' }}</a></li>
                    <li><a href="{{ route('contact') }}">{{ app()->getLocale() === 'ar' ? 'الدعم الفني' : 'Technical Support' }}</a></li>
                    <li><a href="{{ route('reviews.index') }}">{{ app()->getLocale() === 'ar' ? 'آراء العملاء' : 'Customer Reviews' }}</a></li>
                </ul>
            </div>
        </div>

        {{-- Bottom Copyright Line with Language Switch Segment --}}
        <div class="footer-bottom-line">
            <div class="footer-bottom-container">
                <p class="footer-copy-text">
                    {{ app()->getLocale() === 'ar' ? 'جميع الحقوق محفوظة © توصيلي' : 'All rights reserved © Tawsili ' . date('Y') }}
                </p>
                
                {{-- Segmented Language Switcher --}}
                <form method="post" action="{{ route('language.update') }}" class="footer-lang-form">
                    @csrf
                    <div class="footer-lang-switch">
                        <button type="submit" name="locale" value="ar" class="lang-segment {{ app()->getLocale() === 'ar' ? 'active' : '' }}">عربي</button>
                        <button type="submit" name="locale" value="en" class="lang-segment {{ app()->getLocale() === 'en' ? 'active' : '' }}">EN</button>
                    </div>
                </form>
            </div>
        </div>
    </footer>

    {{-- AI Assistant (Clean floating widget) --}}
    @unless (request()->routeIs('assistant.show', 'contact'))
        @include('components.ai-assistant')
    @endunless
</body>
</html>
