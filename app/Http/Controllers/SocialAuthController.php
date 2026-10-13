<?php
namespace App\Http\Controllers;

use App\Models\SocialAccount;
use App\Models\User;
use App\Services\SocialOAuth;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SocialAuthController extends Controller
{
    public function start(Request $r, string $provider, SocialOAuth $oauth)
    {
        abort_unless($oauth->available($provider), 404);
        if (!$oauth->sameOrigin($r, $provider)) return $this->failure('Open Lichtpunt op hetzelfde adres als de ingestelde callback.', 'Open Lichtpunt at the same address as the configured callback.');
        $r->session()->forget(['social.pending', 'social.flow']);
        $r->session()->put('social.flow', ['provider' => $provider, 'started' => time(), 'locale' => app()->getLocale(), 'invite' => preg_match('/^[a-f0-9]{32}$/', (string) $r->query('invite')) ? $r->query('invite') : null]);
        try { return $oauth->redirect($provider); }
        catch (\Throwable $e) {
            $r->session()->forget('social.flow');
            return $this->failure('De koppeling kon niet worden gestart. Probeer het later nog eens.', 'Sign-in could not be started. Please try again later.');
        }
    }

    public function callback(Request $r, string $provider, SocialOAuth $oauth)
    {
        abort_unless($oauth->available($provider), 404);
        $flow = $r->session()->pull('social.flow');
        if (!is_array($flow) || ($flow['provider'] ?? '') !== $provider || ($flow['started'] ?? 0) < time() - 600 || !$oauth->sameOrigin($r, $provider)) {
            return $this->failure('Deze inlogpoging is verlopen. Begin opnieuw.', 'This sign-in attempt expired. Please start again.');
        }
        app()->setLocale(in_array($flow['locale'] ?? '', ['nl', 'en'], true) ? $flow['locale'] : 'nl');
        $r->session()->put('lichtpunt_locale', app()->getLocale());
        if ($r->has('error')) return $this->failure('Inloggen is geannuleerd. Je kunt het opnieuw proberen.', 'Sign-in was cancelled. You can try again.');
        try { $identity = $oauth->identity($provider); }
        catch (\Throwable $e) { return $this->failure('De inlogcontrole is niet gelukt. Begin opnieuw.', 'Sign-in could not be verified. Please start again.'); }
        if (($identity['provider'] ?? '') !== $provider || empty($identity['subject']) || strlen($identity['subject']) > 191) return $this->failure('Dit account kon niet worden gecontroleerd.', 'This account could not be verified.');
        $account = SocialAccount::where('provider', $provider)->where('provider_user_id', $identity['subject'])->first();
        if ($account) return $this->signIn($r, $account->user);
        $r->session()->regenerate();
        $r->session()->put('social.pending', $identity + ['expires' => time() + 600, 'invite' => $flow['invite'] ?? null]);
        return redirect('/aanmelden/afronden');
    }

    public function form(Request $r)
    {
        $pending = $this->pending($r);
        return view('community.social-complete', [
            'pending' => $pending,
            'existing' => $pending['email'] !== '' && User::where('email', $pending['email'])->exists(),
            'suggestedName' => trim(preg_replace('/[^\pL\pN _.-]/u', '', mb_substr($pending['name'], 0, 40))),
        ]);
    }

    public function complete(Request $r)
    {
        $p = $this->pending($r);
        $r->merge(['email' => $p['email'] !== '' ? $p['email'] : strtolower(trim((string) $r->input('email')))]);
        $v = $r->validate([
            'name' => ['required', 'string', 'min:2', 'max:40', 'regex:/^[\pL\pN _.-]+$/u'],
            'email' => 'required|email|max:254', 'intentions' => 'accepted',
            'existing_password' => 'nullable|string|max:128', 'website' => 'prohibited',
        ]);
        $created = false;
        try {
            $u = DB::transaction(function () use ($p, $v, &$created) {
                $account = SocialAccount::where('provider', $p['provider'])->where('provider_user_id', $p['subject'])->lockForUpdate()->first();
                if ($account) return $account->user;
                $u = User::where('email', $v['email'])->lockForUpdate()->first();
                if ($u) {
                    // Matching email alone NEVER links accounts. Require the existing password.
                    if ($u->is_suspended || empty($v['existing_password']) || !Hash::check($v['existing_password'], $u->password)) {
                        throw ValidationException::withMessages(['existing_password' => $this->text('Dit adres heeft al een account. Bevestig je bestaande wachtwoord om de koppeling te maken, of gebruik wachtwoord herstellen.', 'This address already has an account. Confirm your existing password to connect it, or reset your password.')]);
                    }
                    if (SocialAccount::where('user_id', $u->id)->where('provider', $p['provider'])->exists()) {
                        throw ValidationException::withMessages(['existing_password' => $this->text('Er is al een ander account van deze aanbieder gekoppeld.', 'Another account from this provider is already connected.')]);
                    }
                } else {
                    $u = User::create(['name' => trim($v['name']), 'email' => $v['email'], 'password' => Str::random(64)]);
                    $u->forceFill([
                        'email_verified_at' => $p['email_verified'] ? now() : null,
                        'invite_code' => bin2hex(random_bytes(16)),
                        'referred_by' => empty($p['invite']) ? null : User::where('invite_code', $p['invite'])->where('is_suspended', false)->value('id'),
                    ])->save();
                    $created = true;
                }
                $u->forceFill(['community_accepted_at' => now(), 'community_version' => '2026-10-10'])->save();
                SocialAccount::create(['user_id' => $u->id, 'provider' => $p['provider'], 'provider_user_id' => $p['subject']]);
                return $u;
            });
        } catch (\Illuminate\Database\QueryException $e) {
            return back()->withErrors(['account' => $this->text('Afronden lukte niet. Probeer opnieuw; mogelijk is dit account ondertussen al aangemaakt.', 'Could not finish. Please try again; this account may have been created in the meantime.')])->onlyInput('name');
        }
        $r->session()->forget('social.pending');
        if ($created && !$u->hasVerifiedEmail()) {
            try { event(new Registered($u)); }
            catch (\Throwable $e) { $r->session()->flash('status', $this->text('Je account is aangemaakt. Verstuur de bevestigingsmail opnieuw vanuit je profiel.', 'Your account was created. Resend the confirmation email from your profile.')); }
        }
        return $this->signIn($r, $u);
    }

    private function pending(Request $r): array
    {
        $p = $r->session()->get('social.pending');
        if (!is_array($p) || ($p['expires'] ?? 0) < time()) {
            $r->session()->forget('social.pending');
            throw ValidationException::withMessages(['account' => $this->text('De aanmelding is verlopen. Begin opnieuw via Inloggen.', 'Registration expired. Please start again from Login.')])->redirectTo('/inloggen');
        }
        return $p;
    }

    private function signIn(Request $r, ?User $u)
    {
        if (!$u || $u->is_suspended) return $this->failure('Dit account is gepauzeerd.', 'This account is paused.');
        Auth::login($u);
        $r->session()->regenerate();
        return redirect('/profiel');
    }

    private function failure(string $nl, string $en)
    {
        return redirect('/inloggen?lang='.app()->getLocale())->withErrors(['social' => $this->text($nl, $en)]);
    }
    private function text(string $nl, string $en): string { return app()->getLocale() === 'en' ? $en : $nl; }
}
