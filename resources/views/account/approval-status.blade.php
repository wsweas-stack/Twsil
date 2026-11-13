@extends('layouts.public')

@section('title', __('app.auth.approval_title'))

@section('content')
    <div class="account-shell">
        <header class="account-heading">
            <span class="eyebrow">{{ __('app.auth.staff_area') }}</span>
            <h1>{{ __('app.auth.approval_title') }}</h1>
        </header>
        <section class="account-content" aria-labelledby="approval-title">
            <h2 id="approval-title">{{ __('app.approval_statuses.' . auth()->user()->approval_status->value) }}</h2>
            <p>{{ auth()->user()->approval_status === \App\Enums\ApprovalStatus::Pending ? __('app.auth.pending_approval') : __('app.auth.rejected_approval') }}</p>
        </section>
    </div>
@endsection