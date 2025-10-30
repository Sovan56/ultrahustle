<?php

namespace App\Models\Forum;

use Illuminate\Database\Eloquent\Model;

class ForumPollOption extends Model
{
    protected $fillable = ['poll_id','label','votes','position'];

    protected $casts = [
        'votes'    => 'integer',
        'position' => 'integer',
    ];

    public function poll() { return $this->belongsTo(ForumPoll::class, 'poll_id'); }
}
