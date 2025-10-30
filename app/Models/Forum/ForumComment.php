<?php

namespace App\Models\Forum;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ForumComment extends Model
{
    protected $fillable = ['thread_id','user_id','parent_id','body_html','likes_count'];

    protected $casts = [
        'likes_count' => 'integer',
    ];

    public function thread() { return $this->belongsTo(ForumThread::class, 'thread_id'); }
    public function user()   { return $this->belongsTo(User::class); }
    public function parent() { return $this->belongsTo(ForumComment::class, 'parent_id'); }
    public function replies(){ return $this->hasMany(ForumComment::class, 'parent_id')->orderBy('created_at'); }
    public function likes()  { return $this->hasMany(ForumCommentLike::class, 'comment_id'); }
}
