<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {
  Schema::table('articles',function(Blueprint $t){$t->unsignedTinyInteger('daily_score')->nullable()->index();$t->json('daily_reason')->nullable();$t->timestamp('daily_scored_at')->nullable();$t->unsignedTinyInteger('daily_score_attempts')->default(0);});
  Schema::table('article_reads',fn(Blueprint $t)=>$t->index(['read_date','article_id'],'article_reads_weekly_index'));
  Schema::table('user_article_likes',fn(Blueprint $t)=>$t->index(['created_at','article_id'],'user_article_likes_weekly_index'));
  Schema::create('daily_spotlights',function(Blueprint $t){$t->id();$t->date('edition_date')->unique();$t->foreignId('article_id')->nullable()->constrained()->nullOnDelete();$t->string('category',40)->nullable();$t->boolean('is_daily')->default(true);$t->boolean('manual')->default(false);$t->timestamps();});
 }
 public function down():void {Schema::table('article_reads',fn(Blueprint $t)=>$t->dropIndex('article_reads_weekly_index'));Schema::table('user_article_likes',fn(Blueprint $t)=>$t->dropIndex('user_article_likes_weekly_index'));Schema::dropIfExists('daily_spotlights');Schema::table('articles',fn(Blueprint $t)=>$t->dropColumn(['daily_score','daily_reason','daily_scored_at','daily_score_attempts']));}
};
