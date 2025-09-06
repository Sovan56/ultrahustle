<?php

namespace App\Services\Payouts;

use Illuminate\Support\Facades\Http;
use App\Models\UserPayoutAccount;

class RazorpayXPayouts
{
    protected string $base = 'https://api.razorpay.com/v1';
    protected string $key;
    protected string $secret;
    protected ?string $accountNumber; // your RazorpayX virtual account

    public function __construct()
    {
        $this->key    = config('services.razorpayx.key');
        $this->secret = config('services.razorpayx.secret');
        $this->accountNumber = config('services.razorpayx.account_number'); // e.g. "23232300232323"
    }

    protected function http()
    {
        return Http::withBasicAuth($this->key, $this->secret);
    }

    public function ensureContactAndFundAccount(UserPayoutAccount $acc): array
    {
        $meta = $acc->meta ?? [];
        if (!isset($meta['contact_id'])) {
            $c = $this->http()->post($this->base . '/contacts', [
                'name'  => $acc->holder_name,
                'email' => $acc->user->email,
                'contact' => $acc->user->phone_number ?? null,
                'type'  => 'customer',
                'reference_id' => 'U'.$acc->user_id,
            ])->throw()->json();
            $meta['contact_id'] = $c['id'] ?? null;
        }

        if (!isset($meta['fund_account_id'])) {
            if ($acc->type === 'bank') {
                $fa = $this->http()->post($this->base . '/fund_accounts', [
                    'contact_id' => $meta['contact_id'],
                    'account_type' => 'bank_account',
                    'bank_account' => [
                        'name'           => $acc->holder_name,
                        'ifsc'           => $acc->ifsc,
                        'account_number' => $acc->account_number,
                    ],
                ])->throw()->json();
            } elseif ($acc->type === 'upi') {
                $fa = $this->http()->post($this->base . '/fund_accounts', [
                    'contact_id' => $meta['contact_id'],
                    'account_type' => 'vpa',
                    'vpa' => [ 'address' => $acc->upi_vpa ],
                ])->throw()->json();
            } else {
                throw new \RuntimeException('Unsupported RazorpayX fund account type');
            }
            $meta['fund_account_id'] = $fa['id'] ?? null;
        }

        $acc->meta = $meta;
        $acc->save();

        return $meta;
    }

    public function createPayout(UserPayoutAccount $acc, int $amountMinor, string $currency, string $reference): array
    {
        $meta = $this->ensureContactAndFundAccount($acc);
        $payload = [
            'account_number' => $this->accountNumber,
            'fund_account_id' => $meta['fund_account_id'],
            'amount' => $amountMinor,
            'currency' => $currency, // INR
            'mode' => $acc->type === 'upi' ? 'UPI' : 'IMPS',
            'purpose' => 'payout',
            'queue_if_low_balance' => true,
            'reference_id' => $reference, // idempotency key
            'narration' => 'Wallet withdrawal',
        ];

        $res = $this->http()
            ->withHeaders(['X-Payout-Idempotency' => $reference])
            ->post($this->base . '/payouts', $payload)
            ->throw()
            ->json();

        return $res;
    }
}
