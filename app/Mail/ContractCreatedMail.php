<?php

namespace App\Mail\Contracts;

use App\Models\ServiceContract;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ContractCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ServiceContract $contract) {}

    public function build()
    {
        return $this->subject('New Contract')
            ->view('emails.contracts.created', ['c'=>$this->contract]);
    }
}
