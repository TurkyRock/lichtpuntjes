<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {
  Schema::create('social_publications',function(Blueprint $t){$t->id();$t->date('edition_date');$t->string('kind',16);$t->unsignedBigInteger('article_id')->nullable()->unique();$t->string('status',20)->default('ready');$t->json('snapshot')->nullable();$t->json('captions')->nullable();$t->unsignedInteger('selection_rank')->nullable();$t->string('selection_note')->nullable();$t->timestamp('due_at');$t->timestamp('first_shared_at')->nullable()->index();$t->timestamps();$t->unique(['edition_date','kind']);});
  Schema::create('social_deliveries',function(Blueprint $t){$t->id();$t->foreignId('publication_id')->constrained('social_publications')->cascadeOnDelete();$t->string('channel',20);$t->string('status',24)->default('not_connected');$t->text('external_url')->nullable();$t->string('source',16)->nullable();$t->timestamp('published_at')->nullable();$t->timestamp('checked_at')->nullable();$t->foreignId('checked_by')->nullable()->constrained('users')->nullOnDelete();$t->timestamps();$t->unique(['publication_id','channel']);});
  Schema::create('social_delivery_events',function(Blueprint $t){$t->id();$t->foreignId('delivery_id')->constrained('social_deliveries')->cascadeOnDelete();$t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();$t->string('action',32);$t->text('url')->nullable();$t->timestamp('created_at');});
 }
 public function down():void {Schema::dropIfExists('social_delivery_events');Schema::dropIfExists('social_deliveries');Schema::dropIfExists('social_publications');}
};
