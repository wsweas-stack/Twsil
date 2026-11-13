@php
    $arrowIcon = app()->getLocale() === 'ar'
        ? \Filament\Support\Icons\Heroicon::OutlinedArrowLeft
        : \Filament\Support\Icons\Heroicon::OutlinedArrowRight;
@endphp

<div class="dqa">
    <h2 class="dqa-heading">{{ $heading }}</h2>

    @if (count($actions) === 0)
        <p class="dqa-empty">{{ $emptyText }}</p>
    @else
        <div class="dqa-grid">
            @foreach ($actions as $action)
                <a href="{{ $action['url'] }}" class="dqa-card">
                    <span class="dqa-card-icon">
                        <x-filament::icon :icon="$action['icon']" />
                    </span>

                    <span class="dqa-card-body">
                        <span class="dqa-card-title">{{ $action['title'] }}</span>
                        <span class="dqa-card-description">{{ $action['description'] }}</span>
                    </span>

                    <x-filament::icon :icon="$arrowIcon" class="dqa-card-arrow" />
                </a>
            @endforeach
        </div>
    @endif
</div>
