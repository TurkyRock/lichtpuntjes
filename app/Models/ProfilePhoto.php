<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class ProfilePhoto extends Model {
 protected $guarded=['id'];
 protected $hidden=['path'];
 protected function casts():array {return ['user_id'=>'integer','reviewed_by'=>'integer'];}
 public function user(){return $this->belongsTo(User::class);}
}
