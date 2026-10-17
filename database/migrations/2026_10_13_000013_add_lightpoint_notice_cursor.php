<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{Schema,DB};
return new class extends Migration {
 public function up():void {
  Schema::table('users',fn(Blueprint $t)=>$t->unsignedBigInteger('lightpoint_notice_seen_id')->default(0));
  // Existing activity is already known. Only new rewards produce notifications.
  DB::table('users')->update(['lightpoint_notice_seen_id'=>(int)DB::table('lightpoint_events')->max('id')]);
 }
 public function down():void {Schema::table('users',fn(Blueprint $t)=>$t->dropColumn('lightpoint_notice_seen_id'));}
};
