<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceMilestoneSubmission extends Model
{
    protected $fillable = [
        'service_milestone_id','seller_id','note',
        'file_path','file_name','file_size','file_mime','url'
    ];

    public function milestone() { return $this->belongsTo(ServiceMilestone::class, 'service_milestone_id'); }
    public function seller() { return $this->belongsTo(User::class, 'seller_id'); }
}
