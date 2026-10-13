<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('articles',function(Blueprint $t){
            $t->unsignedBigInteger('views_count')->default(0)->index();
            $t->unsignedBigInteger('likes_count')->default(0)->index();
        });
        Schema::create('article_reads',function(Blueprint $t){
            $t->id();$t->foreignId('article_id')->constrained()->cascadeOnDelete();
            $t->char('visitor_hash',64);$t->date('read_date');
            $t->unique(['article_id','visitor_hash','read_date']);
        });
        Schema::create('article_likes',function(Blueprint $t){
            $t->id();$t->foreignId('article_id')->constrained()->cascadeOnDelete();
            $t->char('visitor_hash',64);$t->timestamp('created_at');
            $t->unique(['article_id','visitor_hash']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('article_likes');Schema::dropIfExists('article_reads');
        Schema::table('articles',fn(Blueprint $t)=>$t->dropColumn(['views_count','likes_count']));
    }
};
