<?php

namespace App\Contracts;

use App\Models\Payment;
use App\Models\Shipment;

interface CheckoutSessionGateway
{
    /** @return array{id: string, url: string} */
    public function create(Payment $payment, Shipment $shipment): array;

    /** @return array<string, mixed> */
    public function retrieve(string $sessionId): array;

    /** @return array<string, mixed> */
    public function expire(string $sessionId): array;
}