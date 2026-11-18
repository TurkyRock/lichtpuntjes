<?php
namespace App\Services;

use App\Models\Article;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class NewsPreferences
{
    public const TOPICS = [
        'animals'=>['Dieren','Animals','nature'], 'nature'=>['Natuur','Nature','nature'],
        'science'=>['Wetenschap','Science','science'], 'technology'=>['Technologie','Technology','science'],
        'energy'=>['Duurzaamheid','Sustainability','energy'], 'sport'=>['Sport','Sport','sport'],
        'media'=>['Cultuur & media','Culture & media','media'], 'people'=>['Mensen & mooie initiatieven','People & kind initiatives','people'],
    ];
    public const AGES = ['18-24','25-39','40-59','60plus'];

    public function enabled(?User $user): bool
    {
        return $user && !$user->is_suspended && ($user->news_preferences['enabled'] ?? false);
    }

    public function save(User $user, ?array $preferences): void
    {
        DB::transaction(function () use ($user, $preferences) {
            $current = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            if (empty($preferences['enabled']) || empty($preferences['learn_from_reading'])) {
                app(NewsReadingInterests::class)->forget($current->id);
            }
            $current->forceFill(['news_preferences'=>$preferences, 'news_preferences_answered_at'=>now()])->save();
            $user->forceFill(['news_preferences'=>$preferences, 'news_preferences_answered_at'=>$current->news_preferences_answered_at]);
        });
    }

    /** SQL expressions use the connection's JSON grammar, for both MySQL and SQLite. */
    public function relevance(User $user): array
    {
        $p = $user->news_preferences ?? [];
        $parts = ['0']; $bindings = [];
        $condition = function (Builder $query, int $weight) use (&$parts, &$bindings) {
            $base = $query->getQuery();
            $where = $base->getGrammar()->compileWheres($base);
            $parts[] = '(CASE WHEN '.substr($where, 6).' THEN '.$weight.' ELSE 0 END)';
            array_push($bindings, ...$base->getBindings());
        };
        $interests = array_values(array_intersect($p['interests'] ?? [], array_keys(self::TOPICS)));
        if ($interests) {
            $q = Article::query()->where(function ($q) use ($interests) {
                foreach ($interests as $topic) $q->orWhereJsonContains('interest_topics', $topic);
                // Existing articles without new topic tags remain useful immediately.
                $q->orWhere(fn($q) => $q->whereNull('interest_topics')->whereIn('category', array_unique(array_map(fn($t)=>self::TOPICS[$t][2], $interests))));
            });
            $condition($q, 40);
        }
        $muted = array_values(array_intersect($p['less_topics'] ?? [], array_keys(self::TOPICS)));
        if ($muted) {
            $q = Article::query()->where(function($q) use($muted) {
                foreach ($muted as $topic) $q->orWhereJsonContains('interest_topics', $topic);
                $q->orWhere(fn($q)=>$q->whereNull('interest_topics')->whereIn('category',array_unique(array_map(fn($t)=>self::TOPICS[$t][2],$muted))));
            });
            $condition($q, -80);
        }
        $geography = $p['geography'] ?? 'mixed';
        if ($geography !== 'world') $condition(Article::query()->where('story_country','NL'), $geography === 'netherlands' ? 24 : 8);
        // Region and town are separate explicit choices; no GPS, address or inferred writer location.
        $places = \App\Support\StoryLocation::places($p['place']??null,$p['region']??null);
        if ($places) {
            $q = Article::query()->where(function($q)use($places){
                foreach($places as $place) $q->orWhereJsonContains('story_places',$place);
            });
            $regions=\App\Services\NewsFeeds\Regions::selected($user);
            if($regions)$q->orWhereIn('articles.id',DB::table('article_regions')->whereIn('region',$regions)->select('article_id'));
            $condition($q, 28);
        }
        // Only explicit life-stage topics are tagged; no inferred age stereotypes or exclusions.
        if (in_array($p['age_group'] ?? '', self::AGES, true)) $condition(Article::query()->whereJsonContains('audience_groups',$p['age_group']), 3);
        $profile = app(NewsReadingInterests::class)->profile($user);
        foreach ($profile['scores'] as $category=>$weight) {
            $condition(Article::query()->where('origin','news')->where('category',$category), $weight);
        }
        if ($profile['read_ids']) $condition(Article::query()->whereIn('articles.id',$profile['read_ids']), -12);
        return [implode(' + ', $parts), $bindings];
    }

    public static function metadataSchema(): array
    {
        return [
            'interest_topics'=>['type'=>'ARRAY','items'=>['type'=>'STRING','enum'=>array_keys(self::TOPICS)]],
            'story_country'=>['type'=>'STRING'],
            'story_places'=>['type'=>'ARRAY','items'=>['type'=>'STRING']],
            'audience_groups'=>['type'=>'ARRAY','items'=>['type'=>'STRING','enum'=>self::AGES]],
        ];
    }
    public static function metadata(array $result): array
    {
        $lists = function(string $key, array $allowed) use($result) {
            return array_values(array_unique(array_intersect(array_filter(is_array($result[$key]??null)?$result[$key]:[], 'is_string'),$allowed)));
        };
        $country = strtoupper(trim(is_string($result['story_country']??null)?$result['story_country']:''));
        $places = array_values(array_unique(array_filter(array_map(fn($s)=>mb_strtolower(trim(mb_substr($s,0,100))),array_filter(is_array($result['story_places']??null)?$result['story_places']:[],'is_string')))));
        $topics = $lists('interest_topics',array_keys(self::TOPICS));
        return ['interest_topics'=>$topics ?: null, 'story_country'=>preg_match('/^[A-Z]{2}$/D',$country)?$country:null,
            'story_places'=>array_slice($places,0,8), 'audience_groups'=>$lists('audience_groups',self::AGES)];
    }
}
