<?php

namespace App\Mail;

use App\Models\UserAdminTeam;
use App\Models\TeamMember;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TeamRemovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public UserAdminTeam $team, public TeamMember $member) {}

    public function build()
    {
        return $this->subject('Removed from '.$this->team->team_name)
            ->view('emails.team_removed')
            ->with(['team'=>$this->team, 'member'=>$this->member]);
    }
}
