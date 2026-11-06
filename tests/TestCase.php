<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // External breach lookups are mocked explicitly by the security tests.
        config(['account-security.check_breached_passwords' => false]);
    }

    public function actingAs(\Illuminate\Contracts\Auth\Authenticatable $user, $guard = null)
    {
        parent::actingAs($user, $guard);
        // actingAs represents a freshly authenticated session. Security tests
        // override these values to exercise expired and revoked sessions.
        $this->withSession(['auth.password_confirmed_at'=>now()->timestamp, 'account_security_version'=>(int)($user->security_version??0)]);
        return $this;
    }
}
