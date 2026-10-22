<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PositiveMoment extends Model {
 protected $guarded=['id'];
 protected $attributes=['revision'=>1,'status'=>'pending','visibility'=>'private'];
 public function hearts(){return $this->belongsToMany(User::class,'moment_hearts','moment_id','user_id');}
 protected function casts():array {return ['body'=>'encrypted','reason'=>'encrypted:array','moment_date'=>'date','user_id'=>'integer','revision'=>'integer'];}
 public function user(){return $this->belongsTo(User::class);}
 public function scopePublicVisible($q){return $q->where('visibility','public')->where('status','approved')->whereHas('user',fn($u)=>$u->where('is_suspended',false));}
}
