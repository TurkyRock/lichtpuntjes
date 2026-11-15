<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SocialDelivery extends Model {
 protected $guarded=['id'];
 protected function casts():array {return ['published_at'=>'datetime','checked_at'=>'datetime'];}
 public function publication(){return $this->belongsTo(SocialPublication::class);}
}
