<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {Schema::create('account_email_changes',function(Blueprint $t){$t->uuid('id')->primary();$t->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();$t->string('email',254);$t->unsignedBigInteger('security_version');$t->timestamp('expires_at');});}
 public function down():void {Schema::dropIfExists('account_email_changes');}
};
