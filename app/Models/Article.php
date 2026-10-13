<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Article extends Model {
    public function author(){return $this->belongsTo(User::class,'author_id');}
    public function scopeVisible($q){return $q->where('status','published')->where(function($q){$q->where('origin','news')->orWhere(function($q){$q->where('origin','community')->whereHas('author',fn($q)=>$q->where('is_suspended',false));});});}
    protected $guarded = ['id'];
    protected function casts(): array {
        return ['views_count'=>'integer', 'likes_count'=>'integer', 'photo_next_attempt_at'=>'datetime', 'photo'=>'array', 'nl'=>'array', 'en'=>'array', 'published_at'=>'datetime', 'attempts'=>'integer'];
    }
}
