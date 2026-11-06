<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB,Schema};
return new class extends Migration {
 public function up():void {
  Schema::table('beta_answers',function(Blueprint $t){$t->json('summary')->nullable();});
  // Keep all existing answers; allow finished testers to add the new final step.
  DB::table('beta_participants')->whereNotNull('completed_at')->whereNotExists(function($q){$q->selectRaw('1')->from('beta_answers')->whereColumn('beta_answers.participant_id','beta_participants.id')->where('task','summary')->whereIn('outcome',['yes','skipped']);})->update(['completed_at'=>null]);
 }
 public function down():void {Schema::table('beta_answers',function(Blueprint $t){$t->dropColumn('summary');});}
};
