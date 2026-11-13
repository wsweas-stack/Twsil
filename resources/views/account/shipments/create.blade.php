@extends('layouts.public')

@section('title', __('app.shipments.create'))

@section('content')
    <div class="account-shell">
        <header class="account-heading">
            <span class="eyebrow">{{ __('app.auth.customer_area') }}</span>
            <h1>{{ __('app.shipments.create') }}</h1>
        </header>
        <x-account-nav />
        @if ($errors->any()) <p class="status-message error" role="alert">{{ __('app.auth.form_error') }}</p> @endif
        <form class="account-content" method="post" action="{{ route('account.shipments.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="shipment-detail-grid">
                <fieldset class="shipment-detail form-fieldset">
                    <legend>{{ __('app.shipments.sender') }}</legend>
                    <div class="form-field"><label for="sender_name">{{ __('app.auth.full_name') }}</label><input class="form-control" id="sender_name" name="sender_name" value="{{ old('sender_name', auth()->user()->name) }}" required>@error('sender_name')<p class="field-error">{{ $message }}</p>@enderror</div>
                    <div class="form-field"><label for="sender_phone">{{ __('app.auth.phone') }}</label><input class="form-control" id="sender_phone" name="sender_phone" type="tel" value="{{ old('sender_phone', auth()->user()->phone) }}" required>@error('sender_phone')<p class="field-error">{{ $message }}</p>@enderror</div>
                    <div class="form-field"><label for="sender_city">{{ __('app.shipments.city') }}</label><input class="form-control" id="sender_city" name="sender_city" value="{{ old('sender_city', auth()->user()->city) }}" required>@error('sender_city')<p class="field-error">{{ $message }}</p>@enderror</div>
                    <div class="form-field"><label for="sender_address">{{ __('app.shipments.address') }}</label><textarea class="form-control" id="sender_address" name="sender_address" required>{{ old('sender_address') }}</textarea>@error('sender_address')<p class="field-error">{{ $message }}</p>@enderror</div>
                </fieldset>
                <fieldset class="shipment-detail form-fieldset">
                    <legend>{{ __('app.shipments.receiver') }}</legend>
                    <div class="form-field"><label for="receiver_name">{{ __('app.auth.full_name') }}</label><input class="form-control" id="receiver_name" name="receiver_name" value="{{ old('receiver_name') }}" required>@error('receiver_name')<p class="field-error">{{ $message }}</p>@enderror</div>
                    <div class="form-field"><label for="receiver_phone">{{ __('app.auth.phone') }}</label><input class="form-control" id="receiver_phone" name="receiver_phone" type="tel" value="{{ old('receiver_phone') }}" required>@error('receiver_phone')<p class="field-error">{{ $message }}</p>@enderror</div>
                    <div class="form-field"><label for="receiver_email">{{ __('app.shipments.receiver_email') }}</label><input class="form-control" id="receiver_email" name="receiver_email" type="email" value="{{ old('receiver_email') }}">@error('receiver_email')<p class="field-error">{{ $message }}</p>@enderror</div>
                    <div class="form-field"><label for="receiver_city">{{ __('app.shipments.city') }}</label><input class="form-control" id="receiver_city" name="receiver_city" value="{{ old('receiver_city') }}" required>@error('receiver_city')<p class="field-error">{{ $message }}</p>@enderror</div>
                    <div class="form-field"><label for="receiver_address">{{ __('app.shipments.address') }}</label><textarea class="form-control" id="receiver_address" name="receiver_address" required>{{ old('receiver_address') }}</textarea>@error('receiver_address')<p class="field-error">{{ $message }}</p>@enderror</div>
                </fieldset>
            </div>
            <div class="shipment-detail" style="margin-top: 20px;">
                <h2>{{ __('app.shipments.parcel') }}</h2>
                <div class="form-grid">
                    <div class="form-field"><label for="description">{{ __('app.shipments.description') }}</label><input class="form-control" id="description" name="description" value="{{ old('description') }}" maxlength="255" required>@error('description')<p class="field-error">{{ $message }}</p>@enderror</div>
                    <div class="form-field"><label for="weight_kg">{{ __('app.shipments.weight') }}</label><input class="form-control" id="weight_kg" name="weight_kg" type="number" min="0.01" max="9999" step="0.01" value="{{ old('weight_kg') }}">@error('weight_kg')<p class="field-error">{{ $message }}</p>@enderror</div>
                </div>
                <div class="form-field"><label for="invoice">{{ __('app.shipments.invoice') }}</label><input class="form-control" id="invoice" name="invoice" type="file" accept="application/pdf,.pdf"> <p class="field-help">{{ __('app.shipments.invoice_help') }}</p>@error('invoice')<p class="field-error">{{ $message }}</p>@enderror</div>
            </div>
            <p class="field-help">{{ __('app.shipments.fee_pending') }}</p>
            <div class="form-actions"><button class="button-primary" style="width: auto; min-width: 220px;" type="submit">{{ __('app.shipments.submit') }}</button></div>
        </form>
    </div>
@endsection