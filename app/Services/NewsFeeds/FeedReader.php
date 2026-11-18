<?php
namespace App\Services\NewsFeeds;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class FeedReader
{
    public function allowed(string $url,array $source,bool $feed=false):bool {
        $parts=parse_url($url);
        if(!$parts || ($parts['scheme']??'')!=='https' || isset($parts['user']) || isset($parts['pass']) || (isset($parts['port'])&&$parts['port']!==443))return false;
        $hosts=$feed?array_merge($source['hosts'],$source['feed_hosts']??[]):$source['hosts'];
        if(!in_array(strtolower($parts['host']??''),$hosts,true))return false;
        if($feed)return true;
        foreach($source['paths'] as $path)if(str_starts_with($parts['path']??'/',$path))return true;
        return false;
    }
    public function download(string $url,array $source):string {
        if(!$this->allowed($url,$source,true))throw new RuntimeException('Bronadres niet toegelaten.');
        // No cookies, account locations, secrets or private preferences go to publishers.
        $r=Http::withHeaders(['User-Agent'=>'JouwLichtpuntjes/1.0 (+https://jouwlichtpuntjes.nl/werkwijze)','Accept'=>'application/rss+xml, application/atom+xml, application/xml, text/xml'])
            ->withOptions(['allow_redirects'=>false,'stream'=>true])->connectTimeout(5)->timeout(12)->get($url);
        if(!$r->successful())throw new RuntimeException('Bron HTTP '.$r->status().'.');
        $stream=$r->toPsrResponse()->getBody();$body='';
        while(!$stream->eof()){
            $chunk=$stream->read(min(65536,2097153-strlen($body)));
            if($chunk==='')throw new RuntimeException('Geen volledige RSS-feed ontvangen.');
            $body.=$chunk;
            if(strlen($body)>2097152)throw new RuntimeException('Feed te groot; niet verwerkt.');
        }
        return $body;
    }
    public function parse(string $xml,array $source):array {
        if(preg_match('/<html\b/i',substr($xml,0,2000)))throw new RuntimeException('Geen geldige RSS-feed ontvangen; bron gaf een webpagina.');
        if(preg_match('/<!DOCTYPE|<!ENTITY/i',$xml))throw new RuntimeException('Onveilige XML; niet verwerkt.');
        $before=libxml_use_internal_errors(true);
        try{$root=simplexml_load_string($xml,\SimpleXMLElement::class,LIBXML_NONET|LIBXML_NOCDATA);}
        finally{libxml_clear_errors();libxml_use_internal_errors($before);}
        if(!$root || $root->getName()!=='rss')throw new RuntimeException('Geen geldige RSS-feed ontvangen.');
        $entries=[];
        foreach($root->channel->item as $item){
            if(count($entries)>=30)break;
            $url=trim((string)$item->link);
            if(!$this->allowed($url,$source))continue;
            $content=(string)$item->children('http://purl.org/rss/1.0/modules/content/')->encoded;
            $body=$this->text($content ?: (string)$item->description);
            $title=$this->text((string)$item->title);
            $rights=(string)$item->children('http://purl.org/dc/elements/1.1/')->rights;
            // Do not import exceptions, sponsored text, non-derivative licences or externally copyrighted stories.
            if(preg_match('/all rights reserved|rights reserved|no derivatives|no.derivatives|CC.BY.(?:NC|ND)|sponsored|gesponsord|advertorial|©|copyright (?!NASA)/iu',$rights.' '.$body))continue;
            $author=mb_substr(trim((string)$item->children('http://purl.org/dc/elements/1.1/')->creator),0,250);
            if(($source['author_required']??false)&&$author==='')continue;
            if(mb_strlen($title)<5||mb_strlen($body)<120)continue;
            try{$date=\Carbon\CarbonImmutable::parse((string)$item->pubDate);if(!(string)$item->pubDate)continue;}catch(\Throwable $e){continue;}
            if($date->isFuture()||$date->lt(now()->subDays(30)))continue;
            $entries[]=['url'=>$url,'title'=>mb_substr($title,0,1000),'body'=>mb_substr($body,0,12000),'author'=>$author?:null,'published_at'=>$date];
        }
        return $entries;
    }
    private function text(string $html):string {
        $html=preg_replace('~<(script|style)\b[^>]*>.*?</\1>~is','',$html);
        $html=preg_replace('~</(?:p|div|li|h[1-6])>|<br\s*/?>~i',"\n",$html);
        return trim(preg_replace('/[\t ]+/u',' ',html_entity_decode(strip_tags($html),ENT_QUOTES|ENT_HTML5,'UTF-8')));
    }
}
