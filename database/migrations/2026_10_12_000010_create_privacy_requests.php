<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {Schema::create('privacy_requests',function(Blueprint $t){$t->id();$t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();$t->string('kind',30);$t->text('message');$t->timestamp('resolved_at')->nullable();$t->timestamps();});}
 public function down():void {Schema::dropIfExists('privacy_requests');}
};
