<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('positive_moments', function (Blueprint $table) {
            $table->timestamp('image_accepted_at')->nullable();
            $table->string('image_state', 16)->nullable()->index();
            $table->string('image_style', 16)->nullable();
            $table->string('image_subject', 24)->nullable();
            $table->string('image_path')->nullable();
            $table->unsignedInteger('image_revision')->nullable();
        });
    }
    public function down(): void {
        Schema::table('positive_moments', fn (Blueprint $table) => $table->dropColumn([
            'image_accepted_at', 'image_state', 'image_style', 'image_subject', 'image_path', 'image_revision',
        ]));
    }
};
