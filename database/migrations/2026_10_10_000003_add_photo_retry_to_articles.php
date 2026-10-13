<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('articles',fn(Blueprint $t)=>$t->timestamp('photo_next_attempt_at')->nullable()->index());
    }
    public function down(): void {
        Schema::table('articles',fn(Blueprint $t)=>$t->dropColumn('photo_next_attempt_at'));
    }
};
