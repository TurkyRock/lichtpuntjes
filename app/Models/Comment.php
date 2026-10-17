<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Comment extends Model {
 protected $guarded=['id'];
 protected function casts():array {return ['approved_at'=>'datetime','ai_confidence'=>'float','ai_error'=>'boolean'];}
 public function parent(){return $this->belongsTo(self::class,'parent_id');}
 public function replies(){return $this->hasMany(self::class,'parent_id');}
 public function scopeVisible($query){return $query->where('status','approved')->whereHas('user',fn($q)=>$q->where('is_suspended',false));}
 public function user(){return $this->belongsTo(User::class);}
 public function article(){return $this->belongsTo(Article::class);}
}
