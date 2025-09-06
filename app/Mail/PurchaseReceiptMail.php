<?php

namespace App\Mail;

use App\Models\ProductOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PurchaseReceiptMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ProductOrder $order, public array $buyer, public array $seller, public string $pdfPath) {}

    public function build()
    {
        return $this->subject('Purchase receipt')
            ->view('emails.purchase_receipt')
            ->attach($this->pdfPath, ['as' => 'invoice.pdf'])
            ->with(['order'=>$this->order,'buyer'=>$this->buyer,'seller'=>$this->seller]);
    }
}
