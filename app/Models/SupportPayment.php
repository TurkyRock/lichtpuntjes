<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SupportPayment extends Model {
 protected $guarded=[];
 public function pledge(){return $this->belongsTo(SupportPledge::class,'pledge_id');}
 protected function casts():array{return ['paid_at'=>'datetime'];}
}
