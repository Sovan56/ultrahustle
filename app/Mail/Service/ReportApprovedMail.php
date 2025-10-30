<?php

namespace App\Mail\Service;

use App\Models\ServiceReport;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReportApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ServiceReport $report) {}

    public function build()
    {
        return $this->subject('Your Report Was Approved')
            ->markdown('mail.service.report_approved', [
                'report' => $this->report,
                'order'  => $this->report->order,
            ]);
    }
}
