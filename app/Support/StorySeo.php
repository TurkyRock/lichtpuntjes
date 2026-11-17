<?php
namespace App\Support;

use App\Models\Article;
use Illuminate\Support\Str;

class StorySeo
{
 public const CATEGORIES=['all'=>['Alle lichtpuntjes','All bright spots'],'community'=>['Zelfgeschreven','Community stories'],'media'=>['Media','Media'],'sport'=>['Sport','Sport'],'energy'=>['Duurzaamheid','Sustainability'],'nature'=>['Natuur','Nature'],'science'=>['Wetenschap','Science'],'people'=>['Mens & maatschappij','People & community']];
 public const DESCRIPTIONS=[
  'all'=>['Lees positief nieuws en goed nieuws uit Nederland en de rest van de wereld. Ontdek hoopvolle ontwikkelingen, mooie initiatieven en persoonlijke lichtpuntjes.','Your daily dose of positivity. Read good news from the Netherlands and around the world, hopeful developments and uplifting community stories.'],
  'community'=>['Lees persoonlijke lichtpuntjes: mooie ontmoetingen, kleine gelukjes en vriendelijke gebaren. Verhalen die mensen zelf met Jouw Lichtpuntjes delen.','Read uplifting community stories about kind gestures, meaningful encounters and everyday joys, shared by the people who experienced them.'],
  'media'=>['Ontdek positief nieuws over cultuur en media. Lees over muziek, films, boeken en creatieve initiatieven die mensen samenbrengen.','Discover positive news about culture and media: music, films, books and creative initiatives that bring people together.'],
  'sport'=>['Lees positief sportnieuws over sportplezier, bijzondere prestaties en initiatieven die mensen door beweging en samenwerking verbinden.','Read positive sports news about achievements, the joy of movement and initiatives that connect people through sport and teamwork.'],
  'energy'=>['Ontdek goed nieuws over duurzaamheid: schone energie, hergebruik en initiatieven voor een groenere toekomst. Hoopvolle ontwikkelingen helder samengevat.','Discover good news about sustainability, clean energy, reuse and initiatives for a greener future, with clear summaries and links to the source.'],
  'nature'=>['Lees positief nieuws over natuur en dieren. Ontdek natuurherstel, bescherming van diersoorten en kleine en grote successen voor onze leefomgeving.','Read good news about nature and animals: habitat restoration, wildlife conservation and successes for the environment we share.'],
  'science'=>['Ontdek positief nieuws over wetenschap en technologie. Lees begrijpelijke samenvattingen over onderzoek, uitvindingen en hoopvolle ontdekkingen.','Discover positive science and technology news, with accessible summaries about research, inventions and hopeful discoveries.'],
  'people'=>['Lees goed nieuws over mensen en maatschappij: hulp aan elkaar, buurtinitiatieven en vriendelijke gebaren die het dagelijks leven mooier maken.','Read good news about people and communities: neighbours helping one another, local initiatives and kind gestures that brighten everyday life.'],
 ];
 public static function absolute(string $path):string{return rtrim((string)config('app.url'),'/').$path;}
 public static function home(string $lang='nl',string $category='all',int $page=1):string {
  $path=$lang==='en'?'/en':'/';
  if($category!=='all')$path=$lang==='en'?'/en/topics/'.$category:'/nl/onderwerp/'.$category;
  return $path.($page>1?'?page='.$page:'');
 }
 public static function path(Article $article,string $lang='nl'):string {
  $slug=Str::slug((string)($article->{$lang}['title']??''));
  return ($lang==='en'?'/en/bright-spots/':'/nl/lichtpuntjes/').$article->id.'/'.($slug?:'lichtpuntje');
 }
 public static function readable(Article $article):bool {
  foreach(['nl','en'] as $lang){$text=$article->{$lang};if(!is_array($text)||!is_string($text['title']??null)||trim($text['title'])===''||!is_array($text['body']??null)||!count($text['body'])||count(array_filter($text['body'],fn($p)=>is_string($p)&&trim($p)!==''))!==count($text['body']))return false;}
  return true;
 }
 public static function urls(Article $article):array{return ['nl'=>self::path($article,'nl'),'en'=>self::path($article,'en')];}
 public static function safeUrl(?string $url):?string {
  if(!$url)return null;
  if(str_starts_with($url,'/')&&!str_starts_with($url,'//'))return self::absolute($url);
  return filter_var($url,FILTER_VALIDATE_URL)&&in_array(strtolower((string)parse_url($url,PHP_URL_SCHEME)),['http','https'],true)?$url:null;
 }
 public static function description(string $lang,string $category='all'):string{return self::DESCRIPTIONS[$category][$lang==='en'?1:0]??self::DESCRIPTIONS['all'][$lang==='en'?1:0];}
 public static function heading(string $lang,string $category='all'):string {
  if($category==='all')return $lang==='en'?'Positive news and uplifting stories':'Positief nieuws en mooie verhalen';
  return (self::CATEGORIES[$category][$lang==='en'?1:0]??self::CATEGORIES['all'][$lang==='en'?1:0]).' — '.($lang==='en'?'positive news':'positief nieuws');
 }
 private static function baseGraph():array {
  return [
   ['@type'=>'Organization','@id'=>self::absolute('/#organization'),'name'=>'Jouw Lichtpuntjes','legalName'=>'Two Steps Ahead','url'=>self::absolute('/'),'logo'=>self::absolute('/images/site-paper-sun.svg')],
   ['@type'=>'WebSite','@id'=>self::absolute('/#website'),'name'=>'Jouw Lichtpuntjes','url'=>self::absolute('/'),'inLanguage'=>['nl','en'],'publisher'=>['@id'=>self::absolute('/#organization')]],
  ];
 }
 private static function breadcrumbs(array $items):array {
  return ['@type'=>'BreadcrumbList','itemListElement'=>array_map(fn($item,$index)=>['@type'=>'ListItem','position'=>$index+1,'name'=>$item[0],'item'=>self::absolute($item[1])],$items,array_keys($items))];
 }
 public static function meta(string $lang,string $category='all',int $page=1,?Article $article=null):array {
  $en=$lang==='en';$category=isset(self::CATEGORIES[$category])?$category:'all';$label=self::CATEGORIES[$category][$en?1:0];
  $title=$article?trim(strip_tags((string)$article->{$lang}['title'])):self::heading($lang,$category);
  $description=$article?trim(strip_tags((string)($article->{$lang}['teaser']??''))):self::description($lang,$category);
  if($article&&$description==='')$description=implode(' ',array_slice($article->{$lang}['body']??[],0,2));
  $description=Str::limit(preg_replace('/\s+/u',' ',strip_tags($description)),160,'');
  $alternates=$article?self::urls($article):['nl'=>self::home('nl',$category,$page),'en'=>self::home('en',$category,$page)];
  $canonical=self::absolute($alternates[$lang]);
  $image=self::safeUrl($article?($article->photo['url']??null):'/images/lichtpunt-hero-papier-v2.webp');
  $graph=self::baseGraph();$crumbs=[[$en?'Home':'Startpagina',self::home($lang)]];
  if($category!=='all')$crumbs[]=[$label,self::home($lang,$category)];
  if($article)$crumbs[]=[$title,$alternates[$lang]];
  if(count($crumbs)>1)$graph[]=self::breadcrumbs($crumbs);
  if($article){
   $node=['@type'=>$article->origin==='community'?'BlogPosting':'Article','@id'=>$canonical.'#article','url'=>$canonical,'headline'=>$title,'description'=>$description,'inLanguage'=>$lang,'mainEntityOfPage'=>$canonical,'publisher'=>['@id'=>self::absolute('/#organization')],'author'=>$article->origin==='community'?['@type'=>'Person','name'=>$article->source_name?:($en?'Community member':'Communitylid')]:['@type'=>'Organization','name'=>'Jouw Lichtpuntjes','url'=>self::absolute($en?'/en/how-we-work':'/werkwijze')],'articleSection'=>$label];
   if($image)$node['image']=[$image];
   if($article->published_at){$node['datePublished']=$article->published_at->toIso8601String();$node['dateModified']=($article->content_updated_at??$article->published_at)->toIso8601String();}
   if($article->origin!=='community'&&self::safeUrl($article->source_url))$node['citation']=self::safeUrl($article->source_url);
   $graph[]=$node;
  }else $graph[]=['@type'=>'CollectionPage','@id'=>$canonical.'#page','url'=>$canonical,'name'=>$title,'description'=>$description,'inLanguage'=>$lang,'isPartOf'=>['@id'=>self::absolute('/#website')]];
  return ['title'=>$title.($page>1?' · '.($en?'Page':'Pagina').' '.$page:'').' | Jouw Lichtpuntjes','heading'=>$title,'description'=>$description,'canonical'=>$canonical,'alternates'=>array_map(fn($p)=>self::absolute($p),$alternates),'languagePaths'=>$alternates,'image'=>$image,'schema'=>['@context'=>'https://schema.org','@graph'=>$graph],'type'=>$article?'article':'website'];
 }
 public static function document(string $path,string $lang,int $page=1):?array {
  $documents=[
   '/contact'=>[['Contact en bedrijfsgegevens','Contact and business details'],['Neem contact op met Jouw Lichtpuntjes. Bekijk de gegevens van Two Steps Ahead en vind het e-mailadres voor vragen, meldingen en privacyverzoeken.','Contact Jouw Lichtpuntjes. Find Two Steps Ahead’s business details and the email address for questions, reports and privacy requests.']],
   '/privacy'=>[['Privacyverklaring','Privacy policy'],['Lees hoe Jouw Lichtpuntjes omgaat met persoonsgegevens, lokale AI, accounts, donaties en jouw privacyrechten.','How Jouw Lichtpuntjes handles personal data, local AI, accounts, donations and your privacy rights.']],
   '/cookies'=>[['Cookies en je voorkeuren','Cookies and your preferences'],['Welke cookies gebruikt Jouw Lichtpuntjes? Lees over noodzakelijke cookies en Google Analytics en pas je toestemming aan.','Learn about essential cookies and Google Analytics on Jouw Lichtpuntjes and how to change your consent.']],
   '/huisregels'=>[['Huisregels voor onze community','Community rules'],['Onze afspraken voor persoonlijke verhalen en reacties: deel iets positiefs, ga vriendelijk met elkaar om en bescherm je eigen privacy en die van anderen.','Our guidelines for stories and comments: share something positive, be kind and protect your privacy and that of others.']],
   '/archief'=>[['Nieuwsarchief met eerdere lichtpuntjes','News archive: earlier bright spots'],['Lees eerder gepubliceerd positief nieuws in het archief van Jouw Lichtpuntjes. Oudere verhalen blijven beschikbaar met hun bronvermelding.','Browse previously published positive news in the Jouw Lichtpuntjes archive. Older stories remain available with their original sources.']],
   '/steun'=>[['Steun Jouw Lichtpuntjes vrijwillig','Support Jouw Lichtpuntjes'],['Help Jouw Lichtpuntjes draaiende te houden met een vrijwillige eenmalige of maandelijkse bijdrage. De website blijft gratis te gebruiken.','Help keep Jouw Lichtpuntjes running with a voluntary one-time or monthly contribution. The website remains free to use.']],
  ];
  if(!isset($documents[$path]))return null;
  $en=$lang==='en';[$titles,$descriptions]=$documents[$path];$title=$titles[$en?1:0];$description=$descriptions[$en?1:0];
  $paths=['nl'=>$path,'en'=>$path.'?lang=en'];
  if($page>1){$paths['nl'].='?page='.$page;$paths['en'].='&page='.$page;}
  $canonical=self::absolute($paths[$lang]);$graph=self::baseGraph();$graph[]=self::breadcrumbs([[$en?'Home':'Startpagina',self::home($lang)],[$title,$paths[$lang]]]);
  $graph[]=['@type'=>$path==='/archief'?'CollectionPage':'WebPage','url'=>$canonical,'name'=>$title,'description'=>$description,'inLanguage'=>$lang,'isPartOf'=>['@id'=>self::absolute('/#website')]];
  return ['title'=>$title.($page>1?' · '.($en?'Page':'Pagina').' '.$page:'').' | Jouw Lichtpuntjes','heading'=>$title,'description'=>$description,'canonical'=>$canonical,'alternates'=>array_map(fn($p)=>self::absolute($p),$paths),'languagePaths'=>$paths,'image'=>self::absolute('/images/lichtpunt-hero-papier-v2.webp'),'schema'=>['@context'=>'https://schema.org','@graph'=>$graph],'type'=>'website'];
 }
 public static function information(string $lang,string $page):array {
  $en=$lang==='en';$about=$page==='about';
  $paths=$about?['nl'=>'/over-ons','en'=>'/en/about']:['nl'=>'/werkwijze','en'=>'/en/how-we-work'];
  $title=$about?($en?'About Jouw Lichtpuntjes':'Over Jouw Lichtpuntjes'):($en?'Our approach: sources, AI and corrections':'Onze werkwijze: bronnen, AI en correcties');
  $description=$about?($en?'Meet Jouw Lichtpuntjes, an initiative by Erik from Two Steps Ahead. A welcoming place for positive news and uplifting personal stories.':'Maak kennis met Jouw Lichtpuntjes, een initiatief van Erik van Two Steps Ahead. Een fijne plek voor positief nieuws en persoonlijke lichtpuntjes.'):($en?'How Jouw Lichtpuntjes selects news, uses AI for summaries and illustrations, links to original sources and handles reports and corrections.':'Lees hoe Jouw Lichtpuntjes nieuws selecteert, AI gebruikt voor samenvattingen en sfeerbeelden, bronnen vermeldt en meldingen en correcties behandelt.');
  $canonical=self::absolute($paths[$lang]);$graph=self::baseGraph();
  $graph[]=self::breadcrumbs([[$en?'Home':'Startpagina',self::home($lang)],[$title,$paths[$lang]]]);
  $graph[]=['@type'=>$about?'AboutPage':'WebPage','url'=>$canonical,'name'=>$title,'description'=>$description,'inLanguage'=>$lang,'isPartOf'=>['@id'=>self::absolute('/#website')]];
  return ['title'=>$title.' | Jouw Lichtpuntjes','heading'=>$title,'description'=>$description,'canonical'=>$canonical,'alternates'=>array_map(fn($p)=>self::absolute($p),$paths),'languagePaths'=>$paths,'image'=>self::absolute('/images/lichtpunt-hero-papier-v2.webp'),'schema'=>['@context'=>'https://schema.org','@graph'=>$graph],'type'=>'website'];
 }
}
