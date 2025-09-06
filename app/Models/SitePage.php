<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SitePage extends Model
{
    protected $table = 'site_pages';
    protected $fillable = ['slug','title','content','is_published'];
    protected $casts = ['is_published' => 'boolean'];
}
?>