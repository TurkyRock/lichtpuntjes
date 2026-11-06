<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{Schema,DB};
return new class extends Migration {
 public function up():void {
  Schema::create('beta_rounds',function(Blueprint $t){$t->unsignedInteger('id')->primary();$t->string('name');});
  DB::table('beta_rounds')->insert(['id'=>1,'name'=>'Eerste proefronde']);
  Schema::create('beta_participants',function(Blueprint $t){$t->uuid('id')->primary();$t->string('label',80);$t->string('token_hash',64)->unique();$t->unsignedInteger('access_version')->default(1);$t->timestamp('expires_at');$t->timestamp('revoked_at')->nullable();$t->timestamp('started_at')->nullable();$t->timestamp('completed_at')->nullable();$t->timestamps();});
  Schema::create('beta_answers',function(Blueprint $t){$t->id();$t->foreignUuid('participant_id')->constrained('beta_participants')->cascadeOnDelete();$t->string('task',40);$t->string('outcome',20);$t->unsignedTinyInteger('ease')->nullable();$t->string('device',20)->nullable();$t->text('comment')->nullable();$t->string('page_path',400)->nullable();$t->unsignedInteger('version')->default(1);$t->timestamps();$t->unique(['participant_id','task']);});
 }
 public function down():void {Schema::dropIfExists('beta_answers');Schema::dropIfExists('beta_participants');Schema::dropIfExists('beta_rounds');}
};
