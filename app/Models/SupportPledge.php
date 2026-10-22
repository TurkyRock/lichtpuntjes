<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SupportPledge extends Model {
 public $incrementing=false;protected $keyType='string';protected $guarded=[];
 protected function casts():array{return ['consented_at'=>'datetime','stopped_at'=>'datetime'];}
 public function payments(){return $this->hasMany(SupportPayment::class,'pledge_id');}
 public function user(){return $this->belongsTo(User::class);}
}
