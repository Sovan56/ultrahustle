<?php

namespace App\Mail;

use App\Models\ProductOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DigitalDeliveryMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ProductOrder $order, public array $seller, public array $fileUrls) {}

    public function build()
    {
        return $this->subject('Your digital files are ready')
            ->view('emails.digital_delivery')
            ->with(['order'=>$this->order,'seller'=>$this->seller,'fileUrls'=>$this->fileUrls]);
    }
}
