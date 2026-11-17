<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB,Schema};
return new class extends Migration {
 public function up():void {
  Schema::create('lightpoint_progress_views',function(Blueprint $t){
   $t->foreignId('user_id')->primary()->constrained()->cascadeOnDelete();
   $t->unsignedBigInteger('account_seen_event_id')->default(0);
   $t->unsignedBigInteger('badges_seen_event_id')->default(0);
   $t->unsignedInteger('badges_seen_points')->default(0);$t->timestamps();
  });
  // Existing history starts read; future positive events start the light trail.
  DB::table('users')->select('id')->chunkById(250,function($users){
   $totals=DB::table('lightpoint_events')->whereIn('user_id',$users->pluck('id'))->where('active',true)
    ->selectRaw('user_id, MAX(id) as last_id, SUM(points) as points')->groupBy('user_id')->get()->keyBy('user_id');
   $rows=[];foreach($users as $u){$s=$totals->get($u->id);$rows[]=['user_id'=>$u->id,'account_seen_event_id'=>$s?->last_id??0,
    'badges_seen_event_id'=>$s?->last_id??0,'badges_seen_points'=>max(0,(int)($s?->points??0)),'created_at'=>now(),'updated_at'=>now()];}
   if($rows)DB::table('lightpoint_progress_views')->insert($rows);
  });
 }
 public function down():void {Schema::dropIfExists('lightpoint_progress_views');}
};
