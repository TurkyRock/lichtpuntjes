<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PositiveMoment extends Model {
 use \App\Models\Concerns\HasTranslatedBody;
 protected $hidden=['translations','translation_hash','image_path'];
 protected $guarded=['id'];
 protected $attributes=['revision'=>1,'status'=>'pending','visibility'=>'private'];
 public function hearts(){return $this->belongsToMany(User::class,'moment_hearts','moment_id','user_id');}
 protected function casts():array {return ['image_accepted_at'=>'datetime','image_revision'=>'integer','translations'=>'encrypted:array','body'=>'encrypted','reason'=>'encrypted:array','moment_date'=>'date','user_id'=>'integer','revision'=>'integer'];}
 protected static function booted():void {static::deleted(fn(self $m)=>\App\Services\MomentImageFiles::remove($m->image_path));}
 public function hasMomentImage():bool {return $this->status==='approved'&&$this->image_state==='ready'&&$this->image_revision===$this->revision&&\App\Services\MomentImageFiles::valid($this->image_path);}
 public function momentImageUrl():?string {return $this->hasMomentImage()?'/momentjes/'.$this->id.'/afbeelding?v='.$this->revision:null;}
 public function user(){return $this->belongsTo(User::class);}
 public function scopePublicVisible($q){return $q->where('visibility','public')->where('status','approved')->whereHas('user',fn($u)=>$u->where('is_suspended',false));}
}
