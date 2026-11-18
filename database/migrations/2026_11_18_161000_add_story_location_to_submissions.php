<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('lightspot_submissions', function (Blueprint $table) {
            $table->string('story_place',100)->nullable();
            $table->string('story_region',100)->nullable();
        });
    }
    public function down(): void {
        Schema::table('lightspot_submissions', fn(Blueprint $table)=>$table->dropColumn(['story_place','story_region']));
    }
};
