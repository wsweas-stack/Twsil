@extends('layouts.public')

@section('title', __('app.notifications.inbox'))

@section('content')
    <div class="account-shell">
        <header class="account-heading">
            <span class="eyebrow">{{ __('app.auth.customer_area') }}</span>
            <h1>{{ __('app.notifications.inbox') }}</h1>
        </header>
        <x-account-nav />
        <section class="account-content shipment-list">
            @forelse ($notifications as $notification)
                @php($content = \App\Notifications\OperationalNotification::localizedContent($notification->data))
                <article class="shipment-row" @if (! $notification->read_at) style="border-inline-start: 3px solid var(--brand); padding-inline-start: 12px;" @endif>
                    <span>
                        <strong>{{ $content['title'] }}</strong>
                        <small>{{ $content['message'] }}</small>
                        <small>{{ $notification->created_at->diffForHumans() }}</small>
                    </span>
                    <span class="notification-actions">
                        @if (! $notification->read_at)
                            <form method="post" action="{{ route('account.notifications.read', $notification->id) }}">
                                @csrf
                                <button class="text-link" type="submit">{{ __('app.notifications.mark_read') }}</button>
                            </form>
                        @endif
                        @if (! empty($notification->data['url']))
                            <a class="text-link" href="{{ $notification->data['url'] }}">{{ __('app.notifications.view_details') }}</a>
                        @endif
                    </span>
                </article>
            @empty
                <p class="empty-state">{{ __('app.notifications.empty') }}</p>
            @endforelse
            {{ $notifications->links() }}
        </section>
    </div>
@endsection