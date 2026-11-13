<?php

namespace App\Http\Controllers;

use App\Enums\ShipmentStatus;
use App\Enums\UserRoles;
use App\Http\Requests\StoreShipmentRequest;
use App\Models\Shipment;
use App\Services\ShipmentService;
use App\Services\ShipmentStatusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ShipmentController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorizeCustomer($request);

        $shipments = $request->user()->customerShipments()
            ->with('review')
            ->latest()
            ->paginate(10);

        return view('account.shipments.index', compact('shipments'));
    }

    public function create(Request $request): View
    {
        $this->authorizeCustomer($request);

        return view('account.shipments.create');
    }

    public function store(StoreShipmentRequest $request, ShipmentService $shipments): RedirectResponse
    {
        $shipment = $shipments->createForCustomer(
            $request->user(),
            $request->safe()->except('invoice'),
            $request->file('invoice'),
        );

        return redirect()
            ->route('account.shipments.show', $shipment)
            ->with('status', __('app.shipments.created'));
    }

    public function show(Request $request, Shipment $shipment): View
    {
        $this->authorizeShipmentOwner($request, $shipment);
        $shipment->load(['events', 'review']);

        return view('account.shipments.show', compact('shipment'));
    }

    public function invoice(Request $request, Shipment $shipment): BinaryFileResponse
    {
        $this->authorizeShipmentOwner($request, $shipment);
        abort_unless($shipment->invoice_path && Storage::disk('local')->exists($shipment->invoice_path), 404);

        return response()->download(
            Storage::disk('local')->path($shipment->invoice_path),
            'shipment-invoice.pdf',
            ['Content-Type' => 'application/pdf'],
        );
    }

    public function updateLocation(Request $request, Shipment $shipment): RedirectResponse
    {
        $this->authorizeShipmentOwner($request, $shipment);

        abort_if(
            in_array($shipment->status, [ShipmentStatus::Delivered, ShipmentStatus::Cancelled], true),
            403,
            __('app.shipments.location_locked'),
        );

        $data = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $shipment->forceFill([
            'receiver_latitude' => round((float) $data['latitude'], 7),
            'receiver_longitude' => round((float) $data['longitude'], 7),
        ])->save();

        return back()->with('status', __('app.shipments.location_updated'));
    }

    public function cancel(Request $request, Shipment $shipment, ShipmentStatusService $statuses): RedirectResponse
    {
        $this->authorizeShipmentOwner($request, $shipment);
        $statuses->transition($request->user(), $shipment, \App\Enums\ShipmentStatus::Cancelled);

        return back()->with('status', __('app.shipments.cancelled'));
    }

    private function authorizeCustomer(Request $request): void
    {
        abort_unless($request->user()?->hasRole(UserRoles::Customer->value), 403);
    }

    private function authorizeShipmentOwner(Request $request, Shipment $shipment): void
    {
        abort_unless(
            $request->user()?->hasRole(UserRoles::Customer->value)
                && $shipment->customer_id === $request->user()->id,
            404,
        );
    }
}