<?php

namespace App\Mail\Service;

use App\Models\ServiceOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContractApprovedPaidMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ServiceOrder $order) {}

    public function build()
    {
        return $this->subject('Contract Approved & Paid')
            ->markdown('mail.service.contract_approved_paid', [
                'order' => $this->order,
            ]);
    }
}
