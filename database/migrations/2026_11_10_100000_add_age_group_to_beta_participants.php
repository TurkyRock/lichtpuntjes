<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {Schema::table('beta_participants',function(Blueprint $table){$table->string('age_group',16)->nullable()->index();});}
 public function down():void {Schema::table('beta_participants',function(Blueprint $table){$table->dropColumn('age_group');});}
};
