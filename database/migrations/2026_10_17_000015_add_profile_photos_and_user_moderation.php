<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {
  Schema::create('profile_photos',function(Blueprint $t){$t->id();$t->foreignId('user_id')->constrained()->cascadeOnDelete();$t->string('path')->nullable();$t->string('status',20)->default('pending')->index();$t->text('reason')->nullable();$t->string('ai_model')->nullable();$t->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();$t->timestamp('self_confirmed_at');$t->timestamps();});
  Schema::table('users',fn(Blueprint $t)=>$t->foreignId('profile_photo_id')->nullable()->constrained('profile_photos')->nullOnDelete());
  Schema::create('user_moderation_events',function(Blueprint $t){$t->id();$t->foreignId('user_id')->constrained()->cascadeOnDelete();$t->foreignId('moderator_id')->nullable()->constrained('users')->nullOnDelete();$t->string('action',30);$t->text('note');$t->timestamp('created_at');});
 }
 public function down():void {Schema::dropIfExists('user_moderation_events');Schema::table('users',fn(Blueprint $t)=>$t->dropConstrainedForeignId('profile_photo_id'));Schema::dropIfExists('profile_photos');}
};
