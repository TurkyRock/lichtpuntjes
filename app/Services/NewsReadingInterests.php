<?php
namespace App\Services;

use App\Models\{Article, User};
use Illuminate\Support\Facades\DB;

/** Optional, first-party recommendations; never records contribution text or sensitive topics. */
class NewsReadingInterests
{
    public const DAYS = 90;
    public const LIMIT = 500;
    public function enabled(?User $user): bool
    {
        return app(NewsPreferences::class)->enabled($user) && ($user->news_preferences['learn_from_reading'] ?? false);
    }
    public function forget(int $userId): void
    {
        DB::table('user_news_signals')->where('user_id', $userId)->delete();
    }
    public function record(?User $user, Article $article, string $kind, bool $active = true): void
    {
        if (!$user || !in_array($kind, ['read','like'], true)) return;
        DB::transaction(function () use ($user, $article, $kind, $active) {
            // Same lock as preference changes: withdrawal cannot race a new signal.
            $current = User::whereKey($user->id)->lockForUpdate()->first();
            if (!$current || !$this->enabled($current)) return;
            $signals = DB::table('user_news_signals')->where('user_id', $current->id);
            (clone $signals)->where('created_at', '<', now()->subDays(self::DAYS))->delete();
            if (!$active) {
                (clone $signals)->where('article_id', $article->id)->where('kind', $kind)->delete();
                return;
            }
            // Broad editorial categories only. No moments, comments, self-written stories or inferred health/religion.
            if ($article->origin !== 'news' || !in_array($article->category, ['nature','science','energy','sport','media','people'], true)) return;
            DB::table('user_news_signals')->insertOrIgnore([
                'user_id'=>$current->id, 'article_id'=>$article->id, 'kind'=>$kind,
                'category'=>$article->category, 'created_at'=>now(),
            ]);
            $old = (clone $signals)->orderByDesc('id')->skip(self::LIMIT)->take(self::LIMIT)->pluck('id');
            if ($old->isNotEmpty()) DB::table('user_news_signals')->whereIn('id', $old)->delete();
        });
    }
    public function profile(User $user): array
    {
        if (!$this->enabled($user)) return ['scores'=>[], 'read_ids'=>[]];
        $weights = []; $read = [];
        $signals = DB::table('user_news_signals')->where('user_id', $user->id)
            ->where('created_at', '>=', now()->subDays(self::DAYS))->orderByDesc('id')->limit(self::LIMIT)->get();
        foreach ($signals as $signal) {
            $days = max(0, \Carbon\Carbon::parse($signal->created_at)->diffInDays(now()));
            $weights[$signal->category] = ($weights[$signal->category] ?? 0) + ($signal->kind === 'like' ? 4 : 1) * pow(0.5, $days / 30);
            if ($signal->kind === 'read') $read[] = (int)$signal->article_id;
        }
        return ['scores'=>array_map(fn($weight)=>(int)round(24 * $weight / ($weight + 8)), $weights), 'read_ids'=>$read];
    }
}
