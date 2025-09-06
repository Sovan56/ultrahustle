<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeamProject extends Model
{
    protected $fillable = ['team_id', 'title', 'description'];

    public function team() {
        return $this->belongsTo(UserAdminTeam::class, 'team_id');
    }

    public function images() {
        return $this->hasMany(TeamProjectImage::class, 'project_id');
    }
}
