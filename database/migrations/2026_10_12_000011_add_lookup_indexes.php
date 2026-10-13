<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {
  Schema::table('articles',function(Blueprint $t){$t->index(['status','published_at','id'],'articles_feed_date');$t->index(['status','views_count','published_at'],'articles_feed_reads');$t->index(['status','likes_count','published_at'],'articles_feed_likes');$t->index(['status','category','published_at'],'articles_feed_category');});
  Schema::table('comments',function(Blueprint $t){$t->index(['article_id','status','id'],'comments_public_thread');$t->index(['user_id','created_at'],'comments_account_date');});
  Schema::table('comment_reviews',fn(Blueprint $t)=>$t->index(['comment_id','created_at'],'comment_reviews_date'));
  Schema::table('privacy_requests',fn(Blueprint $t)=>$t->index(['user_id','id'],'privacy_owner'));
 }
 public function down():void {
  Schema::table('articles',function(Blueprint $t){foreach(['articles_feed_date','articles_feed_reads','articles_feed_likes','articles_feed_category'] as $name)$t->dropIndex($name);});
  Schema::table('comments',function(Blueprint $t){$t->dropIndex('comments_public_thread');$t->dropIndex('comments_account_date');});
  Schema::table('comment_reviews',fn(Blueprint $t)=>$t->dropIndex('comment_reviews_date'));
  Schema::table('privacy_requests',fn(Blueprint $t)=>$t->dropIndex('privacy_owner'));
 }
};
