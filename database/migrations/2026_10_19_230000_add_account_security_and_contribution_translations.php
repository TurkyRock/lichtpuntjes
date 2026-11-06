<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('users', fn(Blueprint $t) => $t->unsignedInteger('security_version')->default(0));
        Schema::create('account_security_events', function(Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->string('kind', 40);
            $t->timestamp('created_at')->useCurrent();
        });
        foreach (['positive_moments', 'comments'] as $table) Schema::table($table, function(Blueprint $t) {
            $t->text('translations')->nullable();
            $t->string('source_language', 2)->nullable();
            $t->string('translation_hash', 64)->nullable();
            $t->string('translation_state', 15)->default('pending');
        });
    }
    public function down(): void {
        foreach (['positive_moments', 'comments'] as $table) Schema::table($table, fn(Blueprint $t) => $t->dropColumn(['translations','source_language','translation_hash','translation_state']));
        Schema::dropIfExists('account_security_events');
        Schema::table('users', fn(Blueprint $t) => $t->dropColumn('security_version'));
    }
};
