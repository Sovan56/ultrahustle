<?php

namespace App\Services\Payments;

interface PaymentGatewayInterface
{
    /** Create an order for adding funds (smallest currency unit where applicable) */
    public function createOrder(string $receipt, int $amountMinor, string $currency): array;

    /** Verify the callback signature */
    public function verifySignature(string $orderId, string $paymentId, string $signature): bool;
}
