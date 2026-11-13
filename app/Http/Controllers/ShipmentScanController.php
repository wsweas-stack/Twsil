<?php

namespace App\Http\Controllers;

use App\Enums\UserRoles;
use App\Services\ShipmentScanService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ShipmentScanController extends Controller
{
    public function form(Request $request): View
    {
        abort_unless($request->user()->hasInternalAccess(), 403);

        return view('internal.shipment-scan');
    }

    public function scan(Request $request, ShipmentScanService $scanner): RedirectResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'size:64', 'regex:/^[A-Za-z0-9]+$/'],
        ]);

        try {
            $shipment = $scanner->scanForPickup($request->user(), $validated['token']);
        } catch (HttpException $exception) {
            if ($exception->getStatusCode() !== 403) {
                throw $exception;
            }

            return redirect()
                ->route('shipments.scan.form')
                ->withErrors(['token' => __('app.shipments.scan_forbidden')]);
        } catch (ModelNotFoundException) {
            return redirect()
                ->route('shipments.scan.form')
                ->withErrors(['token' => __('app.shipments.scan_not_found')]);
        }

        return redirect('/admin/manage-shipments')
            ->with('status', __('app.shipments.pickup_confirmed', ['tracking' => $shipment->tracking_number]));
    }
}