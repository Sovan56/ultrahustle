<?php

namespace App\Models\Forum;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ForumThreadLike extends Model
{
    protected $fillable = ['thread_id','user_id'];

    public function thread() { return $this->belongsTo(ForumThread::class, 'thread_id'); }
    public function user()   { return $this->belongsTo(User::class); }
}
