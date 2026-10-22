<?php
namespace App\Models;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
class User extends Authenticatable implements MustVerifyEmail {
 use HasFactory,Notifiable;
 protected $fillable=['name','email','password'];
 protected $hidden=['password','remember_token','email','invite_code','profile_photo_id'];
 public function moments(){return $this->hasMany(PositiveMoment::class);}
 public function comments(){return $this->hasMany(Comment::class);}
 public function submissions(){return $this->hasMany(LightspotSubmission::class);}
 public function photos(){return $this->hasMany(ProfilePhoto::class);}
 public function profilePhoto(){return $this->belongsTo(ProfilePhoto::class,'profile_photo_id');}
 public function avatarUrl():?string {return !$this->is_suspended&&$this->profilePhoto?->status==='approved'?'/profielfotos/'.$this->profile_photo_id:null;}
 protected function casts():array {return ['profile_photo_id'=>'integer','email_verified_at'=>'datetime','community_accepted_at'=>'datetime','password'=>'hashed','is_admin'=>'boolean','is_suspended'=>'boolean'];}
}
