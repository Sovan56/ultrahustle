<?php

namespace App\Models\Forum;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ForumThread extends Model
{
    protected $fillable = [
        'user_id','category_id','title','post_type','excerpt','body_html',
        'media_url','media_poster','media_alt',
        'likes_count','comments_count','saves_count','shares_count',
    ];

    protected $casts = [
        'likes_count'    => 'integer',
        'comments_count' => 'integer',
        'saves_count'    => 'integer',
        'shares_count'   => 'integer',
    ];

    public function user() { return $this->belongsTo(User::class); }
    public function category() { return $this->belongsTo(ForumCategory::class, 'category_id'); }
    public function poll(): HasOne { return $this->hasOne(ForumPoll::class, 'thread_id'); }
    public function comments() { return $this->hasMany(ForumComment::class, 'thread_id'); }
    public function likes() { return $this->hasMany(ForumThreadLike::class, 'thread_id'); }
    public function saves() { return $this->hasMany(ForumThreadSave::class, 'thread_id'); }
    public function shares() { return $this->hasMany(ForumThreadShare::class, 'thread_id'); }

    public function scopeWithBasics($q)
    {
        return $q->with(['user','category']);
    }

    public function isLikedBy(?int $userId): bool
    {
        if (!$userId) return false;
        return $this->likes()->where('user_id',$userId)->exists();
    }

    public function isSavedBy(?int $userId): bool
    {
        if (!$userId) return false;
        return $this->saves()->where('user_id',$userId)->exists();
    }
}
