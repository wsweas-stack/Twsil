/**
 * Tousili — Clean Human Logistics Engine
 * Pure vanilla JavaScript: Sticky header, smooth anchor navigation,
 * scroll spy, counters animation, and interactive Leaflet map.
 */

document.addEventListener('DOMContentLoaded', () => {
    initStickyHeader();
    initSmoothAnchors();
    initScrollSpy();
    initScrollReveal();
    initCounters();
    initTrackingErrorFocus();
    initInteractiveMap();
    initMobileMenu();
});

/**
 * Mobile Menu (native <details> hamburger)
 * - Closes after a link is chosen so the panel never stays open over the page.
 * - Smooth-scrolls to section anchors when already on the home page
 *   (links carry full URLs like /#services, so the browser would reload otherwise).
 * - Closes on Escape, clicks outside the menu, and when leaving mobile layout.
 */
function initMobileMenu() {
    const menu = document.querySelector('.mobile-menu');
    if (!menu) return;

    const backdrop = document.querySelector('.mobile-menu-backdrop');

    const close = () => menu.removeAttribute('open');

    // Keep the dimmed backdrop and body scroll-lock in sync with the <details> state.
    menu.addEventListener('toggle', () => {
        document.body.classList.toggle('mobile-menu-open', menu.open);
    });

    if (backdrop) {
        backdrop.addEventListener('click', close);
    }

    menu.querySelectorAll('.mobile-menu-panel a').forEach(link => {
        link.addEventListener('click', e => {
            close();

            const url = new URL(link.href, window.location.href);
            const currentPath = window.location.pathname.replace(/\/+$/, '') || '/';
            if (!url.hash || url.pathname.replace(/\/+$/, '') !== currentPath) return;

            const target = document.getElementById(url.hash.slice(1));
            if (target) {
                e.preventDefault();
                target.scrollIntoView({ behavior: 'smooth' });
            }
        });
    });

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') close();
    });

    document.addEventListener('click', e => {
        if (menu.open && !menu.contains(e.target)) close();
    });

    window.addEventListener('resize', () => {
        if (window.innerWidth > 1180) close();
    }, { passive: true });
}

/**
 * Scroll Reveal System
 * Marks page elements with [data-reveal] and reveals them
 * progressively as they enter the viewport (one-time reveal per element).
 * Progressive enhancement: without JavaScript nothing is hidden, so
 * crawlers, screen readers, and no-JS users always see full content.
 */
function initScrollReveal() {
    const REVEAL_SELECTORS = [
        '.hero-kicker', '.hero-title', '.hero-desc', '.hero-actions', '.van-wrapper',
        '.tracking-pill-card', '.section-header', '.modern-service-card',
        '.dark-stats-header', '.dark-stat-card', '.step-card', '.step-arrow-connector',
        '.why-us-copy', '.why-us-visual', '.feature-pill-badge',
        '.rating-summary-capsule', '.review-card-item',
        '.contact-form-card', '.contact-map-card',
    ];

    const targets = document.querySelectorAll(REVEAL_SELECTORS.join(', '));
    const elements = Array.from(targets);
    if (!elements.length) return;

    document.documentElement.classList.add('js-reveal');

    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        return; // CSS shows the final state immediately; nothing to observe.
    }

    // Sequential stagger for items sharing the same parent (grids, flows, stacks).
    // Elements nested inside another reveal target are skipped to avoid
    // double-animating (parent slides in, then the child slides in again).
    // Stagger is capped at ~8 steps so long grids never feel sluggish.
    const groups = new Map();
    const isNested = el => elements.some(other => other !== el && other.contains(el));
    elements.forEach(el => {
        if (isNested(el)) return;
        const parent = el.parentElement;
        const siblings = groups.get(parent) || [];
        siblings.push(el);
        groups.set(parent, siblings);
    });
    groups.forEach(siblings => {
        siblings.forEach((el, index) => {
            el.setAttribute('data-reveal', 'zoom');
            if (index > 0) {
                el.style.setProperty('--reveal-delay', `${Math.min(index, 7) * 80}ms`);
            }
        });
    });

    const observer = new IntersectionObserver((entries, obs) => {
        entries.forEach(entry => {
            if (!entry.isIntersecting) return;
            entry.target.classList.add('is-visible');
            obs.unobserve(entry.target); // One-time reveal: no flicker on scroll back.
        });
    }, { threshold: 0.15, rootMargin: '0px 0px -48px 0px' });

    elements.forEach(el => observer.observe(el));
}


function initStickyHeader() {
    const header = document.querySelector('.tawsili-header');
    if (!header) return;

    let ticking = false;
    const onScroll = () => {
        if (window.scrollY > 20) {
            header.classList.add('is-scrolled');
        } else {
            header.classList.remove('is-scrolled');
        }
        ticking = false;
    };

    window.addEventListener('scroll', () => {
        if (!ticking) {
            window.requestAnimationFrame(onScroll);
            ticking = true;
        }
    }, { passive: true });

    onScroll();
}

function initSmoothAnchors() {
    document.querySelectorAll('a[href^="#"]').forEach(link => {
        link.addEventListener('click', e => {
            const hash = link.getAttribute('href');
            if (!hash || hash === '#') return;

            const target = document.querySelector(hash);
            if (target) {
                e.preventDefault();
                target.scrollIntoView({ behavior: 'smooth' });
                document.querySelectorAll('.header-nav a').forEach(a => a.classList.remove('active'));
                if (link.closest('.header-nav')) {
                    link.classList.add('active');
                }
            }
        });
    });
}

function initScrollSpy() {
    const currentPath = window.location.pathname.replace(/\/+$/, '') || '/';
    const navItems = Array.from(document.querySelectorAll('.header-nav a'))
        .map(link => {
            const url = new URL(link.href, window.location.href);
            const section = document.getElementById(url.hash.slice(1));

            if (url.pathname.replace(/\/+$/, '') !== currentPath || !section) {
                return null;
            }

            return { link, section };
        })
        .filter(Boolean)
        .sort((a, b) => a.section.getBoundingClientRect().top - b.section.getBoundingClientRect().top);

    if (!navItems.length) return;

    const header = document.querySelector('.tawsili-header');
    let ticking = false;
    const updateActiveLink = () => {
        const activationLine = window.scrollY + (header?.offsetHeight || 0) + 32;
        let activeItem = navItems[0];

        navItems.forEach(item => {
            const sectionTop = item.section.getBoundingClientRect().top + window.scrollY;
            if (sectionTop <= activationLine) {
                activeItem = item;
            }
        });
        if (window.scrollY + window.innerHeight >= document.documentElement.scrollHeight - 2) {
            activeItem = navItems[navItems.length - 1];
        }

        document.querySelectorAll('.header-nav a, .mobile-menu-panel a').forEach(link => {
            const isActive = link.href === activeItem.link.href;
            link.classList.toggle('active', isActive);
            if (isActive) {
                link.setAttribute('aria-current', 'location');
            } else {
                link.removeAttribute('aria-current');
            }
        });

        ticking = false;
    };
    const scheduleUpdate = () => {
        if (ticking) return;
        window.requestAnimationFrame(updateActiveLink);
        ticking = true;
    };

    window.addEventListener('scroll', scheduleUpdate, { passive: true });
    window.addEventListener('resize', scheduleUpdate);
    window.addEventListener('hashchange', scheduleUpdate);
    updateActiveLink();
}

function initCounters() {
    const counters = document.querySelectorAll('.stat-counter[data-target]');
    if (!counters.length) return;

    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    const observer = new IntersectionObserver((entries, obs) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const el = entry.target;
                const target = parseInt(el.dataset.target, 10);
                if (isNaN(target) || target <= 0) return;

                if (reducedMotion) {
                    el.textContent = target.toLocaleString();
                } else {
                    const duration = 1600;
                    const start = performance.now();
                    const step = now => {
                        const progress = Math.min((now - start) / duration, 1);
                        const ease = 1 - Math.pow(1 - progress, 3);
                        el.textContent = Math.floor(ease * target).toLocaleString();
                        if (progress < 1) requestAnimationFrame(step);
                        else el.textContent = target.toLocaleString();
                    };
                    requestAnimationFrame(step);
                }
                obs.unobserve(el);
            }
        });
    }, { threshold: 0.3 });

    counters.forEach(c => observer.observe(c));
}

function initTrackingErrorFocus() {
    const errorAlert = document.querySelector('.tracking-error-alert');
    const input = document.getElementById('tracking_number');
    if (errorAlert && input) {
        errorAlert.scrollIntoView({ behavior: 'smooth', block: 'center' });
        input.focus({ preventScroll: true });
    }
}

/**
 * Initialize Interactive OpenStreetMap Leaflet Engine
 * Real logistics hub pin, smooth pan/zoom, bilingual popup, recenter controller.
 */
function initInteractiveMap() {
    const mapContainer = document.getElementById('interactive-map');
    if (!mapContainer) return;

    // Verify Leaflet is loaded from CDN
    if (typeof window.L === 'undefined') {
        window.addEventListener('load', () => {
            if (typeof window.L !== 'undefined') {
                setupLeafletInstance(mapContainer);
            }
        });
        return;
    }

    setupLeafletInstance(mapContainer);
}

function setupLeafletInstance(container) {
    // Riyadh Central Hub Coordinates
    const HUB_LAT = 24.7136;
    const HUB_LNG = 46.6753;
    const DEFAULT_ZOOM = 13;

    try {
        const map = window.L.map(container, {
            center: [HUB_LAT, HUB_LNG],
            zoom: DEFAULT_ZOOM,
            scrollWheelZoom: false, // Prevent wheel trapping during regular page scroll
            attributionControl: true
        });

        // High contrast OpenStreetMap tiles
        window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a>'
        }).addTo(map);

        // Custom Red Pulse DivIcon matching Tousili Brand
        const pulseIcon = window.L.divIcon({
            className: 'tousili-map-pin',
            html: `
                <div style="position:relative; width:40px; height:40px; display:flex; align-items:center; justify-content:center;">
                    <span style="position:absolute; width:36px; height:36px; border-radius:50%; background:rgba(215,25,32,0.35); animation:leafletPing 2s cubic-bezier(0,0,0.2,1) infinite;"></span>
                    <div style="position:relative; width:30px; height:30px; border-radius:50%; background:#D71920; border:3px solid #ffffff; box-shadow:0 6px 16px rgba(16,29,39,0.4); display:flex; align-items:center; justify-content:center;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#ffffff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                            <circle cx="12" cy="10" r="3"/>
                        </svg>
                    </div>
                </div>
            `,
            iconSize: [40, 40],
            iconAnchor: [20, 20],
            popupAnchor: [0, -22]
        });

        const isAr = document.documentElement.lang === 'ar';
        const popupTitle = isAr ? 'مركز عمليات توصيلي الرئيسي' : 'Tawsili Central Operations Hub';
        const popupSub = isAr ? 'طريق الملك فهد، المركز اللوجستي الموحد' : 'King Fahd Rd, Unified Logistics Center';
        const popupBadge = isAr ? 'نعمل 24/7 على مدار الساعة' : 'Operational 24/7';

        const popupContent = `
            <div style="font-family:inherit; text-align:center; padding:6px 2px; direction:${isAr ? 'rtl' : 'ltr'};">
                <strong style="display:block; color:#101D27; font-size:13.5px; font-weight:800; margin-bottom:4px;">${popupTitle}</strong>
                <p style="color:#64748B; font-size:11.5px; margin:0 0 8px 0; line-height:1.4;">${popupSub}</p>
                <span style="display:inline-block; background:#FEF2F2; color:#D71920; font-size:11px; font-weight:700; padding:3px 10px; border-radius:999px; border:1px solid #FEE2E2;">${popupBadge}</span>
            </div>
        `;

        const marker = window.L.marker([HUB_LAT, HUB_LNG], { icon: pulseIcon }).addTo(map);
        marker.bindPopup(popupContent).openPopup();

        // Recenter button binding — centers on the visitor's location when
        // available, otherwise back to the main hub.
        const recenterBtn = document.getElementById('btn-recenter-map');
        const flyToUserOrHub = () => {
            if (userMarker && userLat !== null) {
                map.flyTo([userLat, userLng], DEFAULT_ZOOM + 2, { animate: true, duration: 1.2 });
                userMarker.openPopup();
            } else {
                map.flyTo([HUB_LAT, HUB_LNG], DEFAULT_ZOOM + 1, { animate: true, duration: 1.2 });
                marker.openPopup();
            }
        };
        if (recenterBtn) {
            recenterBtn.addEventListener('click', (e) => {
                e.preventDefault();
                flyToUserOrHub();
            });
        }

        // ── Visitor live location (browser Geolocation API) ──────────────
        let userMarker = null;
        let userAccuracyCircle = null;
        let userLat = null;
        let userLng = null;

        const locateVisitor = () => {
            if (!navigator.geolocation) {
                console.warn('Geolocation is not supported by this browser.');
                return;
            }

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    userLat = position.coords.latitude;
                    userLng = position.coords.longitude;
                    const accuracy = position.coords.accuracy;

                    const userIcon = window.L.divIcon({
                        className: 'tousili-user-pin',
                        html: `
                            <div style="position:relative; width:40px; height:40px; display:flex; align-items:center; justify-content:center;">
                                <span style="position:absolute; width:36px; height:36px; border-radius:50%; background:rgba(37,99,235,0.35); animation:leafletPing 2s cubic-bezier(0,0,0.2,1) infinite;"></span>
                                <div style="position:relative; width:28px; height:28px; border-radius:50%; background:#2563EB; border:3px solid #ffffff; box-shadow:0 6px 16px rgba(16,29,39,0.4); display:flex; align-items:center; justify-content:center;">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="#ffffff"><circle cx="12" cy="8" r="4"/><path d="M12 14c-4 0-7 2.5-7 5v2h14v-2c0-2.5-3-5-7-5z"/></svg>
                                </div>
                            </div>
                        `,
                        iconSize: [40, 40],
                        iconAnchor: [20, 20],
                        popupAnchor: [0, -20]
                    });

                    const userPopupTitle = isAr ? 'موقعك الحالي' : 'Your Location';
                    const userPopupSub = isAr
                        ? 'تم تحديد موقعك تلقائياً من جهازك'
                        : 'Detected automatically from your device';
                    const userPopupBadge = isAr
                        ? `دقة التحديد: ±${Math.round(accuracy)} م`
                        : `Accuracy: ±${Math.round(accuracy)} m`;

                    const userPopupContent = `
                        <div style="font-family:inherit; text-align:center; padding:6px 2px; direction:${isAr ? 'rtl' : 'ltr'};">
                            <strong style="display:block; color:#101D27; font-size:13.5px; font-weight:800; margin-bottom:4px;">${userPopupTitle}</strong>
                            <p style="color:#64748B; font-size:11.5px; margin:0 0 8px 0; line-height:1.4;">${userPopupSub}</p>
                            <span style="display:inline-block; background:#EFF6FF; color:#2563EB; font-size:11px; font-weight:700; padding:3px 10px; border-radius:999px; border:1px solid #BFDBFE;">${userPopupBadge}</span>
                        </div>
                    `;

                    userMarker = window.L.marker([userLat, userLng], { icon: userIcon }).addTo(map);
                    userMarker.bindPopup(userPopupContent);

                    if (accuracy && accuracy > 0) {
                        userAccuracyCircle = window.L.circle([userLat, userLng], {
                            radius: Math.min(accuracy, 5000),
                            color: '#2563EB',
                            weight: 1,
                            fillColor: '#2563EB',
                            fillOpacity: 0.08
                        }).addTo(map);
                    }

                    // Frame both the hub and the visitor on screen.
                    map.fitBounds(
                        window.L.latLngBounds([[HUB_LAT, HUB_LNG], [userLat, userLng]]).pad(0.35),
                        { animate: true }
                    );
                    userMarker.openPopup();

                    if (recenterBtn) {
                        const label = recenterBtn.querySelector('span');
                        if (label) {
                            label.textContent = isAr ? 'التوسيط على موقعك' : 'Center on my location';
                        }
                    }
                },
                (error) => {
                    // Permission denied / unavailable / timeout → keep hub view.
                    console.warn('Visitor location unavailable:', error.message);
                },
                { enableHighAccuracy: false, timeout: 10000, maximumAge: 60000 }
            );
        };

        locateVisitor();

        // Adjust map geometry once loaded / rendered
        setTimeout(() => {
            map.invalidateSize();
        }, 350);

        window.addEventListener('resize', () => {
            map.invalidateSize();
        }, { passive: true });

    } catch (err) {
        console.warn('Leaflet map initialization skipped or failed:', err);
    }
}
