<?php

namespace App\Http\Controllers;

use App\Models\Shipment;
use App\Services\ShippingLabelService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ShippingLabelController extends Controller
{
    public function __invoke(Request $request, Shipment $shipment, ShippingLabelService $labels): Response
    {
        $pdf = $labels->render($request->user(), $shipment);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="shipment-' . $shipment->tracking_number . '.pdf"',
        ]);
    }
}