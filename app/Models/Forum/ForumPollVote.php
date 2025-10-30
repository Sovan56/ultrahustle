<?php

namespace App\Models\Forum;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ForumPollVote extends Model
{
    protected $fillable = ['poll_id','option_id','user_id'];

    public function poll()   { return $this->belongsTo(ForumPoll::class, 'poll_id'); }
    public function option() { return $this->belongsTo(ForumPollOption::class, 'option_id'); }
    public function user()   { return $this->belongsTo(User::class); }
}
