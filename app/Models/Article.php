<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Article extends Model {
    protected static function booted(): void {
        static::saved(fn(Article $article)=>app(\App\Services\NewsFeeds\Regions::class)->sync($article));
        static::updating(function(Article $article){
            if($article->isDirty('status')&&$article->status==='published' && !app(\App\Services\NewsFeeds\Rights::class)->permitted($article)){
                $article->status='draft';$article->reason='Bronrechten niet vastgesteld; publicatie tegengehouden.';
            }
            // Likes, reads and background scores must not pretend the story was edited.
            if($article->getOriginal('status')==='published'&&$article->isDirty(['nl','en','source_title','source_description','source_url']))$article->content_updated_at=now();
            if($article->isDirty('status')&&$article->status==='published')app(\App\Services\NewsDuplicates::class)->hideIfDuplicate($article);
        });
    }
    public function author(){return $this->belongsTo(User::class,'author_id');}
    public function scopeVisible($q){return $q->where('status','published')->whereNull('hidden_at')->where(function($q){$q->where('origin','news')->orWhere(function($q){$q->where('origin','community')->whereHas('author',fn($q)=>$q->where('is_suspended',false));});});}
    protected $guarded = ['id'];
    protected function casts(): array {
        return ['source_attribution'=>'array','content_updated_at'=>'datetime','interest_topics'=>'array','story_places'=>'array','audience_groups'=>'array','source_countries'=>'array','daily_score'=>'integer','daily_reason'=>'array','daily_scored_at'=>'datetime','hidden_at'=>'datetime','views_count'=>'integer', 'likes_count'=>'integer', 'photo_next_attempt_at'=>'datetime', 'photo'=>'array', 'nl'=>'array', 'en'=>'array', 'published_at'=>'datetime', 'attempts'=>'integer'];
    }
}
