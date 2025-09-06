<?php

namespace App\Mail;

use App\Models\UserAdminTeam;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TeamDeletedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public UserAdminTeam $team, public string $email) {}

    public function build()
    {
        return $this->subject('Team deleted: '.$this->team->team_name)
            ->view('emails.team_deleted')
            ->with(['team'=>$this->team, 'email'=>$this->email]);
    }
}
