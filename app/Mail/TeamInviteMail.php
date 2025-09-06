<?php

namespace App\Mail;

use App\Models\UserAdminTeam;
use App\Models\TeamMember;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TeamInviteMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public UserAdminTeam $team, public TeamMember $invite, public string $acceptUrl) {}

    public function build()
    {
        return $this->subject('You are invited: '.$this->team->team_name)
            ->view('emails.team_invite')
            ->with([
                'team'      => $this->team,
                'invite'    => $this->invite,
                'acceptUrl' => $this->acceptUrl,
            ]);
    }
}
