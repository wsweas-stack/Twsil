<?php

namespace App\Http\Controllers;

use App\Enums\UserRoles;
use App\Http\Requests\UpdateCustomerProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class CustomerProfileController extends Controller
{
    public function edit(Request $request): View
    {
        abort_unless($request->user()->hasRole(UserRoles::Customer->value), 403);

        return view('account.profile.edit', ['profile' => $request->user()]);
    }

    public function update(UpdateCustomerProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->safe()->only(['name', 'email', 'phone']);

        if (filled($request->input('password'))) {
            $data['password'] = Hash::make($request->input('password'));
        }

        $user->fill($data)->save();

        return back()->with('status', __('app.profile.updated'));
    }
}