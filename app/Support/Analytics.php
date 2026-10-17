<?php

namespace App\Support;

use Illuminate\Http\Request;

class Analytics
{
    public static function id(): ?string
    {
        $id = config('analytics.measurement_id');
        return config('analytics.enabled') && is_string($id) && preg_match('/^G-[A-Z0-9]{4,20}$/D', $id) ? $id : null;
    }

    public static function eligible(Request $request): bool
    {
        // Account pages, authentication, user submissions and administration are never tagged.
        return self::id() !== null && ($request->getPathInfo() === '/' || $request->getPathInfo() === '/en' || $request->is('nl/onderwerp/*','en/topics/*'));
    }
}
