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
            if (array_intersect($keys, $this->keys($other))) return $other;
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
