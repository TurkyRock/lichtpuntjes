<?php
namespace App\Services\Maintenance;
use Illuminate\Support\Facades\{Cache,DB,Log};
class StorageUsage {
 public function report(bool $fresh=false):array {
  if(!$fresh&&($report=Cache::get('site-storage-report')))return $report;
  $bytes=0;$files=0;$complete=true;$breakdown=['news_images'=>0,'moment_images'=>0,'profile_photos'=>0,'logs'=>0,'other'=>0];$tracked=['news_images'=>public_path('news-images'),'moment_images'=>\Illuminate\Support\Facades\Storage::disk('local')->path('moment-images'),'profile_photos'=>\Illuminate\Support\Facades\Storage::disk('local')->path('profile-photos'),'logs'=>storage_path('logs')];$root=realpath(config('retention.usage_root'));
  if(!$root)throw new \RuntimeException('Opslagmap niet gevonden.');
  $walk=function(string $dir)use(&$walk,&$bytes,&$files,&$complete,&$breakdown,$tracked){
   $names=@scandir($dir);if($names===false){$complete=false;return;}
   foreach($names as $name){if($name==='.'||$name==='..')continue;$path=$dir.DIRECTORY_SEPARATOR.$name;
    if(is_link($path))continue;
    if(is_dir($path))$walk($path);elseif(is_file($path)){$size=@filesize($path);if($size===false)$complete=false;else{$bytes+=$size;$files++;$kind='other';foreach($tracked as $label=>$prefix)if(str_starts_with($path,rtrim($prefix,DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR)){$kind=$label;break;}$breakdown[$kind]+=$size;}}
   }
  };$walk($root);$database=0;
  if(DB::connection()->getDriverName()==='mysql'){
   try{$row=DB::selectOne('SELECT COALESCE(SUM(data_length + index_length),0) AS bytes FROM information_schema.tables WHERE table_schema = ?',[DB::connection()->getDatabaseName()]);$database=(int)$row->bytes;}catch(\Throwable $e){$complete=false;}
  }
  $used=$bytes+$database;$quota=max(1,(int)config('retention.quota_bytes'));$percent=round(100*$used/$quota,1);
  $free=@disk_free_space($root);$paused=$percent>=config('retention.pause_percent')||($free!==false&&$free<100*1024*1024);
  $report=['breakdown'=>$breakdown,'measured_at'=>now()->toIso8601String(),'files'=>$files,'file_bytes'=>$bytes,'database_bytes'=>$database,'used_bytes'=>$used,'quota_bytes'=>$quota,'percent'=>$percent,'complete'=>$complete,'paused'=>$paused,'warning'=>$percent>=config('retention.warning_percent')||!$complete,'disk_free_bytes'=>$free];
  Cache::put('site-storage-report',$report,now()->addMinutes(15));
  if(($report['warning']||$paused)&&Cache::add('site-storage-warning',true,now()->addHours(6)))Log::warning('Opslagcontrole: bekijk /beheer/opslag.',array_intersect_key($report,array_flip(['percent','complete','paused'])));
  return $report;
 }
 public function importsAllowed():bool {return !$this->report()['paused'];}
}
