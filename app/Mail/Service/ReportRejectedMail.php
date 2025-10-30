<?php

namespace App\Mail\Service;

use App\Models\ServiceReport;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReportRejectedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ServiceReport $report) {}

    public function build()
    {
        return $this->subject('Your Report Was Rejected')
            ->markdown('mail.service.report_rejected', [
                'report' => $this->report,
                'order'  => $this->report->order,
            ]);
    }
}
