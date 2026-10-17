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
  "resources/views/lichtpunt.blade.php": "30d1ce1e5ad716e5fd20733ebc9074807396d07202bfb84b9afa5950a6b07676",
  "resources/views/homepage/welcome-style.blade.php": "91cfaffd47dfbe486eaa8c49cb4ae6ae8ab2e2114724eae89d5525626c9f4752",
  "public/css/home-daily.css": "2488acae189c65914b419f0f8730df222c9c7ed843ee948641b092a081158cac",
  "public/images/lichtpunt-paper-welcome.png": "2e2847c1cd65e561e739a6019cc659b0ac8176b89ef7ad109cc93ae6662c8395",
  "public/images/lichtpunt-paper-welcome.webp": "f198c58eefdb00edec2516dde52bda36635f8bcad446800ca01c4eea33c7cf92",
  "public/images/hero-paper-strip.svg": "4e10a2361c5a10e8354fd1acd07f51706bfff7d0476a54b00ffc2bde62d27fd6",
  "public/images/button-paper-grain.svg": "cae4007b0539a29e46bb9ba54492373ebac9c77b8d23d612aaaa06c2f967e87c"
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
Header gecontroleerd: papier, lichtje en gescheurde papieren knoppen.
Project: ".$root."
Vernieuw de website met Ctrl+F5.
";
} catch (\Throwable $e) {
    fwrite(STDERR, "Bestanden kloppen, maar de weergavecache kon niet worden gewist: ".$e->getMessage()."
Voer zelf php artisan view:clear uit.
");
    exit(1);
}
