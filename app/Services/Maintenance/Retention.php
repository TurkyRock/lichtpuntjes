<?php
namespace App\Services\Maintenance;
use App\Models\{Article,PositiveMoment,LaptopImageJob,LightspotSubmission};
use Illuminate\Support\Facades\{Cache,DB,File,Storage};
class Retention {
 public function run(bool $apply=false):array {
  $lock=Cache::lock('site-retention',3600);if(!$lock->get())throw new \RuntimeException('Er loopt al een opruimronde.');
  $media=Cache::lock('site-media-maintenance',3600);if(!$media->get()){$lock->release();throw new \RuntimeException('Afbeeldingen worden verwerkt; probeer later opnieuw.');}
  try{
   $report=['at'=>now()->toIso8601String(),'mode'=>$apply?'applied':'preview','items'=>[],'bytes'=>0,'counts'=>[]];$limit=(int)config('retention.max_items',500);
   $record=function($kind,$id,$bytes=0)use(&$report){$report['counts'][$kind]=($report['counts'][$kind]??0)+1;$report['bytes']+=$bytes;if(count($report['items'])<100)$report['items'][]=['kind'=>$kind,'id'=>$id,'bytes'=>$bytes];};
   // Soft-deleted personal memories remain private and recoverable for thirty days.
   foreach(PositiveMoment::onlyTrashed()->where('deleted_at','<',now()->subDays(config('retention.trash_days')))->limit($limit)->get() as $m){
    $record('expired_moments',$m->id);if($apply)DB::transaction(function()use($m){$fresh=PositiveMoment::onlyTrashed()->whereKey($m->id)->where('deleted_at','<',now()->subDays(config('retention.trash_days')))->lockForUpdate()->first();if($fresh)$fresh->forceDelete();});
   }
   // Never expire community submissions, published/hidden articles, manual drafts or active imports.
   $reserved=$this->reservedArticles();
   $query=Article::where('origin','news')->whereNull('hidden_at')->whereIn('status',['rejected','insufficient'])->where('updated_at','<',now()->subDays(config('retention.import_days')))->whereNotIn('id',$reserved)
    ->whereNotIn('id',DB::table('comments')->select('article_id'))
    ->whereNotIn('id',DB::table('article_reads')->select('article_id'))
    ->whereNotIn('id',DB::table('user_article_likes')->select('article_id'))
    ->whereNotIn('id',LightspotSubmission::whereNotNull('article_id')->select('article_id'))
    ->whereNotIn('id',DB::table('daily_spotlights')->whereNotNull('article_id')->select('article_id'))
    ->whereNotIn('id',LaptopImageJob::where('target_type','article')->whereIn('status',['queued','processing','generated','completed'])->select('target_id'));
   foreach($query->limit($limit)->get() as $a){$record('rejected_imports',$a->id);if($apply){$fresh=(clone $query)->whereKey($a->id)->first();if($fresh)$fresh->delete();}}
   foreach(LaptopImageJob::whereIn('status',['applied','rejected','cancelled'])->where('updated_at','<',now()->subDays(config('retention.job_days')))->limit($limit)->get() as $job){$record('finished_image_jobs',$job->id);if($apply)LaptopImageJob::whereKey($job->id)->whereIn('status',['applied','rejected','cancelled'])->where('updated_at','<',now()->subDays(config('retention.job_days')))->delete();}
   foreach(LaptopImageJob::where('target_type','moment')->where('status','completed')->where('updated_at','<',now()->subDays(config('retention.job_days')))->limit($limit)->get() as $job){$record('finished_image_jobs',$job->id);if($apply)$job->delete();}
   $references=app(FileReferences::class)->all();
   $roots=[
    [public_path('news-images'),'/news-images/','~^[a-f0-9]{64}\.(jpg|png|webp)$~',config('retention.orphan_days')],
    [Storage::disk('local')->path('moment-images'),'moment-images/','~^[a-f0-9]{48}\.(jpg|webp)$~',config('retention.orphan_days')],
    [Storage::disk('local')->path('profile-photos'),'profile-photos/','~^[a-f0-9-]{36}\.jpg$~',config('retention.orphan_days')],
    [storage_path('app/illustration-backups'),null,'~^\d+-[a-f0-9-]{36}\.json$~',config('retention.orphan_days')],
    [storage_path('app/tmp'),null,'~.+~',config('retention.temporary_days')],
   ];
   $filesDone=0;
   foreach($roots as [$dir,$prefix,$pattern,$days]){
    if(is_link($dir)||!is_dir($dir))continue;if($dir===storage_path('app/tmp')&&LaptopImageJob::where('status','processing')->exists())continue;$real=realpath($dir);
    foreach(File::files($dir) as $file){if($filesDone>=$limit)break 2;$path=$file->getPathname();$name=$file->getFilename();$ref=$prefix.$name;
     if(is_link($path)||realpath(dirname($path))!==$real||!preg_match($pattern,$name)||$file->getMTime()>=now()->subDays($days)->timestamp||($prefix!==null&&isset($references[$ref])))continue;
     $size=$file->getSize();$record('unused_files',$prefix!==null?$ref:'metadata/'.$name,$size);$filesDone++;
     // References were read under the shared upload/assignment lock. New private paths are random.
     if($apply&&($prefix===null||!isset($references[$ref]))&&filemtime($path)<now()->subDays($days)->timestamp)File::delete($path);
    }
   }
   foreach(['failed_jobs'=>'failed_at','job_batches'=>'created_at'] as $table=>$date){
    if(!\Illuminate\Support\Facades\Schema::hasTable($table))continue;
    $q=DB::table($table);$cut=now()->subDays(config('retention.job_days'));
    if($table==='job_batches')$q->whereNotNull('finished_at')->where('finished_at','<',$cut->timestamp);else $q->where($date,'<',$cut);
    $report['counts']['expired_'.$table]=$q->count();if($apply)$q->delete();
   }
   // Lifetime view totals stay on articles; detailed read rows are only needed for recent rankings.
   $reads=DB::table('article_reads')->where('read_date','<',now()->subDays(90)->toDateString());
   $report['counts']['expired_read_details']=$reads->count();if($apply)$reads->delete();
   // A killed backup can leave a private SQL snapshot; never keep these indefinitely.
   foreach(File::glob(storage_path('app/backup-tmp-*')) as $dir){
    if(is_link($dir)||!is_dir($dir)||!preg_match('/^backup-tmp-[a-f0-9]{24}$/D',basename($dir))||filemtime($dir)>=now()->subDay()->timestamp)continue;
    $safe=true;$iterator=new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir,\FilesystemIterator::SKIP_DOTS));
    foreach($iterator as $entry)if($entry->isLink()){$safe=false;break;}
    if($safe){$record('expired_backup_temporary',basename($dir));if($apply)File::deleteDirectory($dir);}
   }
   if($apply)$this->rotateLogs();
   if($apply)Cache::forever('site-retention-last',$report);else Cache::forever('site-retention-preview',$report);
   return $report;
  }finally{$media->release();$lock->release();}
 }
 public function reservedArticles():array {
  $ids=[];
  foreach([config('news_hourly.state_file'),config('laptop-images.news_batch_file',storage_path('app/laptop-news-batch.json'))] as $path){
   if(!$path||!is_file($path))continue;$state=json_decode(File::get($path),true,512,JSON_THROW_ON_ERROR);
   if(!($state['finished']??false))foreach(array_keys($state['selected']??[]) as $id)$ids[]=(int)$id;
  }return array_unique($ids);
 }
 public function rotateLogs():void {
  $root=storage_path('logs');if(is_link($root))return;
  $archive=$root.'/history';File::ensureDirectoryExists($archive);
  // Copy + truncate keeps descriptors held by long-running workers valid.
  foreach(File::files($root) as $file){$path=$file->getPathname();if(is_link($path)||$file->getExtension()!=='log'||$file->getSize()===0)continue;
   $target=$archive.'/'.$file->getFilename().'.'.now('UTC')->format('Y-m-d').'.gz';if(file_exists($target))continue;
   $in=fopen($path,'c+b');if(!$in)continue;
   try{if(!flock($in,LOCK_EX))continue;$out=gzopen($target,'wb6');if(!$out)throw new \RuntimeException('Logarchief niet schrijfbaar.');try{while(!feof($in)){if(gzwrite($out,fread($in,65536))===false)throw new \RuntimeException('Logarchief opslaan mislukt.');}}finally{gzclose($out);}ftruncate($in,0);flock($in,LOCK_UN);}finally{fclose($in);}
  }
  foreach(File::files($archive) as $file)if(!is_link($file->getPathname())&&preg_match('/\.log\.\d{4}-\d{2}-\d{2}\.gz$/',$file->getFilename())&&$file->getMTime()<now()->subDays(config('retention.log_days'))->timestamp)File::delete($file->getPathname());
  foreach(File::glob($root.'/laravel-????-??-??.log') as $path)if(!is_link($path)&&filemtime($path)<now()->subDays(config('retention.log_days'))->timestamp)File::delete($path);
 }
}
