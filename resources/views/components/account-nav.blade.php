@php
    $accountNavLinks = [
        ['label' => __('app.auth.account'), 'route' => 'account.dashboard', 'active' => request()->routeIs('account.dashboard'), 'icon' => '<path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 0 0 1 1h3m10-11l2 2m-2-2v10a1 1 0 0 1-1 1h-3m-6 0a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V14"/>'],
        ['label' => __('app.shipments.history'), 'route' => 'account.shipments.index', 'active' => request()->routeIs('account.shipments.index') || request()->routeIs('account.shipments.show'), 'icon' => '<path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/>'],
        ['label' => __('app.shipments.create'), 'route' => 'account.shipments.create', 'active' => request()->routeIs('account.shipments.create'), 'icon' => '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/>'],
        ['label' => __('app.reviews.my_reviews'), 'route' => 'account.reviews.index', 'active' => request()->routeIs('account.reviews.index'), 'icon' => '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>'],
        ['label' => __('app.notifications.inbox'), 'route' => 'account.notifications.index', 'active' => request()->routeIs('account.notifications.*'), 'icon' => '<path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>'],
        ['label' => __('app.profile.title'), 'route' => 'account.profile.edit', 'active' => request()->routeIs('account.profile.*'), 'icon' => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>'],
    ];
@endphp
<nav class="account-nav" aria-label="{{ __('app.nav.primary') }}">
    <ul>
        @foreach ($accountNavLinks as $link)
            <li>
                <a href="{{ route($link['route']) }}" @if ($link['active']) aria-current="page" @endif
                    class="{{ $link['active'] ? 'is-active' : '' }}">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        {!! $link['icon'] !!}
                    </svg>
                    <span>{{ $link['label'] }}</span>
                </a>
            </li>
        @endforeach
    </ul>
</nav>
