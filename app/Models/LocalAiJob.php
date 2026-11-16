<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class LocalAiJob extends Model {
 protected $guarded=['id'];
 protected $hidden=['request_key','payload','result','lease_hash','completion_hash'];
 protected function casts():array{return ['payload'=>'encrypted:array','result'=>'encrypted:array','expires_at'=>'datetime','available_at'=>'datetime','lease_until'=>'datetime','claims'=>'integer'];}
}
