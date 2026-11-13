<?php

namespace App\Services;

use App\Models\Shipment;
use App\Models\ShipmentQrCode;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class ShipmentQrCodeService
{
    public function ensureFor(Shipment $shipment): ShipmentQrCode
    {
        $existing = ShipmentQrCode::query()->where('shipment_id', $shipment->id)->first();

        if ($existing !== null) {
            $this->writeImageIfMissing($existing);

            return $existing;
        }

        $token = Str::random(64);
        $imagePath = 'shipment-labels/' . $shipment->id . '/qr.png';
        $this->writeImage($token, $imagePath);

        try {
            return DB::transaction(fn (): ShipmentQrCode => ShipmentQrCode::query()->create([
                'shipment_id' => $shipment->id,
                'token_hash' => hash('sha256', $token),
                'token' => $token,
                'image_path' => $imagePath,
            ]));
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($imagePath);
            $existing = ShipmentQrCode::query()->where('shipment_id', $shipment->id)->first();

            if ($existing !== null) {
                $this->writeImageIfMissing($existing);

                return $existing;
            }

            throw $exception;
        }
    }

    private function writeImageIfMissing(ShipmentQrCode $record): void
    {
        if (! Storage::disk('local')->exists($record->image_path)) {
            $this->writeImage($record->token, $record->image_path);
        }
    }

    private function writeImage(string $token, string $path): void
    {
        $code = new QrCode(
            data: $token,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 360,
            margin: 12,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
            foregroundColor: new Color(32, 36, 43),
            backgroundColor: new Color(255, 255, 255),
        );

        Storage::disk('local')->put($path, (new PngWriter())->write($code)->getString());
    }
}
