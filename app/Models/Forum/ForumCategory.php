<?php

namespace App\Models\Forum;

use Illuminate\Database\Eloquent\Model;

class ForumCategory extends Model
{

    protected $table = 'forum_categories';
    protected $fillable = [
        'id','name','slug','color_bg','color_fg','color_border',
    ];

    public function threads()
    {
        return $this->hasMany(ForumThread::class, 'category_id');
    }
}
