<?php

namespace App\Services\Payments;

use Razorpay\Api\Api;

class RazorpayGateway implements PaymentGatewayInterface
{
    protected Api $api;

    public function __construct()
    {
        $this->api = new Api(config('services.razorpay.key'), config('services.razorpay.secret'));
    }

    public function createOrder(string $receipt, int $amountMinor, string $currency): array
    {
        $order = $this->api->order->create([
            'receipt'         => $receipt,
            'amount'          => $amountMinor, // e.g., 100 INR -> 10000 paise
            'currency'        => $currency,    // 'INR','USD' (depends on user’s country choice)
            'payment_capture' => 1,
        ]);
        return $order->toArray();
    }

    public function verifySignature(string $orderId, string $paymentId, string $signature): bool
    {
        $payload = $orderId . '|' . $paymentId;
        $expected = hash_hmac('sha256', $payload, config('services.razorpay.secret'));
        return hash_equals($expected, $signature);
    }
}
