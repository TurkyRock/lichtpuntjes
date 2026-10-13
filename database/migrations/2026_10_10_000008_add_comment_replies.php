<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {Schema::table('comments',function(Blueprint $t){$t->foreignId('parent_id')->nullable()->constrained('comments')->nullOnDelete();});}
 public function down():void {Schema::table('comments',function(Blueprint $t){$t->dropConstrainedForeignId('parent_id');});}
};
