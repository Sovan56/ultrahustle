<?php

namespace App\Mail\Service;

use App\Models\ServiceOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContractSentMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ServiceOrder $order) {}

    public function build()
    {
        return $this->subject('New Contract from Seller')
            ->markdown('mail.service.contract_sent', ['order'=>$this->order]);
    }
}
