<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {
  Schema::table('articles',function(Blueprint $t){$t->string('origin')->default('news')->index();$t->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();});
  Schema::create('lightspot_submissions',function(Blueprint $t){
   $t->id();$t->foreignId('user_id')->constrained()->cascadeOnDelete();$t->foreignId('article_id')->nullable()->unique()->constrained()->nullOnDelete();
   $t->string('title',140);$t->text('body');$t->string('language',2);$t->string('category');$t->string('status')->default('pending')->index();
   $t->json('reason')->nullable();$t->decimal('confidence',4,3)->nullable();$t->json('translations')->nullable();$t->boolean('ai_error')->default(false);$t->text('processing_error')->nullable();
   $t->foreignId('moderated_by')->nullable()->constrained('users')->nullOnDelete();$t->text('moderator_note')->nullable();$t->timestamp('appealed_at')->nullable();$t->unsignedInteger('revision')->default(1);$t->timestamps();
  });
  Schema::create('lightspot_reviews',function(Blueprint $t){$t->id();$t->foreignId('submission_id')->constrained('lightspot_submissions')->cascadeOnDelete();$t->foreignId('moderator_id')->nullable()->constrained('users')->nullOnDelete();$t->string('decision');$t->text('note');$t->timestamps();});
  Schema::create('lightspot_reports',function(Blueprint $t){$t->id();$t->foreignId('submission_id')->constrained('lightspot_submissions')->cascadeOnDelete();$t->foreignId('user_id')->constrained()->cascadeOnDelete();$t->text('note');$t->boolean('resolved')->default(false);$t->timestamps();$t->unique(['submission_id','user_id']);});
 }
 public function down():void {Schema::dropIfExists('lightspot_reports');Schema::dropIfExists('lightspot_reviews');Schema::dropIfExists('lightspot_submissions');Schema::table('articles',function(Blueprint $t){$t->dropConstrainedForeignId('author_id');$t->dropColumn('origin');});}
};
