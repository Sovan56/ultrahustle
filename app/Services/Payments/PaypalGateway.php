<?php

namespace App\Services\Payments;

use GuzzleHttp\Client;

class PayPalGateway
{
    protected string $base;
    protected string $clientId;
    protected string $secret;

    public function __construct()
    {
        $mode = config('services.paypal.mode', 'sandbox');
        $this->base = $mode === 'live' ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com';
        $this->clientId = config('services.paypal.client_id');
        $this->secret   = config('services.paypal.secret');
    }

    protected function token(): string
    {
        $http = new Client();
        $res = $http->post("{$this->base}/v1/oauth2/token", [
            'auth'        => [$this->clientId, $this->secret],
            'form_params' => ['grant_type' => 'client_credentials'],
            'http_errors' => true,
            'timeout'     => 20,
        ]);
        $data = json_decode((string)$res->getBody(), true);
        return $data['access_token'] ?? '';
    }

   public function createOrder(float $amount, string $currency, array $ctx = []): array
{
    $token = $this->token();
    $http  = new \GuzzleHttp\Client();

    $body = [
        'intent' => 'CAPTURE',
        'purchase_units' => [[
            'amount' => [
                'currency_code' => strtoupper($currency),
                'value' => number_format($amount, 2, '.', '')
            ],
        ]],
    ];

    if (!empty($ctx['return_url']) && !empty($ctx['cancel_url'])) {
        $body['application_context'] = [
            'return_url'  => $ctx['return_url'],
            'cancel_url'  => $ctx['cancel_url'],
            'user_action' => 'PAY_NOW',
        ];
    }

    $res = $http->post("{$this->base}/v2/checkout/orders", [
        'headers' => ['Authorization' => "Bearer {$token}", 'Content-Type' => 'application/json'],
        'json'    => $body,
        'http_errors' => true,
        'timeout'     => 20,
    ]);

    $data = json_decode((string)$res->getBody(), true);
    $approve = collect($data['links'] ?? [])->firstWhere('rel', 'approve');

    return [
        'orderID'    => $data['id'] ?? null,
        'approveUrl' => $approve['href'] ?? null,
    ];
}


    public function captureOrder(string $orderId): array
    {
        $token = $this->token();
        $http  = new Client();
        $res = $http->post("{$this->base}/v2/checkout/orders/{$orderId}/capture", [
            'headers'     => ['Authorization' => "Bearer {$token}", 'Content-Type' => 'application/json'],
            'http_errors' => true,
            'timeout'     => 20,
        ]);
        return json_decode((string)$res->getBody(), true);
    }
}
