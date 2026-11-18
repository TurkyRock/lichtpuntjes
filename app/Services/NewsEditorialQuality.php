<?php
namespace App\Services;

use App\Models\Article;

/** Conservative publication checks, not an independent factual verification. */
final class NewsEditorialQuality
{
    public const POLICY = <<<'POLICY'
EDITORIAL STANDARD v2026-11-18 (new imported news only):
Select a concrete benefit, genuine achievement, helpful initiative or joyful milestone that is explicitly supported by the supplied source. A cheerful adjective does not make a story good news.
Reject gambling/betting/casino product promotions, market-size forecasts, stock tips, supplier sales pitches, routine product/TV guest announcements and celebrity photo updates with no meaningful positive development. Do not reject a genuine public benefit merely because a business is involved; distinguish an actual community/environment/health benefit from marketing claims.
Write idiomatic, professionally edited Dutch (B1/B2, adult readers) and natural English. Dutch titles use sentence case, never English Title Case. Translate ordinary English phrases, but preserve personal names, official programme/product names and titles of works. Avoid literal calques and invented words. Examples of errors to avoid: 'binnenkantehuiskunst', 'voertuigengeleidelijke', 'voetdenkbaar', 'milieuweters', 'EU-gebackte', 'een vaste tarief vooraf gekeken'. In a solar subsidy application, 'applications' means 'aanvragen', not 'toepassingen'. A sportsbook is a betting service, not a Dutch 'sportboek'. When a term is uncertain, use an accurate plain phrase or choose insufficient; never guess.
Every sentence must be traceable to the supplied title/description. Add no background, motivations, reactions, quotes, benefits, organisational names or expansions of acronyms that the source does not establish. Never invent a quote from a fragment. Do not complete a truncated source quote from imagination; omit it or choose insufficient.
Preserve who did what, tense, uncertainty, units, currency and scale. A plan to map genomes is not a completed or publicly available genome bank; research is not an available treatment or guaranteed prevention. Revenue is not market value; a report listing is not an award; an animal species is not a protected area. Keep 'could', 'aims to', estimates, planned dates and attribution. Do not convert currencies, lakh/crore, miles or units unless the supplied source explicitly gives the conversion. Omit an unclear amount rather than relabel it.
Use one to three short paragraphs, usually 60-140 words, and fewer if the evidence is thin. Never require three paragraphs. Every paragraph adds a distinct sourced fact. Do not add generic conclusions such as 'Dit benadrukt het belang van samenwerking', 'een inspirerend voorbeeld', 'een stap naar een betere toekomst', 'This highlights the importance' or 'a testament to' just to sound positive. The facts themselves carry the warmth. Do not copy the headline into every paragraph.
Category is the main subject, not the publisher or the person's name: film/music/TV/celebrities -> media; matches/athletes/sporting achievement -> sport; emissions/renewables/circular economy -> energy; wildlife/habitats -> nature; research/technology -> science; volunteering/community/social help -> people. A taxi booking app is not sustainability merely because the feed supplied it there. Gambling is not a sporting achievement.
Before returning JSON, silently proofread both complete versions: natural grammar, no nonsense compounds or untranslated fragments, complete sentences/quotes, no repeated filler, exactly the same facts and numbers, correct category. Repair any flaw before returning. If reliable text cannot be produced from this source, return insufficient and empty translations. Never invent material to meet a word count. Return only the requested schema.
POLICY;

    public function sourceRejection(Article $article): ?string
    {
        $title=mb_strtolower($article->source_title??'');
        $gambling=preg_match('/\b(sportsbook|sportsbetting|betting app|bet & icasino|bet25|gokapp|wedkantoor)\b/u',$title);
        $promotion=preg_match('/\b(launch\w*|introduc\w*|deploy\w*|reopen\w*|heropen\w*|lance\w*|nul.marge|zero.margin)\b/u',$title);
        if($gambling&&$promotion)return 'Gokproductpromotie is geen positief sportnieuws.';
        if(preg_match('/\b(cagr|compound annual growth rate)\b/iu',$article->source_description??'')&&preg_match('/\b(market|markt)\b/u',$title))return 'Marktprognose of commerciële marktanalyse zonder concreet lichtpuntje.';
        return null;
    }

    public function issues(array $result): array
    {
        $issues=[];
        foreach(['nl','en'] as $language){
            $text=$result[$language]??[];
            $body=$text['body']??[];
            $all=implode(' ',array_merge([$text['title']??'',$text['teaser']??''],$body));
            if(preg_match('/<\/?[a-z][^>]*>|```/iu',$all))$issues[]="$language: opmaakcode in de tekst";
            if($language==='nl'){
                $words=preg_split('/[^\p{L}\p{N}]+/u',mb_strtolower(implode(' ',$body)),-1,PREG_SPLIT_NO_EMPTY);
                $english=count(array_filter($words,fn($w)=>in_array($w,['the','and','with','this','that','has','have','will','from','was','were'],true)));
                $dutch=count(array_filter($words,fn($w)=>in_array($w,['de','het','een','en','van','voor','met','dit','dat','heeft','wordt','zijn'],true)));
                if(count($words)>=40&&$english>=6&&$english/count($words)>.08&&$dutch/count($words)<.02)$issues[]='nl: tekst lijkt grotendeels onvertaald Engels';
            }
            foreach($body as $paragraph){
                if(!preg_match('/[.!?…][\x{201D}\x{2019}"\x{0027})\]]*$/u',trim($paragraph)))$issues[]="$language: onafgemaakte alinea";
                if(substr_count($paragraph,'"')%2!==0||substr_count($paragraph,'“')!==substr_count($paragraph,'”'))$issues[]="$language: onafgemaakt citaat";
            }
            $sentences=[];
            foreach($body as $paragraph)foreach(preg_split('/(?<=[.!?])\s+/u',$paragraph) as $sentence){
                $key=mb_strtolower(trim(preg_replace('/\s+/u',' ',$sentence)));
                if(mb_strlen($key)<45)continue;
                if(isset($sentences[$key]))$issues[]="$language: herhaalde zin";
                $sentences[$key]=true;
            }
            if($language==='nl'&&preg_match('/\b(binnenkantehuiskunst\w*|voertuigengeleidelijk\w*|voetdenkbaar|milieuweters|combatteren|EU-gebackt\w*|sportboek)\b/iu',$all))$issues[]='nl: onjuiste of onbegrijpelijke vertaling';
            if(preg_match('/^(Deze (samenwerking|ontwikkeling|investering|actie|initiatief) (benadrukt|toont)|Dit is een inspirerend voorbeeld|This (collaboration|development|initiative) (highlights|underscores)|This is an inspiring example)/iu',trim(end($body)?:'')))$issues[]="$language: algemene positieve slotzin zonder nieuwsfeit";
        }
        return array_values(array_unique($issues));
    }
}
