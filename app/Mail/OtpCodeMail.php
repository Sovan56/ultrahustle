<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OtpCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $code;

    public function __construct(string $code) { $this->code = $code; }

    public function build()
    {
        return $this->subject('Your Password Reset Code')
            ->view('emails.otp-code')
            ->with(['code' => $this->code]);
    }
}
