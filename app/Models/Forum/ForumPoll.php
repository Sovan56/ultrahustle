<?php

namespace App\Models\Forum;

use Illuminate\Database\Eloquent\Model;

class ForumPoll extends Model
{
    protected $fillable = ['thread_id','multiple','total_votes'];

    protected $casts = [
        'multiple'    => 'boolean',
        'total_votes' => 'integer',
    ];

    public function thread() { return $this->belongsTo(ForumThread::class, 'thread_id'); }
    public function options() { return $this->hasMany(ForumPollOption::class, 'poll_id')->orderBy('position'); }
    public function votes() { return $this->hasMany(ForumPollVote::class, 'poll_id'); }

    public function hasVoted(int $userId): bool
    {
        return $this->votes()->where('user_id',$userId)->exists();
    }
}
