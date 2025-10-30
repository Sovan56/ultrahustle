<?php

namespace App\Mail\Service;

use App\Models\ServiceReport;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReportFiledMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ServiceReport $report) {}

    public function build()
    {
        return $this->subject('New Service Contract Report Filed')
            ->markdown('mail.service.report_filed', [
                'report' => $this->report,
                'order'  => $this->report->order,
            ]);
    }
}
