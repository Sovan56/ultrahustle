<?php

namespace App\Services\Payouts;

use Illuminate\Support\Facades\Http;

class PaypalPayouts
{
    protected string $base;
    protected string $clientId;
    protected string $secret;

    public function __construct()
    {
        $live = (bool) config('services.paypal.live', false);
        $this->base = $live ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com';
        $this->clientId = config('services.paypal.client_id');
        $this->secret   = config('services.paypal.secret');
    }

    protected function token(): string
    {
        $res = Http::asForm()
            ->withBasicAuth($this->clientId, $this->secret)
            ->post($this->base . '/v1/oauth2/token', ['grant_type' => 'client_credentials'])
            ->throw()->json();
        return $res['access_token'];
    }

    /** Send payout to a PayPal email */
    public function createPayout(string $email, string $amount, string $currency, string $reference): array
    {
        $tok = $this->token();
        $payload = [
            'sender_batch_header' => [
                'sender_batch_id' => $reference, // idempotency
                'email_subject'   => 'You have a payout',
            ],
            'items' => [[
                'recipient_type' => 'EMAIL',
                'receiver'       => $email,
                'note'           => 'Wallet withdrawal',
                'sender_item_id' => $reference,
                'amount' => [
                    'currency' => $currency,
                    'value'    => number_format((float)$amount, 2, '.', ''),
                ],
            ]],
        ];

        $res = Http::withToken($tok)
            ->post($this->base . '/v1/payments/payouts', $payload)
            ->throw()
            ->json();

        return $res; // contains batch_id and payout_item_id/status
    }
}
