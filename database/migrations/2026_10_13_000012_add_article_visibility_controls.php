<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {Schema::table('articles',function(Blueprint $table){$table->timestamp('hidden_at')->nullable()->index();$table->foreignId('hidden_by')->nullable()->constrained('users')->nullOnDelete();});}
 public function down():void {Schema::table('articles',function(Blueprint $table){$table->dropConstrainedForeignId('hidden_by');$table->dropColumn('hidden_at');});}
};
