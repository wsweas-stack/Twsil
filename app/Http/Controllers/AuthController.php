<?php

namespace App\Http\Controllers;

use App\Enums\ApprovalStatus;
use App\Enums\UserRoles;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Notifications\OperationalNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function showRegistration(): View
    {
        return view('auth.register');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->safe()->only(['email', 'password']);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => __('app.auth.invalid_credentials')])->onlyInput('email');
        }

        $request->session()->regenerate();

        return $this->redirectFor($request->user());
    }

    public function register(RegisterRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $role = UserRoles::from($data['role']);
        $isCustomer = $role === UserRoles::Customer;

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'phone' => $data['phone'],
            'national_id' => $isCustomer ? null : $data['national_id'],
            'city' => $isCustomer ? null : $data['city'],
            'vehicle_type' => $role === UserRoles::Driver ? $data['vehicle_type'] : null,
            'license_number' => $role === UserRoles::Driver ? $data['license_number'] : null,
            'approval_status' => $isCustomer ? ApprovalStatus::Approved : ApprovalStatus::Pending,
            'approved_at' => $isCustomer ? now() : null,
        ]);

        $user->assignRole($role->value);
        Auth::login($user);
        $request->session()->regenerate();
        $user->notify(new OperationalNotification(
            'registration_received',
            'app.notifications.registration_subject',
            $isCustomer ? 'app.notifications.customer_registered' : 'app.notifications.staff_registration_received',
            $isCustomer ? route('account.dashboard') : route('approval.status'),
        ));

        return $this->redirectFor($user, registration: true);
    }

    public function logout(Request $request): RedirectResponse
    {
        $locale = $request->session()->get('locale', config('app.locale'));
        $locale = in_array($locale, ['ar', 'en'], true) ? $locale : config('app.locale');

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->put('locale', $locale);
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    private function redirectFor(User $user, bool $registration = false): RedirectResponse
    {
        if ($user->hasRole(UserRoles::Customer->value)) {
            return redirect()->route('account.dashboard')->with(
                'status',
                $registration ? __('app.auth.customer_registered') : null,
            );
        }

        if ($user->hasInternalAccess()) {
            return redirect()->intended('/admin');
        }

        if ($user->hasAnyRole([UserRoles::Driver->value, UserRoles::Employee->value])) {
            return redirect()->route('approval.status')->with(
                'status',
                $registration ? __('app.auth.staff_registered') : null,
            );
        }

        Auth::logout();

        return redirect()->route('login')->withErrors(['email' => __('app.auth.no_access_role')]);
    }
}
