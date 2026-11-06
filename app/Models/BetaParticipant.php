<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class BetaParticipant extends Model {
 public $incrementing=false;protected $keyType='string';protected $guarded=[];protected $hidden=['token_hash'];
 protected function casts():array{return ['expires_at'=>'datetime','revoked_at'=>'datetime','started_at'=>'datetime','completed_at'=>'datetime','access_version'=>'integer'];}
 public function answers(){return $this->hasMany(BetaAnswer::class,'participant_id');}
}
