<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserAdminAnotherDetail extends Model
{
    protected $table = 'user_admin_another_details';

    protected $fillable = [
        'user_admin_id',       // maps to users.unique_id (string)
        'profile_picture',     // storage path on public disk
        'location',            // added column
        'social_media_link',   // JSON
        'profile_description', // HTML from Summernote (sanitized)
    ];

    protected $casts = [
        'social_media_link' => 'array',
    ];
}
