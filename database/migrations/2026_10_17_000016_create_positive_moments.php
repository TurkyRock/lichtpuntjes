<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {Schema::create('positive_moments',function(Blueprint $t){$t->id();$t->foreignId('user_id')->constrained()->cascadeOnDelete();$t->text('body');$t->string('visibility',10)->default('private')->index();$t->string('status',20)->default('pending')->index();$t->text('reason')->nullable();$t->string('ai_model')->nullable();$t->date('moment_date')->index();$t->unsignedInteger('revision')->default(1);$t->foreignId('moderated_by')->nullable()->constrained('users')->nullOnDelete();$t->timestamp('ai_accepted_at');$t->timestamps();});Schema::create('moment_hearts',function(Blueprint $t){$t->id();$t->foreignId('moment_id')->constrained('positive_moments')->cascadeOnDelete();$t->foreignId('user_id')->constrained()->cascadeOnDelete();$t->timestamp('created_at');$t->unique(['moment_id','user_id']);});}
 public function down():void {Schema::dropIfExists('moment_hearts');Schema::dropIfExists('positive_moments');}
};
