<?php

namespace App\Mail\Service;

use App\Models\ServiceOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContractReupdatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ServiceOrder $order) {}

    public function build()
    {
        return $this->subject('Contract Updated by Seller')
            ->markdown('mail.service.contract_reupdated', [
                'order' => $this->order,
            ]);
    }
}
