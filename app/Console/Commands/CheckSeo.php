<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\{File,Schema};

class CheckSeo extends Command
{
 protected $signature='seo:check';
 protected $description='Controleer lokale SEO-instellingen en vereiste bestanden, zonder externe accounts of privégegevens.';
 public function handle():int {
  $url=(string)config('app.url');$host=parse_url($url,PHP_URL_HOST);
  $checks=[
   'Publiek HTTPS-adres'=>filter_var($url,FILTER_VALIDATE_URL)&&parse_url($url,PHP_URL_SCHEME)==='https'&&!in_array($host,['localhost','127.0.0.1']),
   'Publieke site niet als gesloten beta ingesteld'=>!config('beta.closed_site'),
   'Productiefouten afgeschermd'=>!config('app.debug'),
   'Dynamische robots.txt niet overschreven'=>!File::exists(public_path('robots.txt')),
   'Inhoudelijke wijzigingsdatum gemigreerd'=>Schema::hasColumn('articles','content_updated_at'),
  ];
  foreach(['css/home-base.css','css/seo-updates.css','images/mobile-reference-paper.webp','images/mobile-footer-cutout.webp','images/button-paper-cutout-v2.webp','images/lichtpunt-hero-papier-v2.webp'] as $file)$checks[$file]=File::exists(public_path($file));
  foreach($checks as $name=>$ok)$this->line(($ok?'OK   ':'LET OP   ').$name);
  $this->line('Websiteadres: '.$url);
  $this->line('Controleer na upload ook /robots.txt, /sitemap.xml, een categorie en een artikel.');
  $this->line('Indexering, zoekposities en echte bezoekersprestaties controleer je apart in Search Console.');
  return in_array(false,$checks,true)?self::FAILURE:self::SUCCESS;
 }
}
