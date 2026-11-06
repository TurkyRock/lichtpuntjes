<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{Schema,DB};
return new class extends Migration {
 public function up():void {
  $parents=DB::table('comments')->pluck('parent_id','id')->all();
  if(DB::getDriverName()==='sqlite'){
   // SQLite can add this nullable reference directly, preserving existing FK children.
   DB::statement('ALTER TABLE comments ADD COLUMN thread_root_id INTEGER REFERENCES comments(id) ON DELETE SET NULL');
   Schema::table('comments',fn(Blueprint $t)=>$t->index(['thread_root_id','status','created_at']));
  }else Schema::table('comments',function(Blueprint $t){$t->foreignId('thread_root_id')->nullable()->constrained('comments')->nullOnDelete();$t->index(['thread_root_id','status','created_at']);});
  // Preserve old nested conversations without changing reply targets or text.
  foreach($parents as $id=>$parent){if(!$parent)continue;$cursor=$id;$seen=[];while(isset($parents[$cursor])&&$parents[$cursor]&&!isset($seen[$cursor])){$seen[$cursor]=true;$cursor=$parents[$cursor];}$root=$cursor!=$id&&array_key_exists($cursor,$parents)&&!isset($seen[$cursor])?$cursor:null;DB::table('comments')->where('id',$id)->update(['parent_id'=>$parent,'thread_root_id'=>$root]);}
  Schema::create('comment_hearts',function(Blueprint $t){$t->foreignId('comment_id')->constrained('comments')->cascadeOnDelete();$t->foreignId('user_id')->constrained('users')->cascadeOnDelete();$t->timestamp('created_at');$t->primary(['comment_id','user_id']);$t->index('user_id');});
 }
 public function down():void {Schema::dropIfExists('comment_hearts');if(DB::getDriverName()==='sqlite'){Schema::table('comments',fn(Blueprint $t)=>$t->dropIndex(['thread_root_id','status','created_at']));DB::statement('ALTER TABLE comments DROP COLUMN thread_root_id');}else Schema::table('comments',function(Blueprint $t){$t->dropIndex(['thread_root_id','status','created_at']);$t->dropConstrainedForeignId('thread_root_id');});}
};
