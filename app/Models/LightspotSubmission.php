<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class LightspotSubmission extends Model {
 protected $guarded=['id'];
 protected function casts():array {return ['ai_retry_at'=>'datetime','reason'=>'array','translations'=>'array','ai_error'=>'boolean','confidence'=>'float','appealed_at'=>'datetime'];}
 public function reviews(){return $this->hasMany(LightspotReview::class,'submission_id');}
 public function user(){return $this->belongsTo(User::class);}
 public function article(){return $this->belongsTo(Article::class);}
 public function reports(){return $this->hasMany(LightspotReport::class,'submission_id');}
}
