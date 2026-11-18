<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('user_news_signals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 8);
            $table->string('category', 20);
            $table->timestamp('created_at');
            $table->unique(['user_id','article_id','kind']);
            $table->index(['user_id','created_at']);
            $table->index('created_at');
        });
    }
    public function down(): void { Schema::dropIfExists('user_news_signals'); }
};
