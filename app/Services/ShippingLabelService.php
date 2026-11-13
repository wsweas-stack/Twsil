<?php

namespace App\Services;

use App\Enums\UserRoles;
use App\Models\Shipment;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ShippingLabelService
{
    public function __construct(private readonly ShipmentQrCodeService $qrCodes) {}

    public function render(User $actor, Shipment $shipment): string
    {
        $this->authorize($actor, $shipment);
        $qrCode = $this->qrCodes->ensureFor($shipment);
        $qrPath = Storage::disk('local')->path($qrCode->image_path);
        abort_unless(is_file($qrPath), 404);

        $qrDataUri = 'data:image/png;base64,' . base64_encode(file_get_contents($qrPath));

        return Pdf::loadView('labels.shipment', compact('shipment', 'qrDataUri'))
            ->setPaper('a6', 'portrait')
            ->output();
    }

    private function authorize(User $actor, Shipment $shipment): void
    {
        if ($actor->hasRole(UserRoles::Customer->value)) {
            abort_unless($shipment->customer_id === $actor->id, 404);

            return;
        }

        abort_unless($actor->hasInternalAccess(), 403);

        if ($actor->hasRole(UserRoles::Driver->value)) {
            abort_unless($shipment->driver_id === $actor->id, 404);

            return;
        }

        abort_unless(
            $actor->hasRole(UserRoles::Admin->value) && $actor->can('shipments.view_any')
                || $actor->hasRole(UserRoles::Employee->value) && $actor->can('shipments.view_any'),
            403,
        );
    }
}