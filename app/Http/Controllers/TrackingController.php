<?php

namespace App\Http\Controllers;

use App\Models\Shipment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TrackingController extends Controller
{
    public function form(): View
    {
        return view('tracking.show');
    }

    public function lookup(Request $request): View|RedirectResponse
    {
        $validated = $request->validate([
            'tracking_number' => ['required', 'string', 'max:32', 'regex:/^[A-Za-z0-9-]+$/'],
        ]);

        $shipment = Shipment::query()
            ->where('tracking_number', strtoupper($validated['tracking_number']))
            ->first();

        return view('tracking.show', [
            'shipment' => $shipment,
            'events' => $shipment?->events()
                ->whereNotNull('occurred_at')
                ->orderBy('occurred_at')
                ->get(['status', 'occurred_at']),
            'notFound' => $shipment === null,
        ]);
    }
}