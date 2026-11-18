<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up():void {
        Schema::table('articles',fn(Blueprint $t)=>$t->json('source_attribution')->nullable());
        Schema::create('news_feed_entries',function(Blueprint $t){
            $t->id();$t->string('source_key',50);$t->char('url_hash',64)->unique();$t->text('url');
            $t->string('title',1000);$t->text('body');$t->string('author',250)->nullable();
            $t->string('category',20);$t->json('regions');$t->json('places');
            $t->timestamp('published_at');$t->string('status',30)->default('queued');
            $t->foreignId('article_id')->nullable()->constrained()->nullOnDelete();
            $t->timestamp('selected_at')->nullable();$t->timestamps();
            $t->index(['status','category']);$t->index(['source_key','selected_at']);
        });
        Schema::create('news_feed_checks',function(Blueprint $t){
            $t->string('source_key',50)->primary();$t->timestamp('checked_at')->nullable();
            $t->timestamp('succeeded_at')->nullable();$t->string('status',40);$t->string('message',250)->nullable();$t->unsignedInteger('new_entries')->default(0);
        });
        Schema::create('article_regions',function(Blueprint $t){
            $t->foreignId('article_id')->constrained()->cascadeOnDelete();$t->string('region',30);
            $t->primary(['article_id','region']);$t->index('region');
        });
    }
    public function down():void {
        Schema::dropIfExists('article_regions');Schema::dropIfExists('news_feed_checks');Schema::dropIfExists('news_feed_entries');
        Schema::table('articles',fn(Blueprint $t)=>$t->dropColumn('source_attribution'));
    }
};
