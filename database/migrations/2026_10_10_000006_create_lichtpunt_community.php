<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {
  Schema::table('users',function(Blueprint $t){$t->boolean('is_admin')->default(false);$t->boolean('is_suspended')->default(false);$t->timestamp('community_accepted_at')->nullable();$t->string('community_version',20)->nullable();$t->char('invite_code',32)->nullable()->unique();$t->foreignId('referred_by')->nullable()->constrained('users')->nullOnDelete();});
  Schema::create('user_article_likes',function(Blueprint $t){$t->id();$t->foreignId('user_id')->constrained()->cascadeOnDelete();$t->foreignId('article_id')->constrained()->cascadeOnDelete();$t->timestamp('created_at');$t->unique(['user_id','article_id']);});
  // Browser likes cannot be attributed to accounts. Archive them in article_likes.
  \Illuminate\Support\Facades\DB::table('articles')->update(['likes_count'=>0]);
  Schema::create('comments',function(Blueprint $t){$t->id();$t->foreignId('article_id')->constrained()->cascadeOnDelete();$t->foreignId('user_id')->constrained()->cascadeOnDelete();$t->text('body');$t->string('status',20)->default('pending')->index();$t->text('ai_reason')->nullable();$t->decimal('ai_confidence',4,3)->nullable();$t->boolean('ai_error')->default(false);$t->string('ai_model')->nullable();$t->timestamp('approved_at')->nullable();$t->timestamp('appealed_at')->nullable();$t->foreignId('moderated_by')->nullable()->constrained('users')->nullOnDelete();$t->text('moderator_note')->nullable();$t->timestamps();});
  Schema::create('lightpoint_events',function(Blueprint $t){$t->id();$t->foreignId('user_id')->constrained()->cascadeOnDelete();$t->string('reference',100);$t->string('kind',30);$t->smallInteger('points');$t->boolean('active')->default(true);$t->date('period');$t->string('label');$t->timestamps();$t->unique(['user_id','reference']);$t->index(['user_id','kind','period']);});
  Schema::create('comment_reviews',function(Blueprint $t){$t->id();$t->foreignId('comment_id')->constrained()->cascadeOnDelete();$t->foreignId('moderator_id')->nullable()->constrained('users')->nullOnDelete();$t->string('from_status');$t->string('to_status');$t->text('note');$t->timestamp('created_at');});
 }
 public function down():void {Schema::dropIfExists('comment_reviews');Schema::dropIfExists('lightpoint_events');Schema::dropIfExists('comments');Schema::dropIfExists('user_article_likes');Schema::table('users',function(Blueprint $t){$t->dropConstrainedForeignId('referred_by');$t->dropColumn(['is_admin','is_suspended','community_accepted_at','community_version','invite_code']);});}
};
