<?php
// Alleen uitvoeren vanuit de terminal, in de Laravel-projectmap.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$root = __DIR__;
if (!is_file($root.'/artisan') || !is_file($root.'/vendor/autoload.php')) {
    fwrite(STDERR, "Pak het pakket uit in de Laravel-projectmap, naast artisan.
");
    exit(1);
}
$expected = json_decode(<<<'MANIFEST'
{
  "app/Http/Controllers/CommunityAdminController.php": "fe0addc557d34f39aef3ecab14c3ce52ca9209089b68d284cb710c0cadc89550",
  "app/Http/Controllers/CommunityController.php": "5ce708dac71b1e37abe894777090e20e283dde714c1ef71e95c1c1a1b8ee503b",
  "app/Http/Controllers/MomentController.php": "fab6257c055b4d02c08f85579da54e79d4144054a5f270e5408dec6c9d890455",
  "app/Http/Controllers/ProfilePhotoController.php": "35559c95d18e60d1b66fa3dba83368d8221549c7aa01ee012bd0907b083d0afa",
  "app/Http/Controllers/PublicStoryController.php": "ff7f32f9a1586408956aaa7535ca5228774bc09a44e8c57ac1fbe9c08c1eb472",
  "app/Http/Controllers/UserAdminController.php": "89ff3e98447aa28e965fefe86a778f8b20266e942cd97e9cc250a8a6f7a5d6c4",
  "app/Http/Middleware/SecurityHeaders.php": "778a628acb15679a57002edc4db971ed33e71df5e6105d6fbb64d76d97fd8371",
  "app/Jobs/ReviewMoment.php": "c606cc4fe53b1f36bdcef729c24d6c187cb13f8ce5d60ad30680a3cb37387af1",
  "app/Jobs/ReviewProfilePhoto.php": "286350b41263f309f6fb26fe39a67159f88d9c59069b9eb53bcc048d8e50861f",
  "app/Models/PositiveMoment.php": "c12ff9dbc2add387c70116bcd99cf6730c05a7effa16b224db9e3c0f0e03eda8",
  "app/Models/ProfilePhoto.php": "c29606b7921ab91e3775f6509fdcbf7286a76388f2c3ba42d64a6cea525e2ca7",
  "app/Models/User.php": "6001fe39a26cc9d10ec3b2532bc33c3b315cc9e132a39d4a73f4aefc74c6583e",
  "app/Services/DailyIllustrationBudget.php": "0c1c390ce6c21c0ad9391c12f7fcc6f328d2da6f5a802415c6a26f3fa4f8a5b5",
  "app/Services/GeminiJson.php": "ad90d10c2a4e6de05ed42d2b1450c22fd73f3de6cdf51d8284e51c9ad3974e5c",
  "app/Services/ImageBudgetException.php": "0b164e0ac086806d421ee782d7e9da79b865cd291c50a799343275d9f80c9a9f",
  "app/Services/MomentModerator.php": "fe5a4fd7a65f75cce53e6dc0e8ccbcac5983a4f1dcf2549dc2b230eb0d7b8e07",
  "app/Services/PortraitNormalizer.php": "450d37d5819c2e7d2972234873a6a3b6c844782d59c00156d5e06f4c97674fe9",
  "app/Services/ProfilePhotoModerator.php": "29344af8f702b4ccd9d981f2dea75c2a0d456df01ce415fc585dc1eb57583243",
  "config/news_cycle.php": "67821968b36cecf6696ea4ab19040ccd775a6c5889a9da4ec8449e8da49d1de7",
  "routes/console.php": "f6cb347e88019f5f8ffcb532bfb387130b6a6c3ffa208e2fceeb3bf471476db9",
  "routes/web.php": "e942e42d8ad75e23a225cc14b72433e46f6bc9842d67c8475bbee975f676301c",
  "resources/views/community/layout.blade.php": "ac4ddd28922fc0784abfa5b0e0e4e04b946b3b2918933c63d4c4be5c03bfcb75",
  "resources/views/community/moment-form.blade.php": "77a2a9ea9e8da9338794c4fb26466f664771e83e48452b84381255bcef6d46d3",
  "resources/views/community/moments.blade.php": "41a1d6230af61093afefde10c19e0360dba1a8e5dcf9317d877b09cf88d78741",
  "resources/views/community/privacy.blade.php": "9b712e5d1920a31fa84e8fd3a12081be8fa2e5a768a7473777ee5bf0628fe920",
  "resources/views/community/profile-content.blade.php": "c5c01aa9efd4fbd59e6edea5fdcff281f0712a4c987d00c5d83eda74a326bda8",
  "resources/views/community/profile-photo.blade.php": "7a1a7a8709f3269807cca7b9354196d88029fd4d13af338c61fcf32ffaa62e30",
  "resources/views/community/public-moments.blade.php": "df4d59ffbd721eab317a7bbe578523633b2a8557ad55dc2a6fa71ee67d809b81",
  "resources/views/community/share-choice.blade.php": "1f0924c93cce67cb194415ee98970294906f058f208e4038de88676bedb3e49b",
  "resources/views/community/user-admin-detail.blade.php": "954ebcc9b316d54c89b2dd97c7cafd018e5c4df79b6c6a7219885b9a179afe07",
  "resources/views/community/users-admin.blade.php": "79bc46ee9ca2c18802ef1dc56ba1e416c7c8af528799c15d802306b11fd5eba9",
  "resources/views/homepage/highlights.blade.php": "416b0a277f2c66bacd4eb00927c89bef0ea96a418254ed28e5b70f0a5f030f6c",
  "resources/views/homepage/moments.blade.php": "db44707db80355fe21fb151e2800fe4ec9285102aea61d0cd0c69bbebf9a06f8",
  "resources/views/homepage/welcome-style.blade.php": "c2997f3537cd096346dc0cff1723acf952d82bef6836a15c5757f0f060f39870",
  "resources/views/lichtpunt.blade.php": "349de04e252bb93758a240caa3e197fbad6abd1d62442ff014e44066facbfb65",
  "resources/views/seo/cards.blade.php": "845e2b154c620d70b51ad23e0a2b6401cf86919fde7951843cc4e1a6ce8bcb0a",
  "resources/views/shared/account-sidebar.blade.php": "04dc37685455c770cad6addb92d3d3b9aab4838d9d2224e3d0a58023cba03994",
  "resources/views/shared/admin-sidebar.blade.php": "b9c80abea8bceafa076094700a39c69eecd659fcd7a8d7d459befa14fd5e927e",
  "resources/views/shared/ai-image-label.blade.php": "49699c7e031d393a346b33afe76993b35fa331646835e5cf2a41b7f940a89957",
  "resources/views/shared/header.blade.php": "2f20c36d2870203bec44e886b263afec291539c54b4c605e76cd13650be61b51",
  "resources/views/shared/moment-heart.blade.php": "d8a6c29ff5b9aca3d469c62b2321b956d298082d11ba974c0500cb8d06e633c5",
  "public/css/home-daily.css": "224bc5f3b42f7b4d303c85996538da14ed3ae6ef5bc3872c28ac647a5a436be9",
  "public/css/profile-photos.css": "615b1f63c52135c7b37482d90cd2ca3b27d6aaa24e6c9e1c79bde15126beadd2",
  "public/css/site-header.css": "72a76e35aa3c460ae4fe9aeb997582d76575fcf46f0de63a8e162518e2ec3145",
  "database/migrations/2026_10_17_000015_add_profile_photos_and_user_moderation.php": "24aa0431bd9a6f898d32f2d539ce9136733aab9bbaad9db152c472bc39986564",
  "database/migrations/2026_10_17_000016_create_positive_moments.php": "88a5b488f212653c364fa34e6a63c3ed20e4e0bdfe0a4931a35d435ac18eabb6"
}
MANIFEST, true, 512, JSON_THROW_ON_ERROR);
$ok = true;
foreach ($expected as $file => $hash) {
    if (!is_file($root.'/'.$file) || !hash_equals($hash, hash_file('sha256', $root.'/'.$file))) {
        fwrite(STDERR, "Ontbreekt of nog oud: ".$file."
");
        $ok = false;
    }
}
if (!$ok) {
    fwrite(STDERR, "Overschrijf de bestanden uit dit pakket, met behoud van de mappen, en probeer opnieuw.
");
    exit(1);
}
try {
    if (!function_exists('imagecreatefromstring')) { fwrite(STDERR, "PHP GD ontbreekt: zet GD aan in Herd/Plesk voor profielfoto-uploads. De andere functies werken wel.\n"); }
    require $root.'/vendor/autoload.php';
    $app = require $root.'/bootstrap/app.php';
    $kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
    $code = $kernel->call('view:clear');
    echo $kernel->output();
    if ($code !== 0) { exit($code); }
    echo "
Community-update met momentjes en verhalen gecontroleerd.
Project: ".$root."
Vernieuw de website met Ctrl+F5.
";
} catch (\Throwable $e) {
    fwrite(STDERR, "Bestanden kloppen, maar de weergavecache kon niet worden gewist: ".$e->getMessage()."
Voer zelf php artisan view:clear uit.
");
    exit(1);
}
