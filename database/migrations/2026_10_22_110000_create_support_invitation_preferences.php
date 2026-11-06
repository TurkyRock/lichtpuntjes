<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {Schema::create('support_invitation_preferences',function(Blueprint $t){$t->foreignId('user_id')->primary()->constrained()->cascadeOnDelete();$t->unsignedBigInteger('paused_until')->default(0);$t->unsignedBigInteger('last_shown_at')->default(0);$t->unsignedBigInteger('popup_shown_at')->default(0);});}
 public function down():void {Schema::dropIfExists('support_invitation_preferences');}
};
