<?php
namespace App\Http\Controllers;
use App\Models\ProfilePhoto;
use App\Models\User;
use App\Jobs\ReviewProfilePhoto;
use App\Services\PortraitNormalizer;
use App\Services\ProfilePhotoModerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
class ProfilePhotoController extends Controller {
 public function store(Request $r,PortraitNormalizer $normalizer){
  $r->validate(['photo'=>'required|file|image|mimes:jpg,jpeg,png,webp|max:4096|dimensions:min_width=128,min_height=128,max_width=2400,max_height=2400','self_photo'=>'accepted','photo_processing'=>'accepted']);
  $bytes=$normalizer->jpeg($r->file('photo'));$path='profile-photos/'.Str::uuid().'.jpg';
  abort_unless(Storage::disk('local')->put($path,$bytes),503,'Foto opslaan mislukt.');
  try{$photo=DB::transaction(function()use($r,$path){User::whereKey($r->user()->id)->lockForUpdate()->firstOrFail();return ProfilePhoto::create(['user_id'=>$r->user()->id,'path'=>$path,'status'=>'pending','self_confirmed_at'=>now()]);});}catch(\Throwable $e){Storage::disk('local')->delete($path);throw $e;}
  try{ReviewProfilePhoto::dispatch($photo->id);}catch(\Throwable $e){$photo->update(['status'=>'doubt','reason'=>'De controle kon niet worden gestart. De beheerder kijkt ernaar.']);}
  return back()->with('status',app()->getLocale()==='en'?'Photo received. It stays private until approved.':'Foto ontvangen. Deze blijft privé totdat hij is goedgekeurd.');
 }
 public function destroy(Request $r){
  $paths=[];DB::transaction(function()use($r,&$paths){$u=User::whereKey($r->user()->id)->lockForUpdate()->firstOrFail();$u->forceFill(['profile_photo_id'=>null])->save();$photos=ProfilePhoto::where('user_id',$u->id)->lockForUpdate()->get();foreach($photos as $p){if($p->path)$paths[]=$p->path;$p->update(['path'=>null,'status'=>'removed']);}});
  foreach($paths as $p)Storage::disk('local')->delete($p);
  return back()->with('status',app()->getLocale()==='en'?'Profile photo removed.':'Profielfoto verwijderd.');
 }
 public function show(Request $r,ProfilePhoto $photo){abort_unless($photo->status==='approved'&&$photo->path&&!$photo->user->is_suspended&&$photo->user->profile_photo_id===$photo->id,404);return $this->image($photo);}
 public function preview(Request $r,ProfilePhoto $photo){abort_unless($r->user()?->id===$photo->user_id||$r->user()?->is_admin,403);return $this->image($photo);}
 private function image(ProfilePhoto $photo){abort_unless($photo->path&&Storage::disk('local')->exists($photo->path),404);return response(Storage::disk('local')->get($photo->path),200,['Content-Type'=>'image/jpeg','X-Content-Type-Options'=>'nosniff','Cache-Control'=>'private, no-store','Content-Disposition'=>'inline; filename="profielfoto.jpg"']);}
 public function moderate(Request $r,ProfilePhoto $photo,ProfilePhotoModerator $moderator){abort_unless($r->user()?->is_admin,403);$v=$r->validate(['decision'=>'required|in:approved,rejected','note'=>'required|string|min:3|max:1000','portrait_confirmed'=>'accepted']);$moderator->decide($photo,$v['decision'],$v['note'],$r->user());return back()->with('status','Fotobeslissing opgeslagen.');}
}
