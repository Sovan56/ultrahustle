<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserKycSubmission extends Model
{
    protected $table = 'user_kyc_submissions';
    protected $guarded = [];               // <— IMPORTANT so updateOrCreate() works
    protected $casts = ['dob' => 'date:Y-m-d'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Media URLs via /media passthrough
    public function getIdFrontUrlAttribute(){ return $this->id_front_path ? url('/media/'.ltrim($this->id_front_path,'/')) : null; }
    public function getIdBackUrlAttribute(){  return $this->id_back_path  ? url('/media/'.ltrim($this->id_back_path,'/'))  : null; }
    public function getSelfieUrlAttribute(){  return $this->selfie_path   ? url('/media/'.ltrim($this->selfie_path,'/'))   : null; }
}
