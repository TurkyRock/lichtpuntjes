<?php
namespace App\Services\Maintenance;
use App\Models\{Article,PositiveMoment,ProfilePhoto,LaptopImageJob};
use Illuminate\Support\Facades\File;
class FileReferences {
 // Include all records, even hidden/rejected content and recoverable deleted moments.
 public function all():array {
  $refs=[];$add=function($path)use(&$refs){if(is_string($path))$refs[$path]=true;};
  foreach(Article::select('id','photo')->cursor() as $a)$add($a->photo['url']??null);
  foreach(PositiveMoment::withTrashed()->select('id','image_path')->cursor() as $m)$add($m->image_path);
  foreach(ProfilePhoto::select('id','path')->cursor() as $p)$add($p->path);
  foreach(LaptopImageJob::whereIn('status',['queued','processing','generated','completed'])->cursor() as $j){$add($j->result['url']??null);$add($j->result['path']??null);}
  if(\Illuminate\Support\Facades\Schema::hasTable('social_publications'))foreach(\App\Models\SocialPublication::whereNotNull('first_shared_at')->orWhereNotNull('article_id')->cursor() as $p)$add($p->snapshot['photo']['url']??null);
  // Old photo metadata is a recoverable reference for the seven-day image grace period.
  foreach(File::glob(storage_path('app/illustration-backups/*.json')) as $path){
   if(filemtime($path)<now()->subDays(config('retention.orphan_days'))->timestamp)continue;
   $backup=json_decode(File::get($path),true);$add($backup['photo']['url']??null);
  }
  return $refs;
 }
}
