<?php

namespace App\Http\Controllers;

use App\Enums\UserRoles;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function dashboard(Request $request): View
    {
        abort_unless($request->user()->hasRole(UserRoles::Customer->value), 403);

        return view('account.dashboard', [
            'recentShipments' => $request->user()->customerShipments()->latest()->limit(5)->get(),
            'shipmentCount' => $request->user()->customerShipments()->count(),
        ]);
    }

    public function approvalStatus(Request $request): View
    {
        abort_unless($request->user()->hasAnyRole([
            UserRoles::Driver->value,
            UserRoles::Employee->value,
        ]), 403);

        return view('account.approval-status');
    }
}