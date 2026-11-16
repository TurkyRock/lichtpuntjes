<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {
  Schema::table('users',function(Blueprint $t){$t->timestamp('adult_confirmed_at')->nullable();});
  Schema::create('privacy_file_deletions',function(Blueprint $t){$t->id();$t->string('path')->unique();$t->timestamp('created_at');});
  Schema::create('content_reports',function(Blueprint $t){$t->id();$t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();$t->string('kind',12);$t->unsignedBigInteger('target_id');$t->string('reason',40);$t->text('message')->nullable();$t->timestamp('resolved_at')->nullable();$t->timestamps();$t->unique(['user_id','kind','target_id']);});
  Schema::create('contribution_consents',function(Blueprint $t){$t->id();$t->foreignId('user_id')->constrained()->cascadeOnDelete();$t->string('kind',12);$t->unsignedBigInteger('target_id');$t->unsignedInteger('revision')->default(1);$t->string('visibility',12);$t->string('version',20);$t->timestamp('created_at');$t->index(['kind','target_id']);});
  Schema::table('support_pledges',function(Blueprint $t){$t->text('management_email')->nullable();$t->string('management_email_hash',64)->nullable()->index();$t->timestamp('detached_at')->nullable();});
 }
 public function down():void {Schema::dropIfExists('privacy_file_deletions');Schema::dropIfExists('contribution_consents');Schema::dropIfExists('content_reports');Schema::table('support_pledges',fn(Blueprint $t)=>$t->dropColumn(['management_email','management_email_hash','detached_at']));Schema::table('users',fn(Blueprint $t)=>$t->dropColumn('adult_confirmed_at'));}
};
