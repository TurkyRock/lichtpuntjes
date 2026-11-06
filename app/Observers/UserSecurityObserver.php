<?php
namespace App\Observers;
use App\Models\User;
use App\Services\AccountSecurity;
use Illuminate\Support\Str;
class UserSecurityObserver {
    public function updating(User $user): void {
        if ($user->isDirty('password') || $user->isDirty('email')) {
            $user->security_version = (int)$user->security_version + 1;
            $user->remember_token = Str::random(60);
        }
        if ($user->isDirty('email')) $user->email_verified_at = null;
    }
    public function updated(User $user): void {
        if ($user->wasChanged('password')) app(AccountSecurity::class)->passwordChanged($user);
        if ($user->wasChanged('email')) {
            app(AccountSecurity::class)->record($user, 'email_changed');
            try { \Illuminate\Support\Facades\Notification::route('mail', $user->getRawOriginal('email'))->notify(new \App\Notifications\AccountSecurityNotice('email_changed')); }
            catch (\Throwable $e) { app(AccountSecurity::class)->record($user, 'notification_delayed'); }
        }
    }
}
