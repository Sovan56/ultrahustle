<?php

namespace App\Mail;

use App\Models\ProductOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BuyerApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ProductOrder $order, public array $buyer) {}

    public function build()
    {
        return $this->subject('Buyer approved your delivery')
            ->view('emails.buyer_approved')
            ->with(['order'=>$this->order,'buyer'=>$this->buyer]);
    }
}
