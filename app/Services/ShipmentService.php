<?php

namespace App\Services;

use App\Enums\ShipmentStatus;
use App\Models\Shipment;
use App\Models\ShipmentEvent;
use App\Models\User;
use App\Notifications\OperationalNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class ShipmentService
{
    public function __construct(
        private readonly InvoiceTextExtractor $invoiceTextExtractor,
        private readonly ShipmentQrCodeService $qrCodes,
    ) {}

    /** @param array<string, mixed> $data */
    public function createForCustomer(User $customer, array $data, ?UploadedFile $invoice = null): Shipment
    {
        $storedPath = null;

        try {
            $shipment = DB::transaction(function () use ($customer, $data, $invoice, &$storedPath): Shipment {
                if ($invoice !== null) {
                    $storedPath = $invoice->storeAs(
                        'shipments/' . $customer->id,
                        'invoice-' . Str::uuid() . '.pdf',
                        'local',
                    );
                }

                $shipment = Shipment::query()->create([
                    ...$data,
                    'customer_id' => $customer->id,
                    'tracking_number' => $this->newTrackingNumber(),
                    'status' => ShipmentStatus::Pending,
                    'delivery_fee' => '0.00',
                    'currency' => config('app.currency', 'SAR'),
                    'invoice_path' => $storedPath,
                    'invoice_extracted_text' => $storedPath
                        ? $this->invoiceTextExtractor->extract(Storage::disk('local')->path($storedPath))
                        : null,
                ]);

                ShipmentEvent::query()->create([
                    'shipment_id' => $shipment->id,
                    'actor_id' => $customer->id,
                    'status' => ShipmentStatus::Pending->value,
                    'type' => 'shipment_created',
                    'description' => 'Shipment created',
                    'occurred_at' => now(),
                ]);

                return $shipment;
            });

            $this->qrCodes->ensureFor($shipment);
            $customer->notify(new OperationalNotification(
                'shipment_created',
                'app.notifications.shipment_created_subject',
                'app.notifications.shipment_created',
                route('account.shipments.show', $shipment),
                ['tracking' => $shipment->tracking_number],
            ));

            return $shipment;
        } catch (Throwable $exception) {
            if ($storedPath !== null) {
                Storage::disk('local')->delete($storedPath);
            }

            throw $exception;
        }
    }

    private function newTrackingNumber(): string
    {
        do {
            $trackingNumber = 'TW-' . Str::upper(Str::random(10));
        } while (Shipment::query()->where('tracking_number', $trackingNumber)->exists());

        return $trackingNumber;
    }
}