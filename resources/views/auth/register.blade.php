@extends('layouts.public')

@section('title', __('app.auth.register_title'))

@section('content')
    <div class="auth-shell">
        <aside class="auth-aside">
            <a class="brand" href="{{ route('home') }}">
                <span class="brand-mark" aria-hidden="true">ت</span>
                <span>{{ __('app.brand.name') }}</span>
            </a>
            <div>
                <p class="eyebrow" style="color: #fff;">{{ __('app.auth.join_eyebrow') }}</p>
                <h1>{{ __('app.auth.join_heading') }}</h1>
                <p>{{ __('app.auth.join_copy') }}</p>
            </div>
            <div class="aside-foot">{{ __('app.brand.aside_note') }}</div>
        </aside>
        <section class="auth-form-panel" aria-labelledby="form-title">
            <div class="auth-form">
                <span class="eyebrow">{{ __('app.auth.new_account') }}</span>
                <h2 id="form-title">{{ __('app.auth.register_title') }}</h2>
                <p class="form-intro">{{ __('app.auth.register_intro') }}</p>

                @if ($errors->any())
                    <p class="status-message error" role="alert">{{ __('app.auth.form_error') }}</p>
                @endif

                <form method="post" action="{{ route('register') }}" id="registration-form">
                    @csrf
                    <div class="form-field">
                        <label for="role">{{ __('app.auth.account_type') }}</label>
                        <select class="form-control" id="role" name="role" required
                            data-staff-notice="{{ __('app.auth.staff_approval_notice') }}"
                            data-customer-notice="{{ __('app.auth.customer_available_now') }}">
                            <option value="customer" @selected(old('role', 'customer') === 'customer')>{{ __('app.auth.role_customer') }}</option>
                            <option value="driver" @selected(old('role') === 'driver')>{{ __('app.auth.role_driver') }}</option>
                            <option value="employee" @selected(old('role') === 'employee')>{{ __('app.auth.role_employee') }}</option>
                        </select>
                        @error('role') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="form-field">
                        <label for="name">{{ __('app.auth.full_name') }}</label>
                        <input class="form-control" id="name" name="name" value="{{ old('name') }}" autocomplete="name" required>
                        @error('name') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="form-grid">
                        <div class="form-field">
                            <label for="email">{{ __('app.auth.email') }}</label>
                            <input class="form-control" id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required>
                            @error('email') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                        <div class="form-field">
                            <label for="phone">{{ __('app.auth.phone') }}</label>
                            <input class="form-control" id="phone" name="phone" type="tel" value="{{ old('phone') }}" autocomplete="tel" required>
                            @error('phone') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div id="staff-fields" hidden>
                        <div class="form-grid">
                            <div class="form-field">
                                <label for="national_id">{{ __('app.auth.national_id') }}</label>
                                <input class="form-control" id="national_id" name="national_id" value="{{ old('national_id') }}" autocomplete="off">
                                @error('national_id') <p class="field-error">{{ $message }}</p> @enderror
                            </div>
                            <div class="form-field">
                                <label for="city">{{ __('app.auth.city') }}</label>
                                <input class="form-control" id="city" name="city" value="{{ old('city') }}" autocomplete="address-level2">
                                @error('city') <p class="field-error">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>
                    <div id="driver-fields" hidden>
                        <div class="form-grid">
                            <div class="form-field">
                                <label for="vehicle_type">{{ __('app.auth.vehicle_type') }}</label>
                                <input class="form-control" id="vehicle_type" name="vehicle_type" value="{{ old('vehicle_type') }}">
                                @error('vehicle_type') <p class="field-error">{{ $message }}</p> @enderror
                            </div>
                            <div class="form-field">
                                <label for="license_number">{{ __('app.auth.license_number') }}</label>
                                <input class="form-control" id="license_number" name="license_number" value="{{ old('license_number') }}">
                                @error('license_number') <p class="field-error">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>
                    <div class="form-grid">
                        <div class="form-field">
                            <label for="password">{{ __('app.auth.password') }}</label>
                            <input class="form-control" id="password" name="password" type="password" minlength="10" autocomplete="new-password" required>
                            @error('password') <p class="field-error">{{ $message }}</p> @enderror
                        </div>
                        <div class="form-field">
                            <label for="password_confirmation">{{ __('app.auth.confirm_password') }}</label>
                            <input class="form-control" id="password_confirmation" name="password_confirmation" type="password" minlength="10" autocomplete="new-password" required aria-describedby="password-confirmation-error">
                            @error('password_confirmation') <p class="field-error" id="password-confirmation-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <p class="field-help" id="approval-help">{{ __('app.auth.customer_available_now') }}</p>
                    <div class="form-actions">
                        <button class="button-primary" type="submit">{{ __('app.auth.create_account') }}</button>
                    </div>
                </form>
                <p class="form-bottom">{{ __('app.auth.already_registered') }} <a class="text-link" href="{{ route('login') }}">{{ __('app.auth.login') }}</a></p>
            </div>
        </section>
    </div>
    <script>
        const roleSelect = document.getElementById('role');
        const staffFields = document.getElementById('staff-fields');
        const driverFields = document.getElementById('driver-fields');
        const approvalHelp = document.getElementById('approval-help');

        function syncRoleFields() {
            const role = roleSelect.value;
            const isStaff = role === 'driver' || role === 'employee';
            const isDriver = role === 'driver';
            staffFields.hidden = !isStaff;
            driverFields.hidden = !isDriver;
            staffFields.querySelectorAll('input').forEach((input) => input.required = isStaff);
            driverFields.querySelectorAll('input').forEach((input) => input.required = isDriver);
            approvalHelp.textContent = isStaff
                ? roleSelect.dataset.staffNotice
                : roleSelect.dataset.customerNotice;
        }

        roleSelect.addEventListener('change', syncRoleFields);
        syncRoleFields();
    </script>
@endsection