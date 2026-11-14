<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {
  Schema::create('laptop_image_jobs',function(Blueprint $t){
   $t->id();$t->string('target_type',12);$t->unsignedBigInteger('target_id');$t->string('content_hash',64);$t->unsignedTinyInteger('attempt')->default(1);
   $t->string('status',20)->default('queued')->index();$t->text('payload');$t->text('result')->nullable();$t->string('lease_hash',64)->nullable();$t->string('completion_hash',64)->nullable();$t->string('worker_id',80)->nullable();$t->timestamp('lease_until')->nullable()->index();$t->timestamp('available_at')->nullable();$t->unsignedInteger('claims')->default(0);$t->string('failure_code',40)->nullable();$t->timestamps();
   $t->unique(['target_type','target_id','content_hash','attempt'],'laptop_target_version_attempt');
  });
  Schema::create('ai_service_pauses',function(Blueprint $t){$t->string('service')->primary();$t->string('reason',20);$t->timestamp('retry_at');$t->timestamps();});
  Schema::create('laptop_image_workers',function(Blueprint $t){$t->string('worker_id',80)->primary();$t->timestamp('last_seen_at');});
  foreach(['positive_moments','lightspot_submissions'] as $table)Schema::table($table,function(Blueprint $t){$t->timestamp('ai_retry_at')->nullable()->index();$t->string('ai_wait_reason',24)->nullable();});
 }
 public function down():void {foreach(['positive_moments','lightspot_submissions'] as $table)Schema::table($table,fn(Blueprint $t)=>$t->dropColumn(['ai_retry_at','ai_wait_reason']));Schema::dropIfExists('laptop_image_workers');Schema::dropIfExists('ai_service_pauses');Schema::dropIfExists('laptop_image_jobs');}
};
