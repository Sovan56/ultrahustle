<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExpertBadge extends Model
{
    protected $fillable = ['user_id','granted_by','note'];
    public function user(){ return $this->belongsTo(User::class, 'user_id'); }
}
