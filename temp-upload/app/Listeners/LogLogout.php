<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Logout;
use Spatie\Activitylog\Models\Activity;

class LogLogout
{
    public function handle(Logout $event): void
    {
        $user = $event->user;

        // Skip if a similar logout activity was just logged (within 3 seconds)
        if ($user && Activity::query()
            ->where('log_name', 'auth')
            ->where('event', 'logout')
            ->where('causer_type', get_class($user))
            ->where('causer_id', $user->getKey())
            ->where('created_at', '>=', now()->subSeconds(3))
            ->exists()) {
            return;
        }

        $display = null;
        if ($user) {
            $display = $user->name ?? $user->email ?? ('User#' . ($user->getKey() ?? ''));
        }

        $ua = request()->userAgent();
        $ua = is_string($ua) ? mb_substr($ua, 0, 255) : null;

        activity()
            ->causedBy($user)
            ->event('logout')
            ->inLog('auth')
            ->withProperties([
                'ip' => request()->ip(),
                'user_agent' => $ua,
                'guard' => property_exists($event, 'guard') ? $event->guard : null,
                'path' => request()->path(),
            ])
            ->log("Pengguna {$display} keluar.");
    }
}