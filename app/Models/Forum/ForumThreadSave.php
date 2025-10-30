<?php

namespace App\Models\Forum;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ForumThreadSave extends Model
{
    protected $fillable = ['thread_id','user_id'];
    public $timestamps = true; 

    public function thread() { return $this->belongsTo(ForumThread::class, 'thread_id'); }
    public function user()   { return $this->belongsTo(User::class); }
}
