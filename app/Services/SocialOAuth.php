<?php
namespace App\Services;

use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use SocialiteProviders\Manager\Config;

class SocialOAuth
{
    public const PROVIDERS = ['google', 'apple', 'microsoft'];

    public function available(string $provider): bool
    {
        if (!in_array($provider, self::PROVIDERS, true)) return false;
        $c = config("social-login.$provider", []);
        $class = $this->providerClass($provider);
        if (!class_exists(Socialite::class) || !class_exists($class) || empty($c['client_id'])) return false;
        $url = parse_url($c['redirect'] ?? '');
        if (!$url || empty($url['host']) || ($url['path'] ?? '') !== "/auth/$provider/callback") return false;
        if (!empty($url['query']) || !empty($url['fragment']) || !empty($url['user'])) return false;
        $https = ($url['scheme'] ?? '') === 'https';
        if (!$https && !(($url['scheme'] ?? '') === 'http' && in_array($url['host'], ['localhost', '127.0.0.1', '[::1]'], true))) return false;
        if ($provider === 'apple') return $https && !empty($c['team_id']) && !empty($c['key_id']) && is_readable($c['private_key'] ?? '');
        return !empty($c['client_secret']);
    }

    public function enabled(): array
    {
        return array_values(array_filter(self::PROVIDERS, fn($p) => $this->available($p)));
    }

    public function sameOrigin(Request $request, string $provider): bool
    {
        $url = parse_url(config("social-login.$provider.redirect", ''));
        return $url && strtolower($request->getHost()) === strtolower($url['host'] ?? '')
            && $request->getScheme() === ($url['scheme'] ?? '')
            && $request->getPort() === ($url['port'] ?? (($url['scheme'] ?? '') === 'https' ? 443 : 80));
    }

    public function driver(string $provider)
    {
        abort_unless($this->available($provider), 404);
        $c = config("social-login.$provider");
        // Apple POSTs its callback cross-site: issue a Secure SameSite=None session cookie.
        // OAuth state + Apple's verified nonce protect this callback, not a Laravel form token.
        if ($provider === 'apple') {
            config(['session.secure' => true, 'session.same_site' => 'none', 'services.apple' => $c]);
        }
        $class = $provider === 'microsoft' ? \App\OAuth\MicrosoftProvider::class : $this->providerClass($provider);
        $driver = Socialite::buildProvider($class, $c);
        if ($provider !== 'google') {
            $driver->setConfig(new Config($c['client_id'], $c['client_secret'], $c['redirect'], $c));
        }
        $driver->setHttpClient(new \GuzzleHttp\Client(['connect_timeout' => 10, 'timeout' => 20]));
        if ($provider === 'google') $driver->setScopes(['openid', 'profile', 'email']);
        if ($provider === 'microsoft') $driver->setScopes(['openid', 'profile', 'email', 'User.Read']);
        // Keep Socialite's state checks enabled; never call stateless().
        return $driver;
    }

    public function redirect(string $provider) { return $this->driver($provider)->redirect(); }

    public function identity(string $provider): array
    {
        $u = $this->driver($provider)->user();
        $raw = $u->getRaw();
        $email = $provider === 'microsoft' ? ($raw['mail'] ?? $u->getEmail()) : $u->getEmail();
        $email = strtolower(trim((string) $email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) $email = '';
        $verified = $provider !== 'microsoft' && in_array($raw['email_verified'] ?? $raw['verified_email'] ?? false, [true, 1, 'true', '1'], true);
        return [
            'provider' => $provider, 'subject' => (string) $u->getId(),
            'email' => $email, 'email_verified' => $email !== '' && $verified,
            'name' => (string) $u->getName(),
        ];
        // Access/refresh/identity tokens and avatars are intentionally not persisted.
    }

    private function providerClass(string $provider): string
    {
        return match ($provider) {
            'google' => \Laravel\Socialite\Two\GoogleProvider::class,
            'apple' => \SocialiteProviders\Apple\Provider::class,
            'microsoft' => \SocialiteProviders\Microsoft\Provider::class,
        };
    }
}
