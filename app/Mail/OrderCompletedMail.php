<?php

namespace App\Mail;

use App\Models\ProductOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderCompletedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ProductOrder $order, public array $seller) {}

    public function build()
    {
        return $this->subject('Order completed')
            ->view('emails.order_completed')
            ->with(['order'=>$this->order,'seller'=>$this->seller]);
    }
}
