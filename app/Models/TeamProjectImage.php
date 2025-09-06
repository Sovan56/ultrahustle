<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeamProjectImage extends Model
{
    protected $fillable = ['project_id', 'image_path'];

    public function project() {
        return $this->belongsTo(TeamProject::class, 'project_id');
    }

    public function getUrlAttribute(): string {
        // Use your /media route
        return url('media/'.$this->image_path);
    }
}
