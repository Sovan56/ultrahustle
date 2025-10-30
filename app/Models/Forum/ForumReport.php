<?php

namespace App\Models\Forum;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ForumReport extends Model
{
    protected $fillable = ['thread_id','comment_id','user_id','reason','notes'];

    public function thread() { return $this->belongsTo(ForumThread::class, 'thread_id'); }
    public function comment(){ return $this->belongsTo(ForumComment::class, 'comment_id'); }
    public function user()   { return $this->belongsTo(User::class); }
}
