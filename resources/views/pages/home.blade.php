@extends('layouts.public')

@section('title', app()->getLocale() === 'ar' ? 'توصيلي | منصة إدارة الشحنات وخدمات التوصيل' : 'Tawsili | Delivery Operations & Logistics Platform')

@section('content')
    {{-- ═══════════════════════════════════════════════════════════════════════
         1. HERO SECTION (Clean, Bright, Daylight, Real Van Visual)
    ═══════════════════════════════════════════════════════════════════════ --}}
    <section id="hero" class="hero-section" aria-labelledby="hero-title">
        <div class="hero-container">
            {{-- Copy Column (Right in RTL, Left in LTR) --}}
            <div class="hero-copy">
                <div class="hero-kicker">
                    <span class="kicker-dash" aria-hidden="true"></span>
                    <span class="kicker-text">{{ app()->getLocale() === 'ar' ? 'خدمات الشحن والتوصيل' : 'Courier & Logistics Services' }}</span>
                </div>
                
                <h1 id="hero-title" class="hero-title">
                    @if (app()->getLocale() === 'ar')
                        شحناتك تصل ..<br>
                        <span class="text-brand">بأمان وسرعة</span>
                    @else
                        Your shipments arrive<br>
                        <span class="text-brand">safely and on time</span>
                    @endif
                </h1>

                <p class="hero-desc">
                    {{ app()->getLocale() === 'ar'
                        ? 'مع توصيلي، أصبح إرسال واستقبال الشحنات أسهل وأكثر تنظيماً بمتابعة ذكية لحظة بلحظة.'
                        : 'With Tawsili, sending and receiving shipments has become easier, smarter, and more organized.' }}
                </p>

                <div class="hero-actions">
                    @guest
                        <a class="btn-hero-primary" href="{{ route('register') }}">
                            <span>{{ app()->getLocale() === 'ar' ? 'ابدأ الآن' : 'Start Now' }}</span>
                            <svg class="btn-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
                            </svg>
                        </a>
                    @else
                        <a class="btn-hero-primary" href="{{ route('account.shipments.create') }}">
                            <span>{{ app()->getLocale() === 'ar' ? 'ابدأ شحنة جديدة' : 'New Shipment' }}</span>
                            <svg class="btn-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
                            </svg>
                        </a>
                    @endguest

                    <a class="btn-hero-secondary" href="#how-it-works">
                        <svg class="btn-play-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="12" cy="12" r="10"/>
                            <polygon points="10 8 16 12 10 16 10 8" fill="currentColor"/>
                        </svg>
                        <span>{{ app()->getLocale() === 'ar' ? 'معرفة المزيد' : 'Learn More' }}</span>
                    </a>
                </div>
            </div>

            {{-- Visual Column: Tawsili home delivery --}}
            <div class="hero-visual">
                <div class="van-wrapper">
                    <img id="hero-van-img" class="van-image" src="{{ asset('images/homepage-delivery.jpg') }}" alt="{{ app()->getLocale() === 'ar' ? 'مندوب توصيلي يسلّم شحنة إلى العميل' : 'Tawsili courier handing a shipment to a customer' }}" fetchpriority="high">
                </div>
            </div>
        </div>
    </section>

    {{-- ═══════════════════════════════════════════════════════════════════════
         2. FLOATING TRACKING COMMAND BAR (Exact Pill Overlap)
    ═══════════════════════════════════════════════════════════════════════ --}}
    <section id="tracking" class="tracking-pill-section" aria-label="{{ __('app.tracking.title') }}">
        <div class="tracking-pill-container">
            <div class="tracking-pill-card">
                {{-- Info Block --}}
                <div class="tracking-pill-info">
                    <div class="track-pin-circle">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="#D71920" stroke="#D71920" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3" fill="#ffffff" stroke="none"/>
                        </svg>
                    </div>
                    <div class="track-texts">
                        <strong class="track-title">{{ app()->getLocale() === 'ar' ? 'أين وصلت شحنتك؟' : 'Where is your shipment?' }}</strong>
                        <span class="track-subtitle">{{ app()->getLocale() === 'ar' ? 'أدخل رقم التتبع لمعرفة آخر تحديث لشحنتك' : 'Enter tracking number to check the latest update' }}</span>
                    </div>
                </div>

                {{-- Form Block --}}
                <form class="tracking-pill-form" action="{{ route('tracking.lookup') }}" method="post">
                    @csrf
                    <div class="track-input-wrap">
                        <label class="sr-only" for="tracking_number">{{ app()->getLocale() === 'ar' ? 'رقم التتبع' : 'Tracking Number' }}</label>
                        <svg class="search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#94A3B8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                        </svg>
                        <input class="track-input" id="tracking_number" name="tracking_number" value="{{ old('tracking_number') }}" placeholder="TW-XXXXXXX" autocomplete="off" required>
                    </div>

                    <button class="btn-track-submit" type="submit">
                        <span>{{ app()->getLocale() === 'ar' ? 'تتبع الشحنة' : 'Track' }}</span>
                        <svg class="btn-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
                        </svg>
                    </button>
                </form>
            </div>

            @error('tracking_number')
                <p class="tracking-error-alert" role="alert">{{ $message }}</p>
            @enderror
        </div>
    </section>

    {{-- ═══════════════════════════════════════════════════════════════════════
         3. SERVICES SECTION WITH HIGH-RESOLUTION LOGISTICS PHOTOGRAPHY
    ═══════════════════════════════════════════════════════════════════════ --}}
    <section id="services" class="services-section" aria-labelledby="services-title">
        <div class="section-container">
            <div class="section-header">
                <div class="section-badge">
                    <span class="badge-dash" aria-hidden="true"></span>
                    <h2 id="services-title" class="badge-title">{{ app()->getLocale() === 'ar' ? 'خدماتنا' : 'Our Services' }}</h2>
                    <span class="badge-dash" aria-hidden="true"></span>
                </div>
                <p class="section-subtitle">
                    {{ app()->getLocale() === 'ar' ? 'مجموعة متكاملة من خدمات الشحن السريع والحلول اللوجستية المتقدمة' : 'A complete suite of express delivery and advanced logistics solutions' }}
                </p>
            </div>

            <div class="modern-services-grid">
                {{-- Service 1: Express Fleet Delivery --}}
                <article class="modern-service-card">
                    <div class="service-media">
                        <img src="https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?auto=format&fit=crop&w=800&q=80" alt="{{ app()->getLocale() === 'ar' ? 'أسطول التوصيل السريع' : 'Express Delivery Fleet' }}" loading="lazy">
                        <span class="service-tag">{{ app()->getLocale() === 'ar' ? 'شحن فوري' : 'Express' }}</span>
                    </div>
                    <div class="service-body">
                        <div class="service-icon-wrap">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#D71920" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <rect x="1" y="3" width="15" height="13"/>
                                <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/>
                                <circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>
                            </svg>
                        </div>
                        <h3 class="service-card-title">{{ app()->getLocale() === 'ar' ? 'التوصيل السريع للمدن' : 'Express City Delivery' }}</h3>
                        <p class="service-card-desc">{{ app()->getLocale() === 'ar' ? 'أسطول متكامل وسائقون معتمدون لنقل شحناتك بين المدن بأمان وسرعة قياسية.' : 'A dedicated fleet ensuring fast, same-day and inter-city parcel deliveries safely.' }}</p>
                    </div>
                </article>

                {{-- Service 2: Smart Warehousing & Sorting --}}
                <article class="modern-service-card">
                    <div class="service-media">
                        <img src="https://images.unsplash.com/photo-1553413077-190dd305871c?auto=format&fit=crop&w=800&q=80" alt="{{ app()->getLocale() === 'ar' ? 'المستودعات والفرز الآلي' : 'Smart Warehousing & Sorting' }}" loading="lazy">
                        <span class="service-tag">{{ app()->getLocale() === 'ar' ? 'فرز آلي' : 'Automated' }}</span>
                    </div>
                    <div class="service-body">
                        <div class="service-icon-wrap">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#D71920" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                                <polyline points="3.27 6.96 12 12.01 20.73 6.96"/>
                                <line x1="12" y1="22.08" x2="12" y2="12"/>
                            </svg>
                        </div>
                        <h3 class="service-card-title">{{ app()->getLocale() === 'ar' ? 'مراكز فرز وتخزين متطورة' : 'Advanced Fulfillment Centers' }}</h3>
                        <p class="service-card-desc">{{ app()->getLocale() === 'ar' ? 'مستودعات حديثة مجهزة بأنظمة مسح وتجهيز بوليصات رقمية لضمان عدم تأخير أي طرد.' : 'State-of-the-art logistics hubs with digital waybill printing and secure parcel handling.' }}</p>
                    </div>
                </article>

                {{-- Service 3: Live Barcode Tracking & OTP Verification --}}
                <article class="modern-service-card">
                    <div class="service-media">
                        <img src="https://images.unsplash.com/photo-1580674684081-7617fbf3d745?auto=format&fit=crop&w=800&q=80" alt="{{ app()->getLocale() === 'ar' ? 'تتبع الشحنات وإثبات التسليم' : 'Live Tracking & OTP Verification' }}" loading="lazy">
                        <span class="service-tag">{{ app()->getLocale() === 'ar' ? 'تتبع مباشر' : 'Live Tracking' }}</span>
                    </div>
                    <div class="service-body">
                        <div class="service-icon-wrap">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#D71920" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M12 2a8 8 0 0 0-8 8c0 5.25 8 12 8 12s8-6.75 8-12a8 8 0 0 0-8-8z"/>
                                <circle cx="12" cy="10" r="3"/>
                            </svg>
                        </div>
                        <h3 class="service-card-title">{{ app()->getLocale() === 'ar' ? 'تتبع حي وتحقق آمن برمز OTP' : 'Live Tracking & OTP Security' }}</h3>
                        <p class="service-card-desc">{{ app()->getLocale() === 'ar' ? 'تابع حركة شحنتك خطوة بخطوة، مع تأكيد الاستلام النهائي برمز تحقق رقمي آمن.' : 'Real-time GPS status updates and secure recipient 4-digit OTP handover confirmation.' }}</p>
                    </div>
                </article>

                {{-- Service 4: Business Logistics & Bulk Freight --}}
                <article class="modern-service-card">
                    <div class="service-media">
                        <img src="https://images.unsplash.com/photo-1578575437130-527eed3abbec?auto=format&fit=crop&w=800&q=80" alt="{{ app()->getLocale() === 'ar' ? 'الشحن التجاري واللوجستي' : 'Commercial Freight & Logistics' }}" loading="lazy">
                        <span class="service-tag">{{ app()->getLocale() === 'ar' ? 'قطاع الأعمال' : 'Business' }}</span>
                    </div>
                    <div class="service-body">
                        <div class="service-icon-wrap">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#D71920" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M3 18v-6a9 9 0 0 1 18 0v6"/>
                                <path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"/>
                            </svg>
                        </div>
                        <h3 class="service-card-title">{{ app()->getLocale() === 'ar' ? 'حلول لوجستية متكاملة للشركات' : 'Enterprise Logistics Solutions' }}</h3>
                        <p class="service-card-desc">{{ app()->getLocale() === 'ar' ? 'إدارة الشحنات المتكررة، فواتير إلكترونية معتمدة، ودعم فني مخصص.' : 'Dedicated corporate accounts with bulk shipping tools, custom reporting, and dedicated support.' }}</p>
                    </div>
                </article>
            </div>
        </div>
    </section>

    {{-- ═══════════════════════════════════════════════════════════════════════
         4. ELEVATED STATISTICS SECTION (Prestigious Dark Navy Band — #stats)
    ═══════════════════════════════════════════════════════════════════════ --}}
    <section id="stats" class="dark-stats-band" aria-labelledby="stats-heading">
        <div class="section-container">
            <div class="dark-stats-header">
                <span class="dark-stats-kicker">{{ app()->getLocale() === 'ar' ? 'إنجازات وأرقام حقيقية' : 'Verified Live Metrics' }}</span>
                <h2 id="stats-heading" class="dark-stats-title">{{ app()->getLocale() === 'ar' ? 'مؤشرات تشغيلية حية من منصة توصيلي' : 'Live operational metrics from Tawsili' }}</h2>
                <p class="dark-stats-sub">{{ app()->getLocale() === 'ar' ? 'بيانات حية ومباشرة من نظام إدارة الشحنات وعمليات التوصيل الموثقة.' : 'Live operational data updated in real-time from our platform database.' }}</p>
            </div>

            <div class="dark-stats-grid">
                {{-- Stat 1: Delivered --}}
                <div class="dark-stat-card">
                    <div class="stat-icon-badge">
                        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#D71920" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                    </div>
                    <strong class="stat-counter" data-target="{{ $deliveredCount }}">{{ number_format($deliveredCount) }}</strong>
                    <h3 class="stat-card-title">{{ app()->getLocale() === 'ar' ? 'شحنة مُسلَّمة بنجاح' : 'Delivered Shipments' }}</h3>
                    <p class="stat-card-desc">{{ app()->getLocale() === 'ar' ? 'تم تسليمها للمستلم برمز التحقق OTP' : 'Verified final handovers with secure OTP' }}</p>
                </div>

                {{-- Stat 2: Cities Covered --}}
                <div class="dark-stat-card">
                    <div class="stat-icon-badge">
                        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#D71920" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>
                        </svg>
                    </div>
                    <strong class="stat-counter" data-target="{{ $cityCount }}">{{ number_format($cityCount) }}</strong>
                    <h3 class="stat-card-title">{{ app()->getLocale() === 'ar' ? 'مدينة ومحافظة مغطاة' : 'Cities Covered' }}</h3>
                    <p class="stat-card-desc">{{ app()->getLocale() === 'ar' ? 'شبكة توصيل واسعة تغطي كل الوجهات' : 'Extensive network reaching all major destinations' }}</p>
                </div>

                {{-- Stat 3: Total Managed Shipments --}}
                <div class="dark-stat-card">
                    <div class="stat-icon-badge">
                        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#D71920" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect x="2" y="7" width="20" height="14" rx="2" ry="2"/>
                            <path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/>
                        </svg>
                    </div>
                    <strong class="stat-counter" data-target="{{ $shipmentCount }}">{{ number_format($shipmentCount) }}</strong>
                    <h3 class="stat-card-title">{{ app()->getLocale() === 'ar' ? 'إجمالي الشحنات المدارة' : 'Total Shipments' }}</h3>
                    <p class="stat-card-desc">{{ app()->getLocale() === 'ar' ? 'تتبع حي وإدارة شاملة من الإنشاء' : 'End-to-end tracked parcels from creation' }}</p>
                </div>

                {{-- Stat 4: Reviews & Rating --}}
                <div class="dark-stat-card">
                    <div class="stat-icon-badge">
                        <svg width="26" height="26" viewBox="0 0 24 24" fill="#F59E0B" stroke="#F59E0B" stroke-width="1" aria-hidden="true">
                            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                        </svg>
                    </div>
                    <strong class="stat-counter" data-target="{{ $reviewCount }}">{{ number_format($reviewCount) }}</strong>
                    <h3 class="stat-card-title">{{ app()->getLocale() === 'ar' ? 'تقييم معتمد من العملاء' : 'Verified Reviews' }}</h3>
                    <p class="stat-card-desc">
                        @if ($reviewCount > 0)
                            <span class="gold-badge">{{ number_format($averageRating, 1) }} ★</span>
                            {{ app()->getLocale() === 'ar' ? 'متوسط التقييمات المعتمدة' : 'Average approved rating' }}
                        @else
                            {{ __('app.home.no_reviews') }}
                        @endif
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- ═══════════════════════════════════════════════════════════════════════
         5. HOW IT WORKS ("كيف تعمل توصيلي؟" — 4 Step Flow)
    ═══════════════════════════════════════════════════════════════════════ --}}
    <section id="how-it-works" class="how-it-works-section" aria-labelledby="how-title">
        <div class="section-container">
            <div class="section-header">
                <h2 id="how-title" class="clean-section-title">{{ app()->getLocale() === 'ar' ? 'كيف تعمل توصيلي؟' : 'How Tawsili Works' }}</h2>
                <p class="section-subtitle">
                    {{ app()->getLocale() === 'ar' ? 'عملية بسيطة في أربع خطوات من الطلب حتى التسليم' : 'A simple streamlined process in four steps' }}
                </p>
            </div>

            <div class="steps-flow-container">
                {{-- Step 01 --}}
                <div class="step-card">
                    <span class="step-badge">01</span>
                    <div class="step-icon-circle">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/>
                        </svg>
                    </div>
                    <h3 class="step-title">{{ app()->getLocale() === 'ar' ? 'أنشئ شحنتك' : 'Create Shipment' }}</h3>
                    <p class="step-desc">{{ app()->getLocale() === 'ar' ? 'أدخل بيانات المرسل والمستلم والوزن' : 'Fill sender and receiver details' }}</p>
                </div>

                {{-- Connector Arrow 1 --}}
                <div class="step-arrow-connector" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#CBD5E1" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="15 18 9 12 15 6"/>
                    </svg>
                </div>

                {{-- Step 02 --}}
                <div class="step-card">
                    <span class="step-badge">02</span>
                    <div class="step-icon-circle">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <polyline points="6 9 6 2 18 2 18 9"/>
                            <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
                            <rect x="6" y="14" width="12" height="8"/>
                        </svg>
                    </div>
                    <h3 class="step-title">{{ app()->getLocale() === 'ar' ? 'جهز الشحنة' : 'Prepare Parcel' }}</h3>
                    <p class="step-desc">{{ app()->getLocale() === 'ar' ? 'احصل على بوليصات وملصق الشحن' : 'Print waybill and shipping label' }}</p>
                </div>

                {{-- Connector Arrow 2 --}}
                <div class="step-arrow-connector" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#CBD5E1" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="15 18 9 12 15 6"/>
                    </svg>
                </div>

                {{-- Step 03 --}}
                <div class="step-card">
                    <span class="step-badge">03</span>
                    <div class="step-icon-circle">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect x="1" y="3" width="15" height="13"/>
                            <polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/>
                            <circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>
                        </svg>
                    </div>
                    <h3 class="step-title">{{ app()->getLocale() === 'ar' ? 'استلام السائق' : 'Driver Pickup' }}</h3>
                    <p class="step-desc">{{ app()->getLocale() === 'ar' ? 'يتولى السائق متابعة عملية الاستلام' : 'Assigned driver handles collection' }}</p>
                </div>

                {{-- Connector Arrow 3 --}}
                <div class="step-arrow-connector" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#CBD5E1" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="15 18 9 12 15 6"/>
                    </svg>
                </div>

                {{-- Step 04 --}}
                <div class="step-card">
                    <span class="step-badge">04</span>
                    <div class="step-icon-circle">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                    </div>
                    <h3 class="step-title">{{ app()->getLocale() === 'ar' ? 'تأكيد التسليم' : 'Confirm Delivery' }}</h3>
                    <p class="step-desc">{{ app()->getLocale() === 'ar' ? 'تأكد وصول الشحنة للمستلم برمز OTP' : 'Secure delivery verification with OTP' }}</p>
                </div>
            </div>
        </div>
    </section>

    {{-- ═══════════════════════════════════════════════════════════════════════
         6. WHY US SECTION ("لماذا توصيلي؟" — Real Shipping Boxes & Feature Pills)
    ═══════════════════════════════════════════════════════════════════════ --}}
    <section id="why-us" class="why-us-section" aria-labelledby="why-title">
        <div class="section-container">
            <div class="why-us-grid">
                {{-- Column 1: Copy & CTA --}}
                <div class="why-us-copy">
                    <h2 id="why-title" class="clean-section-title text-start">{{ app()->getLocale() === 'ar' ? 'لماذا توصيلي؟' : 'Why Tawsili?' }}</h2>
                    <p class="why-us-desc">
                        {{ app()->getLocale() === 'ar'
                            ? 'لأننا نؤمن بأن التفاصيل تصنع الفرق، نوفر لك تجربة شحن مريحة وموثوقة بأحدث تقنيات الفرز والتحقق الرقمي.'
                            : 'Because we believe precision makes the difference, we deliver a seamless and reliable logistics experience.' }}
                    </p>
                    <a href="{{ route('about') }}" class="btn-hero-primary">
                        <span>{{ app()->getLocale() === 'ar' ? 'اكتشف المزيد' : 'Discover More' }}</span>
                        <svg class="btn-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
                        </svg>
                    </a>
                </div>

                {{-- Column 2: Crisp Parcel Boxes Photo on Pallet with Red Accent --}}
                <div class="why-us-visual">
                    <div class="boxes-wrapper">
                        <img src="{{ asset('images/tawsili-why-us.png') }}" alt="{{ app()->getLocale() === 'ar' ? 'طرد توصيلي جاهز للشحن' : 'Tawsili parcel ready for shipping' }}" class="boxes-image">
                    </div>
                </div>

                {{-- Column 3: 4 Stacked White Feature Pill Badges --}}
                <div class="why-us-features">
                    {{-- 1. سرعة في التوصيل --}}
                    <div class="feature-pill-badge">
                        <span class="pill-text">{{ app()->getLocale() === 'ar' ? 'سرعة في التوصيل' : 'Speedy Delivery' }}</span>
                        <div class="pill-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#D71920" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <circle cx="12" cy="12" r="10"/>
                                <polyline points="12 6 12 12 16 14"/>
                            </svg>
                        </div>
                    </div>

                    {{-- 2. أمان وموثوقية --}}
                    <div class="feature-pill-badge">
                        <span class="pill-text">{{ app()->getLocale() === 'ar' ? 'أمان وموثوقية' : 'Safety & Reliability' }}</span>
                        <div class="pill-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#D71920" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                            </svg>
                        </div>
                    </div>

                    {{-- 3. متابعة في كل خطوة --}}
                    <div class="feature-pill-badge">
                        <span class="pill-text">{{ app()->getLocale() === 'ar' ? 'متابعة في كل خطوة' : 'Step-by-Step Tracking' }}</span>
                        <div class="pill-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#D71920" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <rect x="5" y="2" width="14" height="20" rx="2" ry="2"/>
                                <line x1="12" y1="18" x2="12.01" y2="18"/>
                            </svg>
                        </div>
                    </div>

                    {{-- 4. دعم فني متميز --}}
                    <div class="feature-pill-badge">
                        <span class="pill-text">{{ app()->getLocale() === 'ar' ? 'دعم فني متميز' : 'Top Customer Support' }}</span>
                        <div class="pill-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#D71920" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M3 18v-6a9 9 0 0 1 18 0v6"/>
                                <path d="M21 19a2 2 0 0 1-2 2h-1a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h3zM3 19a2 2 0 0 0 2 2h1a2 2 0 0 0 2-2v-3a2 2 0 0 0-2-2H3z"/>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ═══════════════════════════════════════════════════════════════════════
         7. REVIEWS & RATINGS SECTION ("تقييمات العملاء" — #reviews)
    ═══════════════════════════════════════════════════════════════════════ --}}
    <section id="reviews" class="reviews-section" aria-labelledby="reviews-title">
        <div class="section-container">
            <div class="section-header">
                <div class="section-badge">
                    <span class="badge-dash" aria-hidden="true"></span>
                    <h2 id="reviews-title" class="badge-title">{{ app()->getLocale() === 'ar' ? 'تقييمات العملاء' : 'Customer Reviews' }}</h2>
                    <span class="badge-dash" aria-hidden="true"></span>
                </div>
                <p class="section-subtitle">
                    {{ app()->getLocale() === 'ar' ? 'ماذا يقول عملاؤنا عن تجربة الشحن والتوصيل مع توصيلي؟' : 'What our customers say about shipping with Tawsili' }}
                </p>

                {{-- Rating Summary Capsule --}}
                <div class="rating-summary-capsule">
                    @if ($reviewCount > 0)
                        <div class="rating-stars-gold" aria-hidden="true">
                            @for ($i = 0; $i < 5; $i++)
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="#F59E0B" stroke="#F59E0B" stroke-width="1">
                                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                                </svg>
                            @endfor
                        </div>
                        <strong class="rating-score">{{ number_format($averageRating, 1) }} / 5</strong>
                        <span class="rating-count">({{ number_format($reviewCount) }} {{ app()->getLocale() === 'ar' ? 'تقييم معتمد' : 'Verified Reviews' }})</span>
                    @else
                        <p class="rating-empty-summary" role="status">{{ __('app.home.no_reviews') }}</p>
                    @endif
                </div>
            </div>

            {{-- Reviews Grid (3 Columns) --}}
            <div class="reviews-cards-grid">
                @forelse ($reviews as $review)
                    <article class="review-card-item">
                        <div class="card-stars">
                            @for ($i = 1; $i <= 5; $i++)
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="{{ $i <= $review->rating ? '#F59E0B' : '#E2E8F0' }}" stroke="none">
                                    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                                </svg>
                            @endfor
                        </div>
                        @if (filled($review->comment))
                            <p class="review-text">"{{ $review->comment }}"</p>
                        @else
                            <p class="review-text">{{ __('app.home.rating_only') }}</p>
                        @endif
                        <footer class="review-author">
                            <div class="author-avatar">{{ mb_substr($review->user?->name ?? 'ع', 0, 1) }}</div>
                            <div class="author-info">
                                <strong class="author-name">{{ $review->user?->name ?? (app()->getLocale() === 'ar' ? 'عميل موثق' : 'Verified Customer') }}</strong>
                                <span class="author-verified">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#16A34A" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                    {{ app()->getLocale() === 'ar' ? 'شحنة مستلمة وموثقة' : 'Verified Delivery' }}
                                </span>
                            </div>
                        </footer>
                    </article>
                @empty
                    <article class="review-empty-state" role="status">{{ __('app.home.no_reviews') }}</article>
                @endforelse
            </div>

            <div class="reviews-action-center">
                <a href="{{ route('reviews.index') }}" class="btn-hero-secondary">
                    <span>{{ app()->getLocale() === 'ar' ? 'عرض جميع التقييمات' : 'View All Reviews' }}</span>
                    <svg class="btn-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
                    </svg>
                </a>
            </div>
        </div>
    </section>

    {{-- ═══════════════════════════════════════════════════════════════════════
         8. INTERACTIVE CONTACT & LIVE WORKING MAP (Dark Navy Band — #contact)
    ═══════════════════════════════════════════════════════════════════════ --}}
    <section id="contact" class="dark-contact-band" aria-labelledby="contact-heading">
        <div class="section-container">
            <div class="dark-contact-header">
                <div class="section-badge dark-badge">
                    <span class="badge-dash"></span>
                    <h2 id="contact-heading" class="badge-title text-white">{{ app()->getLocale() === 'ar' ? 'تواصل معنا' : 'Contact Us' }}</h2>
                    <span class="badge-dash"></span>
                </div>
                <p class="dark-contact-sub">
                    {{ app()->getLocale() === 'ar' ? 'فريقنا متواجد للرد على استفساراتكم وتنسيق متطلبات الشحن والتوصيل.' : 'Our team is available to answer your inquiries and coordinate your shipping.' }}
                </p>
            </div>

            <div class="contact-map-layout">
                {{-- Side 1: Interactive Contact Form --}}
                <div class="contact-form-card">
                    <h3 class="form-card-title">{{ app()->getLocale() === 'ar' ? 'أرسل لنا استفسارك مباشرة' : 'Send Us a Direct Message' }}</h3>
                    <p class="form-card-subtitle">{{ app()->getLocale() === 'ar' ? 'املأ الحقول التالية وسيقوم فريق خدمة العملاء بالتواصل معك مباشرة.' : 'Fill in the fields below and our customer support team will contact you directly.' }}</p>

                    @if (session('status'))
                        <div class="contact-alert success" role="status">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#16A34A" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                            <span>{{ session('status') }}</span>
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="contact-alert error" role="alert">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#DC2626" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                            <span>{{ __('app.auth.form_error') }}</span>
                        </div>
                    @endif

                    <form class="contact-interactive-form" method="post" action="{{ route('contact.submit') }}">
                        @csrf
                        <div class="form-row-2">
                            <div class="contact-field">
                                <label for="contact-name">{{ app()->getLocale() === 'ar' ? 'الاسم الكامل' : 'Full Name' }} <span class="req">*</span></label>
                                <input type="text" id="contact-name" name="name" class="contact-input" value="{{ old('name') }}" placeholder="{{ app()->getLocale() === 'ar' ? 'مثال: محمد السعيد' : 'e.g. John Doe' }}" autocomplete="name" required>
                                @error('name') <span class="contact-error">{{ $message }}</span> @enderror
                            </div>

                            <div class="contact-field">
                                <label for="contact-email">{{ app()->getLocale() === 'ar' ? 'البريد الإلكتروني' : 'Email Address' }} <span class="req">*</span></label>
                                <input type="email" id="contact-email" name="email" class="contact-input" value="{{ old('email') }}" placeholder="name@company.com" autocomplete="email" required>
                                @error('email') <span class="contact-error">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="contact-field">
                            <label for="contact-message">{{ app()->getLocale() === 'ar' ? 'نص الرسالة أو تفاصيل الشحنة' : 'Message or Shipment Details' }} <span class="req">*</span></label>
                            <textarea id="contact-message" name="message" class="contact-textarea" rows="4" minlength="10" maxlength="5000" placeholder="{{ app()->getLocale() === 'ar' ? 'اكتب استفسارك، وجهة الشحن، أو حجم الطرود هنا...' : 'Write your question, destination or cargo size here...' }}" required>{{ old('message') }}</textarea>
                            @error('message') <span class="contact-error">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-submit-row">
                            <button type="submit" class="btn-contact-submit">
                                <span>{{ app()->getLocale() === 'ar' ? 'إرسال الرسالة' : 'Send Message' }}</span>
                                <svg class="btn-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
                                </svg>
                            </button>
                        </div>
                    </form>

                    {{-- Quick contact meta chips --}}
                    <div class="contact-meta-chips">
                        <div class="meta-chip">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#D71920" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                            <a href="mailto:{{ config('mail.from.address') }}">{{ config('mail.from.address') }}</a>
                        </div>
                    </div>
                </div>

                {{-- Side 2: Live Working Interactive Leaflet Map --}}
                <div class="contact-map-card">
                    <div class="map-card-header">
                        <div class="map-status-indicator">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#D71920" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                <circle cx="12" cy="10" r="3"/>
                            </svg>
                            <strong>{{ app()->getLocale() === 'ar' ? 'مقر المركز اللوجستي الرئيسي' : 'Main Logistics Hub' }}</strong>
                        </div>
                        <span class="map-hub-city">{{ app()->getLocale() === 'ar' ? 'الرياض، المملكة العربية السعودية' : 'Riyadh, Saudi Arabia' }}</span>
                    </div>

                    {{-- Map Container initialized by Leaflet --}}
                    <div id="interactive-map" class="interactive-leaflet-map" role="region" aria-label="Interactive Logistics Map"></div>

                    <div class="map-card-footer">
                        <div class="map-footer-item">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#D71920" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                            <span>{{ app()->getLocale() === 'ar' ? 'طريق الملك فهد، المركز اللوجستي، الرياض' : 'King Fahd Rd, Logistics Center, Riyadh' }}</span>
                        </div>
                        <button type="button" class="btn-map-recenter" id="btn-recenter-map">
                            <span>{{ app()->getLocale() === 'ar' ? 'إعادة توسيط الخريطة' : 'Recenter Map' }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </section>

@endsection
