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
  "resources/views/lichtpunt.blade.php": "6104a5136e8c5425a83244edf01ef301720c6a15df79885e8ba254176151904a",
  "resources/views/homepage/welcome-style.blade.php": "c79bc62e4128b6d7d2f86d74a516db039e0fa0e44ee88f8df332963aa63dcc91",
  "public/css/site-header.css": "5dc581ca5d07d0e8a6bbd4e352cd1b9f7222983a7c11630bd13a180272512349",
  "public/css/home-daily.css": "0fa78299508682ac180906d8da106d65f5c672b7b98ed4dd70c00d6a1bc21105",
  "public/images/lichtpunt-paper-welcome.png": "2e2847c1cd65e561e739a6019cc659b0ac8176b89ef7ad109cc93ae6662c8395",
  "public/images/lichtpunt-paper-welcome.webp": "f198c58eefdb00edec2516dde52bda36635f8bcad446800ca01c4eea33c7cf92",
  "public/images/hero-paper-strip.svg": "4e10a2361c5a10e8354fd1acd07f51706bfff7d0476a54b00ffc2bde62d27fd6",
  "public/images/button-paper-cutout-v2.png": "ee1af0907cf0ce76339b7565d3a285a3c45a7021b7dc4deb111e084b026b5275"
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
    require $root.'/vendor/autoload.php';
    $app = require $root.'/bootstrap/app.php';
    $kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
    $code = $kernel->call('view:clear');
    echo $kernel->output();
    if ($code !== 0) { exit($code); }
    echo "
Homepage gecontroleerd: compacte indeling en groene deelknop.
Project: ".$root."
Vernieuw de website met Ctrl+F5.
";
} catch (\Throwable $e) {
    fwrite(STDERR, "Bestanden kloppen, maar de weergavecache kon niet worden gewist: ".$e->getMessage()."
Voer zelf php artisan view:clear uit.
");
    exit(1);
}
