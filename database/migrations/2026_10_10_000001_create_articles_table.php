<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->string('fingerprint', 64)->unique();
            $table->text('source_title');
            $table->text('source_description')->nullable();
            $table->string('source_name');
            $table->text('source_url');
            $table->timestamp('published_at')->nullable();
            $table->string('category')->nullable();
            $table->string('status')->default('pending')->index();
            $table->text('reason')->nullable();
            $table->json('nl')->nullable();
            $table->json('en')->nullable();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('articles'); }
};
