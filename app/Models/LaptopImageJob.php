<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class LaptopImageJob extends Model {
 protected $guarded=['id'];
 protected $hidden=['payload','result','lease_hash','completion_hash','worker_id'];
 protected function casts():array{return ['payload'=>'encrypted:array','result'=>'array','lease_until'=>'datetime','available_at'=>'datetime','claims'=>'integer','attempt'=>'integer'];}
}
