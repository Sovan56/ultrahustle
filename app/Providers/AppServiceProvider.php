<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('UserAdmin.*', function ($view) {
        $u = auth()->user();

        $raw = $u?->anotherDetail?->profile_picture;
        if ($raw) {
            if (!Str::startsWith($raw, ['http://','https://','/media/','/storage/'])) {
                $avatar = route('media.pass', ['path' => ltrim($raw, '/')]);
            } else {
                $avatar = Str::startsWith($raw, '/storage/')
                    ? route('media.pass', ['path' => ltrim($raw, '/')])
                    : $raw;
            }
        } else {
            $avatar = asset('assets/img/users/user-1.png');
        }

        $displayName = trim(($u->first_name ?? '').' '.($u->last_name ?? '')) ?: ($u->name ?? (isset($u->email) ? Str::before($u->email, '@') : 'User'));

        $view->with(compact('u','avatar','displayName'));
    });
    }
}
