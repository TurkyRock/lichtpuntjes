<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {
  Schema::create('user_visits',function(Blueprint $t){$t->id();$t->foreignId('user_id')->constrained()->cascadeOnDelete();$t->date('visit_date');$t->unique(['user_id','visit_date']);});
  Schema::create('user_activities',function(Blueprint $t){$t->id();$t->foreignId('user_id')->constrained()->cascadeOnDelete();$t->date('activity_date');$t->string('kind',20);$t->string('reference',120);$t->unique(['user_id','activity_date','kind','reference'],'user_activity_once');});
 }
 public function down():void {Schema::dropIfExists('user_activities');Schema::dropIfExists('user_visits');}
};
