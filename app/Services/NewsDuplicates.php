<?php
namespace App\Services;

use App\Models\Article;
use Illuminate\Support\Str;

class NewsDuplicates
{
    public const STATUSES = ['pending', 'photo_trial_pending', 'error', 'draft', 'published', 'replacement', 'news_ready'];

    public static function titleKey(?string $title): string
    {
        $text = Str::lower(Str::ascii(html_entity_decode(strip_tags($title ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
        $text = preg_replace('/[\x{2018}\x{2019}\x{0027}]/u', '', $text);
        return trim(preg_replace('/[^\p{L}\p{N}]+/u', ' ', $text));
    }

    public function keys(Article $article): array
    {
        return array_values(array_unique(array_filter(array_map(self::titleKey(...), [
            $article->source_title, $article->nl['title'] ?? null, $article->en['title'] ?? null,
        ]))));
    }

    private function eventWords(string $title): array
    {
        // Only compare the headline before a quote/subtitle, not broad person names alone.
        $title=preg_split('/[:：]/u',$title,2)[0];
        $words=explode(' ',self::titleKey($title));
        $stop=['de','het','een','en','van','voor','met','als','is','wordt','worden','nieuwe','nieuw','zijn','haar','the','a','an','and','of','for','with','as','is','becomes','new'];
        return array_values(array_unique(array_diff($words,$stop,[''])));
    }

    private function sameRecentHeadline(Article $article,Article $other): bool
    {
        // Do not retrospectively hide published articles when this policy is installed.
        if($article->status==='published')return false;
        $at=$article->published_at??$article->created_at??now();
        $otherAt=$other->published_at??$other->created_at;
        if(!$otherAt||abs($at->diffInHours($otherAt,false))>72)return false;
        foreach([$article->source_title,$article->nl['title']??'',$article->en['title']??''] as $title){
            $left=$this->eventWords($title??'');if(count($left)<4)continue;
            foreach([$other->source_title,$other->nl['title']??'',$other->en['title']??''] as $candidate){
                $right=$this->eventWords($candidate??'');if(count($right)<4)continue;
                $shared=count(array_intersect($left,$right));$union=count(array_unique(array_merge($left,$right)));
                if($shared>=4&&$shared/$union>=.85)return true;
            }
        }
        return false;
    }

    public function find(Article $article): ?Article
    {
        $keys = $this->keys($article);
        if (!$keys || ($article->origin ?? 'news') !== 'news') return null;
        $query = Article::where('origin', 'news')->whereNull('hidden_at')->whereIn('status', self::STATUSES);
        if ($article->exists) {
            $query->whereKeyNot($article->id);
            if ($article->status === 'published') $query->where('id', '<', $article->id);
            else $query->where(function ($q) use ($article) {
                $q->where('status', 'published')->orWhere('id', '<', $article->id);
            });
        }
        foreach ($query->orderByRaw("CASE WHEN status = 'published' THEN 0 ELSE 1 END")->orderBy('id')->cursor() as $other) {
            if (array_intersect($keys, $this->keys($other))||$this->sameRecentHeadline($article,$other)) return $other;
        }
        return null;
    }

    public function hideIfDuplicate(Article $article): bool
    {
        if (($article->origin ?? 'news') !== 'news' || $article->hidden_at) return false;
        $canonical = $this->find($article);
        if (!$canonical) return false;
        $article->forceFill(['hidden_at' => now(), 'hidden_by' => null,
            'reason' => 'Dubbele nieuwstitel; behouden bericht #'.$canonical->id.'.']);
        return true;
    }

    /** Published articles win over queued copies; otherwise retain the oldest. */
    public function clean(bool $apply = false): array
    {
        $seen = []; $duplicates = [];
        $articles = Article::where('origin', 'news')->whereNull('hidden_at')->whereIn('status', self::STATUSES)
            ->orderByRaw("CASE WHEN status = 'published' THEN 0 ELSE 1 END")->orderBy('id')->cursor();
        foreach ($articles as $article) {
            $keys = $this->keys($article); $canonical = null;
            foreach ($keys as $key) if (isset($seen[$key])) { $canonical = $seen[$key]; break; }
            if ($canonical !== null) {
                $duplicates[] = ['keep' => $canonical, 'hide' => $article->id, 'title' => $article->nl['title'] ?? $article->source_title];
                if ($apply) Article::whereKey($article->id)->whereNull('hidden_at')->update([
                    'hidden_at' => now(), 'hidden_by' => null,
                    'reason' => 'Dubbele nieuwstitel; behouden bericht #'.$canonical.'.',
                ]);
            }
            // Remember alternative language/source titles of hidden copies too.
            foreach ($keys as $key) $seen[$key] ??= $canonical ?? $article->id;
        }
        return $duplicates;
    }
}
