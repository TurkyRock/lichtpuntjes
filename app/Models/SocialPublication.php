<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SocialPublication extends Model {
 protected $guarded=['id'];
 protected function casts():array {return ['snapshot'=>'array','captions'=>'array','edition_date'=>'date','due_at'=>'datetime','first_shared_at'=>'datetime'];}
 public function deliveries(){return $this->hasMany(SocialDelivery::class,'publication_id');}
 public function article(){return $this->belongsTo(Article::class);}
}
