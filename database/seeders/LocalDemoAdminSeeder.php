<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class LocalDemoAdminSeeder extends Seeder
{
    public function run(): void
    {
        $databaseFile = realpath(DB::connection()->getDatabaseName());
        $expectedFile = realpath(database_path('database.sqlite'));

        if (!app()->environment('local')
            || DB::connection()->getDriverName() !== 'sqlite'
            || $databaseFile === false
            || $databaseFile !== $expectedFile) {
            throw new RuntimeException('A demo admin may only be created in the local project SQLite database.');
        }

        if (User::where('email', 'demo-admin@lichtpunt.test')->exists()) {
            throw new RuntimeException('The local demo admin already exists; no data was changed.');
        }

        $password = bin2hex(random_bytes(16));

        DB::transaction(function () use ($password): void {
            $user = User::create([
                'name' => 'Demo-beheerder',
                'email' => 'demo-admin@lichtpunt.test',
                'password' => $password,
            ]);
            $user->forceFill([
                'email_verified_at' => now(),
                'community_accepted_at' => now(),
                'community_version' => '2026-10-10',
                'invite_code' => bin2hex(random_bytes(16)),
                'is_admin' => true,
                'is_suspended' => false,
            ])->save();
        });

        $this->command?->info('Local demo admin created.');
        $this->command?->line('Login email: demo-admin@lichtpunt.test');
        $this->command?->line('Login password (shown once): '.$password);
    }
}
