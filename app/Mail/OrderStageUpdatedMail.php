<?php

namespace App\Mail;

use App\Models\ProductOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderStageUpdatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ProductOrder $order, public array $seller) {}

    public function build()
    {
        return $this->subject('Order update: stages changed')
            ->view('emails.order_stage_updated')
            ->with(['order'=>$this->order,'seller'=>$this->seller]);
    }
}
