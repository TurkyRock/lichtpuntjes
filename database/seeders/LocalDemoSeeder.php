<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class LocalDemoSeeder extends Seeder
{
    public function run(): void
    {
        $databaseFile = realpath(DB::connection()->getDatabaseName());
        $expectedFile = realpath(database_path('database.sqlite'));

        if (!app()->environment('local')
            || DB::connection()->getDriverName() !== 'sqlite'
            || $databaseFile === false
            || $databaseFile !== $expectedFile) {
            throw new RuntimeException('Demo data may only be added to the local project SQLite database.');
        }

        $email = 'demo@lichtpunt.test';
        if (User::where('email', $email)->exists()) {
            throw new RuntimeException('The local demo account already exists; no data was changed.');
        }

        $articles = [
            [
                'slug' => 'demo-neighbourhood-garden',
                'category' => 'nature',
                'days_ago' => 1,
                'nl' => [
                    'title' => '[DEMO] Buurt maakt samen een geveltuin',
                    'teaser' => 'Buren toveren een kale straat om tot een groene ontmoetingsplek.',
                    'body' => [
                        'Dit fictieve voorbeeldverhaal laat zien hoe buren samen tegels wippen en bloemen planten.',
                        'De nieuwe geveltuin is bedoeld als oefenmateriaal voor de lokale website.',
                    ],
                ],
                'en' => [
                    'title' => '[DEMO] Neighbours create a shared street garden',
                    'teaser' => 'Residents turn a bare street into a green place to meet.',
                    'body' => [
                        'This fictional sample story describes neighbours planting flowers together.',
                        'The garden is a demo example for the local website.',
                    ],
                ],
            ],
            [
                'slug' => 'demo-school-robotics',
                'category' => 'science',
                'days_ago' => 2,
                'nl' => [
                    'title' => '[DEMO] Leerlingen bouwen samen een kleine robot',
                    'teaser' => 'Een verzonnen schoolproject brengt techniek en samenwerken bij elkaar.',
                    'body' => [
                        'In dit fictieve voorbeeld ontwerpen leerlingen een robot van hergebruikte materialen.',
                        'Het verhaal is uitsluitend bedoeld om de weergave van een artikel te testen.',
                    ],
                ],
                'en' => [
                    'title' => '[DEMO] Students build a small robot together',
                    'teaser' => 'A fictional school project combines engineering and teamwork.',
                    'body' => [
                        'In this sample, students design a robot from reused materials.',
                        'This story is fictional and exists only to test article display.',
                    ],
                ],
            ],
            [
                'slug' => 'demo-community-football',
                'category' => 'sport',
                'days_ago' => 3,
                'nl' => [
                    'title' => '[DEMO] Gemengd buurtteam organiseert een sportmiddag',
                    'teaser' => 'Een fictief team nodigt de hele wijk uit om mee te doen.',
                    'body' => [
                        'Dit verzonnen verhaal gaat over een laagdrempelige sportmiddag voor buurtbewoners.',
                        'Iedereen kan meedoen of vanaf de zijlijn aanmoedigen.',
                    ],
                ],
                'en' => [
                    'title' => '[DEMO] A neighbourhood team hosts a sports afternoon',
                    'teaser' => 'A fictional team invites everyone in the area to join in.',
                    'body' => [
                        'This made-up story is about an inclusive afternoon of community sports.',
                        'Everyone can take part or cheer from the sidelines.',
                    ],
                ],
            ],
            [
                'slug' => 'demo-library-kindness',
                'category' => 'people',
                'days_ago' => 4,
                'nl' => [
                    'title' => '[DEMO] Bibliotheek start een ruilplank voor kinderboeken',
                    'teaser' => 'Een fictieve leesactie geeft kinderboeken een tweede leven.',
                    'body' => [
                        'In dit verzonnen voorbeeld kunnen bezoekers een kinderboek ruilen voor een ander verhaal.',
                        'Zo oefenen we de nieuwskaart en de Engelstalige samenvatting.',
                    ],
                ],
                'en' => [
                    'title' => '[DEMO] Library starts a children’s book exchange shelf',
                    'teaser' => 'A fictional reading project gives children’s books a second life.',
                    'body' => [
                        'In this sample, visitors can exchange one children’s book for another.',
                        'It helps us exercise the story card and its English summary.',
                    ],
                ],
            ],
        ];

        foreach ($articles as $article) {
            if (Article::where('fingerprint', hash('sha256', $article['slug']))->exists()) {
                throw new RuntimeException('Some local demo articles already exist; no data was changed.');
            }
        }

        $password = bin2hex(random_bytes(16));

        DB::transaction(function () use ($email, $password, $articles): void {
            $user = User::create([
                'name' => 'Demo-gebruiker',
                'email' => $email,
                'password' => $password,
            ]);
            $user->forceFill([
                'email_verified_at' => now(),
                'community_accepted_at' => now(),
                'community_version' => '2026-10-10',
                'invite_code' => bin2hex(random_bytes(16)),
                'is_admin' => false,
                'is_suspended' => false,
            ])->save();

            foreach ($articles as $article) {
                Article::create([
                    'fingerprint' => hash('sha256', $article['slug']),
                    'source_title' => $article['nl']['title'],
                    'source_description' => 'Fictief voorbeeldartikel voor lokaal testen; geen echt nieuws.',
                    'source_name' => 'Lichtpunt demo (fictief)',
                    'source_url' => 'https://example.test/demo/'.$article['slug'],
                    'published_at' => now()->subDays($article['days_ago']),
                    'category' => $article['category'],
                    'status' => 'published',
                    'nl' => $article['nl'],
                    'en' => $article['en'],
                    'attempts' => 0,
                    'origin' => 'news',
                    'views_count' => 0,
                    'likes_count' => 0,
                ]);
            }
        });

        $this->command?->info('Local demo data created. The articles are fictional and marked [DEMO].');
        $this->command?->line('Login email: '.$email);
        $this->command?->line('Login password (shown once): '.$password);
    }
}
