<?php
namespace App\Services;

use App\Models\Article;

/** Daily features are a warm editorial selection, independent of popularity or revenue. */
class DailyFeaturePolicy
{
    public const VERSION = '2026-11-19';
    public const FOCUSES = ['nature_animals', 'kindness_community', 'joy_creativity', 'human_hope', 'business', 'other'];
    private const ALLOWED = ['nature_animals', 'kindness_community', 'joy_creativity', 'human_hope'];

    public static function allows(Article $article): bool
    {
        if (in_array($article->category, ['business','finance','economy'], true)) return false;
        $head = self::text([
            $article->source_title, $article->nl['title'] ?? '', $article->en['title'] ?? '',
            $article->nl['teaser'] ?? '', $article->en['teaser'] ?? '',
        ]);
        // Check the original headline too: a warm rewrite must not disguise business news.
        if (self::business($head)) return false;
        $reason = $article->daily_reason ?? [];
        if (($reason['focus'] ?? null) === 'business') return false;
        if (($reason['policy'] ?? null) === self::VERSION) {
            return in_array($reason['focus'] ?? null, self::ALLOWED, true);
        }
        // Older scores are not evidence of the new editorial focus. Require a concrete
        // warm/nature cue; an ambiguous old article stays in the ordinary feed instead.
        return (bool) preg_match('~\b(?:buurt(?:tuin|bewoners|feest|initiatief)?|vrijwilliger\w*|vriend(?:elijk\w*|schap|en)?|glimlach\w*|liefde|lief(?:s|devol)?|zonnig\w*|klein(?:e)? geluk\w*|natuur(?:herstel|gebied)?|biodiversiteit|bos(?:sen)?|bloemen?|bloesem|tuin(?:en)?|dieren?|dierentuin|vogel\w*|zeehond\w*|schildpad\w*|eekhoorn\w*|gered|redding|terugkeer|herstelt|herstel|samen genieten|speel(?:tuin|plezier)|muziek|kunst|creatief|creativiteit|neighbou?r\w*|volunteer\w*|kindness|friendship|smil\w*|sunny|garden\w*|flowers?|wildlife|animals?|birds?|forest\w*|rescue\w*|recover\w*|nature|playground|creativity)\b~u', $head);
    }

    public static function business(string $text): bool
    {
        return (bool) preg_match('~\b(?:omzet\w*|winst(?:groei|cijfers|verwachting|record)?|aandeel(?:houder\w*|koers\w*|markt\w*)?|beurs(?:gang|koers|fonds|genoteerd)\w*|overname\w*|fusie\w*|financieringsronde\w*|financiering\w*|investering\w*|investeringsronde\w*|markt(?:groei|waarde|aandeel|prognose|analyse|verwachting)\w*|bedrijfs(?:nieuws|resultaat|award|prijs|overname|groei)\w*|zakelijk\w*|ceo|cfo|directie(?:benoeming)?|productlancering\w*|lanceert|introduceert|verkoop(?:cijfers|record|groei)\w*|commercie\w*|revenue\w*|earnings|quarterly results|shareholders?|stock(?:s|market)?|ipo|acquisition\w*|merger\w*|funding\w*|investment\w*|venture capital|market (?:size|growth|share|forecast|report)|business award\w*|chief executive|product launch\w*|launches|unveils|introduces|expands? (?:its|their) (?:business|services)|sales (?:record|growth|figures)|corporate partnership\w*)\b~u', $text);
    }

    public static function text(array $parts): string
    {
        return mb_strtolower(html_entity_decode(strip_tags(implode(' ', array_filter($parts, 'is_string'))), ENT_QUOTES | ENT_HTML5, 'UTF-8'), 'UTF-8');
    }
}
