<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {Schema::table('articles',fn(Blueprint $table)=>$table->timestamp('content_updated_at')->nullable());}
 public function down():void {Schema::table('articles',fn(Blueprint $table)=>$table->dropColumn('content_updated_at'));}
};
