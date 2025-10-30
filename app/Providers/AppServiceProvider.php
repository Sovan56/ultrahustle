<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Blade;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        View::composer('UserAdmin.*', function ($view) {
            $u = auth()->user() ?? \App\Models\User::find(session('user_id'));

            // Avatar (your existing logic)
            $raw = $u?->anotherDetail?->profile_picture;
            if ($raw) {
                if (!Str::startsWith($raw, ['http://', 'https://', '/media/', '/storage/'])) {
                    $avatar = route('media.pass', ['path' => ltrim($raw, '/')]);
                } else {
                    $avatar = Str::startsWith($raw, '/storage/')
                        ? route('media.pass', ['path' => ltrim($raw, '/')])
                        : $raw;
                }
            } else {
                $avatar = asset('assets/img/users/user-1.png');
            }

            $displayName = trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? ''))
                ?: ($u->name ?? (isset($u->email) ? Str::before($u->email, '@') : 'User'));

            // NEW: expose mode
            $isCreator = (int)($u->user_state ?? 0) === 1;

            $view->with(compact('u', 'avatar', 'displayName', 'isCreator'));
        });

        // Optional but nice: global Blade conditionals
        Blade::if('creator', function () {
            $u = auth()->user() ?? \App\Models\User::find(session('user_id'));
            return (int)($u->user_state ?? 0) === 1;
        });

        Blade::if('client', function () {
            $u = auth()->user() ?? \App\Models\User::find(session('user_id'));
            return (int)($u->user_state ?? 0) === 0;
        });
    }
}
