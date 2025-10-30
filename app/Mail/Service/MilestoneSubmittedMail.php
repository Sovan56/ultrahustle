<?php

namespace App\Mail\Service;

use App\Models\ServiceMilestone;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MilestoneSubmittedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ServiceMilestone $milestone) {}

    public function build()
    {
        $order = $this->milestone->order;
        return $this->subject('Milestone Submitted')
            ->markdown('mail.service.milestone_submitted', [
                'milestone' => $this->milestone,
                'order'     => $order,
            ]);
    }
}
