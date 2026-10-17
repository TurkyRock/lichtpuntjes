<?php
namespace App\Support;
use App\Models\Article;
use Illuminate\Support\Str;
class StorySeo {
 public const CATEGORIES=['all'=>['Alle lichtpuntjes','All bright spots'],'community'=>['Zelfgeschreven','Community stories'],'media'=>['Media','Media'],'sport'=>['Sport','Sport'],'energy'=>['Duurzaamheid','Sustainability'],'nature'=>['Natuur','Nature'],'science'=>['Wetenschap','Science'],'people'=>['Mens & maatschappij','People & community']];
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
 public static function readable(Article $article):bool {foreach(['nl','en'] as $lang){$text=$article->{$lang};if(!is_array($text)||!is_string($text['title']??null)||$text['title']===''||!is_array($text['body']??null)||count(array_filter($text['body'],'is_string'))!==count($text['body']))return false;}return true;}
 public static function urls(Article $article):array{return ['nl'=>self::path($article,'nl'),'en'=>self::path($article,'en')];}
 public static function safeUrl(?string $url):?string {
  if(!$url)return null;
  if(str_starts_with($url,'/')&&!str_starts_with($url,'//'))return self::absolute($url);
  return filter_var($url,FILTER_VALIDATE_URL)&&in_array(strtolower((string)parse_url($url,PHP_URL_SCHEME)),['http','https'],true)?$url:null;
 }
 public static function meta(string $lang,string $category='all',int $page=1,?Article $article=null):array {
  $en=$lang==='en';$label=self::CATEGORIES[$category][$en?1:0]??self::CATEGORIES['all'][$en?1:0];
  $title=$article?(string)$article->{$lang}['title']:($category==='all'?($en?'Positive news and uplifting stories':'Positief nieuws en mooie verhalen'):$label.' — '.($en?'positive news':'positief nieuws'));
  $description=$article?(string)($article->{$lang}['teaser']??''):($en?'Your daily dose of positivity. Discover good news from around the world and uplifting stories shared by our community.':'Jouw dagelijkse portie positiviteit. Ontdek positief nieuws uit de hele wereld en mooie lichtpuntjes die mensen zelf delen.');
  $alternates=$article?self::urls($article):['nl'=>self::home('nl',$category,$page),'en'=>self::home('en',$category,$page)];
  $image=self::safeUrl($article?($article->photo['url']??null):'/images/lichtpunt-hero-papier-v2.png');
  $schema=['@context'=>'https://schema.org','@type'=>'WebSite','name'=>'Jouw Lichtpuntje','url'=>self::absolute('/'),'inLanguage'=>['nl','en']];
  if($article){
   $schema=['@context'=>'https://schema.org','@type'=>$article->origin==='community'?'BlogPosting':'Article','headline'=>$title,'description'=>Str::limit(strip_tags($description),160,''),'inLanguage'=>$lang,'mainEntityOfPage'=>self::absolute($alternates[$lang]),'publisher'=>['@type'=>'Organization','name'=>'Jouw Lichtpuntje','url'=>self::absolute('/')],'author'=>['@type'=>$article->origin==='community'?'Person':'Organization','name'=>$article->origin==='community'?$article->source_name:'Jouw Lichtpuntje']];
   if($image)$schema['image']=[$image];
   if($article->published_at)$schema['datePublished']=$article->published_at->toIso8601String();
   if($article->origin!=='community'&&self::safeUrl($article->source_url))$schema['citation']=self::safeUrl($article->source_url);
  }
  return ['title'=>$title.($page>1?' · '.($en?'Page':'Pagina').' '.$page:'').' | Jouw Lichtpuntje','description'=>Str::limit(strip_tags($description),160,''),'canonical'=>self::absolute($alternates[$lang]),'alternates'=>array_map(fn($p)=>self::absolute($p),$alternates),'languagePaths'=>$alternates,'image'=>$image,'schema'=>$schema,'type'=>$article?'article':'website'];
 }
}
