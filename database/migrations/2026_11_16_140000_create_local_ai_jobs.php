<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void{Schema::create('local_ai_jobs',function(Blueprint $t){$t->id();$t->string('request_key',64)->unique();$t->string('model',80);$t->string('status',20)->default('queued')->index();$t->longText('payload')->nullable();$t->longText('result')->nullable();$t->string('lease_hash',64)->nullable();$t->string('completion_hash',64)->nullable();$t->unsignedInteger('claims')->default(0);$t->timestamp('lease_until')->nullable();$t->timestamp('available_at');$t->timestamp('expires_at')->index();$t->timestamps();});}
 public function down():void{Schema::dropIfExists('local_ai_jobs');}
};
