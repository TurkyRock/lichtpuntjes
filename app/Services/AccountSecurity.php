<?php
namespace App\Services;
use App\Models\User;
use Illuminate\Support\Facades\DB;
class AccountSecurity {
    public function record(User $user, string $kind): void {
        DB::table('account_security_events')->insert(['user_id'=>$user->id,'kind'=>$kind,'created_at'=>now()]);
    }
    public function passwordChanged(User $user): void {
        $this->record($user, 'password_changed');
        try { $user->notify(new \App\Notifications\AccountSecurityNotice('password_changed')); }
        catch (\Throwable $e) { $this->record($user, 'notification_delayed'); }
    }
}
