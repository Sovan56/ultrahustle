<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserAdminTeam extends Model
{
    protected $table = 'user_admin_teams';

    protected $fillable = [
        'team_name', 'team_owner_id', 'profile_image', 'about',
    ];

    public function members(): HasMany
    {
        return $this->hasMany(TeamMember::class, 'team_id');
    }

    public function projects() {
    return $this->hasMany(\App\Models\TeamProject::class, 'team_id');
}
}
