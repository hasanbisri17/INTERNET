<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Failed;
use Spatie\Activitylog\Models\Activity;

class LogFailedLogin
{
    public function handle(Failed $event): void
    {
        $user = $event->user; // may be null

        $identifier = null;
        if ($user) {
            $identifier = $user->email ?? $user->name ?? ('User#' . ($user->getKey() ?? ''));
        } else {
            $creds = $event->credentials ?? [];
            $usernameField = collect(['email', 'username', 'name'])->first(fn ($k) => isset($creds[$k]));
            $identifier = $usernameField ? ($creds[$usernameField] ?? 'unknown') : 'unknown';
        }

        // Skip if a similar failed login activity was just logged (within 3 seconds)
        $exists = Activity::query()
            ->where('log_name', 'auth')
            ->where('event', 'failed')
            ->when($user, fn ($q) => $q->where('causer_type', get_class($user))->where('causer_id', $user->getKey()))
            ->where('created_at', '>=', now()->subSeconds(3))
            ->exists();
        if ($exists) {
            return;
        }

        $ua = request()->userAgent();
        $ua = is_string($ua) ? mb_substr($ua, 0, 255) : null;

        activity()
            ->event('failed')
            ->inLog('auth')
            ->withProperties([
                'ip' => request()->ip(),
                'user_agent' => $ua,
                'guard' => property_exists($event, 'guard') ? $event->guard : null,
                'email_or_username' => $identifier,
                'path' => request()->path(),
            ])
            ->log("Upaya login gagal untuk: {$identifier}.");
    }
}