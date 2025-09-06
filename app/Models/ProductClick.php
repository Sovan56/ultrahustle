<?php
// app/Models/ProductClick.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductClick extends Model
{
    protected $fillable = ['product_id','user_id','source','ip','user_agent'];
}
