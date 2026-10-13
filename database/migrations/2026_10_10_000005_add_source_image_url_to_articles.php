<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up():void { Schema::table('articles',fn(Blueprint $t)=>$t->text('source_image_url')->nullable()); }
    public function down():void { Schema::table('articles',fn(Blueprint $t)=>$t->dropColumn('source_image_url')); }
};
