<?php

namespace App\Models\Forum;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ForumCommentLike extends Model
{
    protected $fillable = ['comment_id','user_id'];

    public function comment() { return $this->belongsTo(ForumComment::class, 'comment_id'); }
    public function user()    { return $this->belongsTo(User::class); }
}
