<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up():void {
        Schema::table('support_payments',function(Blueprint $table){
            $table->timestamp('thanks_requested_at')->nullable();
            $table->timestamp('thanks_attempted_at')->nullable();
            $table->timestamp('thanks_sent_at')->nullable();
            $table->unsignedTinyInteger('thanks_attempts')->default(0);
        });
    }
    public function down():void {
        Schema::table('support_payments',fn(Blueprint $table)=>$table->dropColumn(['thanks_requested_at','thanks_attempted_at','thanks_sent_at','thanks_attempts']));
    }
};
