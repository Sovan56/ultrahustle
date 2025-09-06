<?php

namespace App\Support;

use Illuminate\Support\Str;

class Avatar
{
    public static function url($user): string
    {
        if (!$user) return 'https://placehold.co/96x96?text=U';
        $raw = $user->anotherDetail->profile_picture ?? null;

        if (!$raw) return 'https://placehold.co/96x96?text=U';

        if (Str::startsWith($raw, ['http://','https://','/media/','/storage/'])) {
            if (Str::startsWith($raw, ['/storage/'])) {
                return route('media.pass', ['path' => ltrim($raw, '/')]);
            }
            return $raw;
        }
        return route('media.pass', ['path' => ltrim($raw, '/')]);
    }
}
