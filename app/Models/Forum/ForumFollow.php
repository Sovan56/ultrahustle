<?php

namespace App\Models\Forum;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ForumFollow extends Model
{
    protected $fillable = ['follower_id','followed_id'];

    public function follower() { return $this->belongsTo(User::class, 'follower_id'); }
    public function followed() { return $this->belongsTo(User::class, 'followed_id'); }
}
