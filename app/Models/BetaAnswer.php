<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class BetaAnswer extends Model {
 protected $guarded=[];protected function casts():array{return ['ease'=>'integer','version'=>'integer','summary'=>'array'];}
}
