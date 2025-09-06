<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Mass assignable attributes.
     */

    // app/Models/User.php
    protected $fillable = [
        'unique_id',
        'first_name',
        'last_name',
        'phone_number',
        'country_id',
        'currency',
        'email',
        'password',
        
        'terms_accepted_at',
        'signup_ip',
        'signup_user_agent',
        'last_seen_at',
    ];

    protected $casts = [
        'terms_accepted_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Attribute casting.
     */
    protected function casts(): array
    {
       return [
        'email_verified_at'     => 'datetime',
        'password'              => 'hashed',
        'twofa_enabled'         => 'boolean',
        'twofa_recovery_codes'  => 'array',
        'terms_accepted_at'   => 'datetime',
    ];
    }

    /**
     * Auto-generate unique_id on create.
     */
    protected static function booted(): void
    {
        static::creating(function (User $user) {
            if (empty($user->unique_id)) {
                // 10-char uppercase ID, regenerate if a collision ever happens.
                do {
                    $candidate = strtoupper(Str::random(10));
                } while (static::where('unique_id', $candidate)->exists());

                $user->unique_id = $candidate;
            }
        });
    }

    /**
     * Convenience accessor for full name (optional).
     */
    public function getNameAttribute(): string
    {
        return trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? ''));
    }

    public function anotherDetail()
    {
        return $this->hasOne(\App\Models\UserAdminAnotherDetail::class, 'user_admin_id', 'unique_id');
    }

    // ..
    public function country()
    {
        return $this->belongsTo(\App\Models\Country::class, 'country_id');
    }

    public function kycSubmission()
    {
        return $this->hasOne(\App\Models\UserKycSubmission::class);
    }
}
