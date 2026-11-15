<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('users', function (Blueprint $table) {
            $table->json('news_preferences')->nullable();
            $table->timestamp('news_preferences_answered_at')->nullable();
        });
        Schema::table('articles', function (Blueprint $table) {
            $table->json('interest_topics')->nullable();
            $table->string('story_country', 2)->nullable();
            $table->json('story_places')->nullable();
            $table->json('audience_groups')->nullable();
            $table->json('source_countries')->nullable();
            $table->string('source_language', 40)->nullable();
        });
    }
    public function down(): void {
        Schema::table('users', fn(Blueprint $table) => $table->dropColumn(['news_preferences','news_preferences_answered_at']));
        Schema::table('articles', fn(Blueprint $table) => $table->dropColumn(['interest_topics','story_country','story_places','audience_groups','source_countries','source_language']));
    }
};
