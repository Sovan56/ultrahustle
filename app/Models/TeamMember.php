<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeamMember extends Model
{
    protected $table = 'team_members';

    // ✅ make sure role is fillable
    protected $fillable = [
        'team_id',
        'positions',
        'role',          // <— add this
        'member_id',
        'member_email',
        'status',
        'invite_token',
        'invited_at',
        'responded_at',
        'invited_by',
    ];

    protected $casts = [
        'invited_at'   => 'datetime',
        'responded_at' => 'datetime',
    ];

    public function team()
    {
        return $this->belongsTo(UserAdminTeam::class, 'team_id');
    }
}
