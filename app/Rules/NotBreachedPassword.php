<?php
namespace App\Rules;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\{Cache, Http};
class NotBreachedPassword implements ValidationRule {
    public function validate(string $attribute, mixed $value, Closure $fail): void {
        if (!config('account-security.check_breached_passwords', true) || mb_strlen((string)$value) < 15) return;
        $hash = strtoupper(sha1((string)$value));
        $prefix = substr($hash, 0, 5);
        try {
            $body = Cache::remember('password-range:'.$prefix, 21600, function() use ($prefix) {
                $response = Http::withOptions(['allow_redirects'=>false])->withHeaders(['Add-Padding'=>'true'])
                    ->connectTimeout(3)->timeout(5)->get('https://api.pwnedpasswords.com/range/'.$prefix);
                if (!$response->successful()) throw new \RuntimeException('Password check unavailable');
                $body = $response->body();
                if (!preg_match('/^[A-F0-9]{35}:\d+/m', $body)) throw new \RuntimeException('Invalid range response');
                return $body;
            });
            foreach (preg_split('/\R/', $body) as $line) {
                if (!preg_match('/^([A-F0-9]{35}):(\d+)$/', trim($line), $match)) continue;
                if (hash_equals(substr($hash, 5), $match[1]) && (int)$match[2] > 0) {
                    $fail(app()->getLocale()==='en' ? 'This password has appeared in a data breach. Choose another password phrase.' : 'Dit wachtwoord is eerder uitgelekt. Kies een andere wachtwoordzin.');
                    return;
                }
            }
        } catch (\Throwable $e) {
            $fail(app()->getLocale()==='en' ? 'The password safety check is temporarily unavailable. Please try again shortly.' : 'De controle op gelekte wachtwoorden is tijdelijk niet beschikbaar. Probeer het zo opnieuw.');
        }
    }
}
