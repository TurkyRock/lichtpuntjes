<?php
namespace App\Services;

use App\Models\Article;

/** Conservative publication checks, not an independent factual verification. */
final class NewsEditorialQuality
{
    public const POLICY = <<<'POLICY'
EDITORIAL STANDARD v2026-11-18-JOY-INDEPENDENT (new imported news only):
Write a short independent factual summary in your own wording, not a sentence-by-sentence translation or lightly reworded copy. Do not reproduce long source passages, distinctive narrative phrasing or quotations. Keep numbers and facts accurate. When the source contains few facts, keep the summary short instead of filling gaps.
Lichtpuntjes is a gentle break from the business-news cycle. The reader should leave with a small smile, warmth, wonder or grounded hope. Prioritise animal rescues and recoveries, species returning, nature restoration and beautiful discoveries; everyday kindness, friendship and community volunteering; play, creativity, accessible cultural enjoyment, joyful shared activities and grassroots sporting achievements. Small, well-sourced stories are valuable; they need not be economically significant.
Reject a story whose main news value is commercial success: corporate partnerships, contracts, financing rounds, executive appointments, turnover, sales records, market forecasts, share performance, business awards or a new commercial product/service. A sustainability/innovation label or speculative 'could improve lives' ending does not turn a business announcement into a human or nature story. Keep a business-related story only when the supplied source clearly centres a specific delivered benefit for people, animals or their environment (for example reliable power now installed at a hospital, volunteers restoring a habitat, or an accessible community activity). Name companies only when necessary to understand that benefit; never replace their sales pitch with invented joy.
Nature includes animals, seasonal experiences and restoration, not only climate threats and policy. Science should offer understandable wonder or a tangible benefit, not a software/enterprise launch. Sport should feature participants, inclusion, play or achievement, not betting or commercial deals. Media should offer creativity, cultural pleasure or a meaningful joyful milestone, not routine publicity or celebrity drama. Community stories should focus on people doing something kind or enjoyable together.
Use a warm, concrete, lightly written tone without hype, compulsory happiness or telling readers how they must feel. Let the sourced event evoke the emotion. Never add an imagined reaction, rescue, happy ending or benefit. A short story about a garden or a small act of kindness can outrank a multimillion-euro announcement. Prefer fewer strong stories to filling a category quota with weak business news.
Select a concrete benefit, genuine achievement, helpful initiative or joyful milestone that is explicitly supported by the supplied source. A cheerful adjective does not make a story good news.
Reject gambling/betting/casino product promotions, market-size forecasts, stock tips, supplier sales pitches, routine product/TV guest announcements and celebrity photo updates with no meaningful positive development. Do not reject a genuine public benefit merely because a business is involved; distinguish an actual community/environment/health benefit from marketing claims.
Write idiomatic, professionally edited Dutch (B1/B2, adult readers) and natural English. Dutch titles use sentence case, never English Title Case. Headlines must be short, immediately understandable and inviting because of a specific sourced fact. Prefer a clear verb and fluent Dutch word order; create curiosity without clickbait, commands, hype or claims the source does not support. Do not copy an English headline's structure or leave ordinary foreign words untranslated. Translate unfamiliar terms by their meaning in context: Spanish 'bajante' about a lake means 'dalende waterstand', never 'bajante'. A suitable title for exposed tracks could be 'Dalende waterstand legt sporen op de bodem van het meer bloot' when supported by the source. Keep official names such as 'Open Floor' when needed, but explain the event in fluent Dutch. Translate ordinary English phrases, but preserve personal names, official programme/product names and titles of works. Avoid literal calques and invented words. Examples of errors to avoid: 'binnenkantehuiskunst', 'voertuigengeleidelijke', 'voetdenkbaar', 'milieuweters', 'EU-gebackte', 'een vaste tarief vooraf gekeken'. In a solar subsidy application, 'applications' means 'aanvragen', not 'toepassingen'. A sportsbook is a betting service, not a Dutch 'sportboek'. When a term is uncertain, use an accurate plain phrase or choose insufficient; never guess.
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
        if(preg_match('/\b(market size|market forecast|market report|share price|stock picks|stock market|aandelentips|aandelenkoers|beurskoers)\b/u',$title))return 'Zakelijke markt- of beleggingsanalyse past niet bij een dagelijks lichtpuntje.';
        if(preg_match('/\b(oem|odm)\b/u',$title)&&preg_match('/\b(solutions|manufacturer\w*|manufacturing|oplossingen|fabrikant\w*)\b/u',$title))return 'Commerciële leverancierpromotie zonder concreet menselijk of natuurvoordeel.';
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
                if(preg_match('/\bbajante\b/iu',$all))$issues[]='nl: onvertaald Spaans woord (bajante)';
                $words=preg_split('/[^\p{L}\p{N}]+/u',mb_strtolower($all),-1,PREG_SPLIT_NO_EMPTY);
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
    /** Conservative copying signal, not a legal assessment or a safe-word-count rule. */
    public function sourceOverlap(string $source,array $result):array {
        $words=function(string $text):array {preg_match_all('/[\pL\pN]+/u',mb_strtolower($text),$m);return $m[0];};
        $original=$words($source);$phrases=[];
        for($i=0;$i<=count($original)-16;$i++)$phrases[implode(' ',array_slice($original,$i,16))]=true;
        if(!$phrases)return [];
        foreach(['nl','en'] as $locale)foreach($result[$locale]['body']??[] as $paragraph){
            $w=$words($paragraph);
            for($i=0;$i<=count($w)-16;$i++)if(isset($phrases[implode(' ',array_slice($w,$i,16))]))return ['lange letterlijke passage uit de brontekst; eigen samenvatting controleren'];
        }
        return [];
    }

}
