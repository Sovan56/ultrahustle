<?php

use Illuminate\Support\Str;

if (! function_exists('user_avatar_url')) {
    /**
     * Resolve a user avatar path from users / user_admin_another_details to a public URL.
     * Accepts Eloquent model, stdClass, or array. Falls back to placeholder.
     */
    function user_avatar_url($user, string $fallback = 'https://placehold.co/64x64?text=U'): string
    {
        $path = data_get($user, 'anotherDetail.profile_picture')   // Product::user->anotherDetail
             ?: data_get($user, 'profile_picture')                 // direct field
             ?: data_get($user, 'avatar')                          // generic alias
             ?: null;

        if (! $path) {
            return $fallback;
        }

        // Already absolute or served paths?
        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        // Normalize /storage/* and any relative stored asset via our /media passthrough
        if (Str::startsWith($path, ['/media/', '/storage/'])) {
            $path = ltrim($path, '/'); // media.pass expects no leading slash
        }

        return route('media.pass', ['path' => ltrim($path, '/')]);
    }
}
