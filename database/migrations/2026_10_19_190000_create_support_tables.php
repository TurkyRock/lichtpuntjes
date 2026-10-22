<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {
  Schema::create('support_pledges',function(Blueprint $t){
   $t->uuid('id')->primary();$t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
   $t->unsignedInteger('cents');$t->string('frequency',10);$t->string('mode',8);
   $t->string('status')->default('pending');$t->string('customer_id')->nullable();
   $t->string('first_payment_id')->nullable()->unique();$t->text('checkout_url')->nullable();
   $t->string('subscription_id')->nullable()->unique();$t->string('subscription_status')->nullable();
   $t->timestamp('consented_at')->nullable();$t->timestamp('stopped_at')->nullable();$t->timestamps();
  });
  Schema::create('support_payments',function(Blueprint $t){
   $t->id();$t->foreignUuid('pledge_id')->constrained('support_pledges')->cascadeOnDelete();
   $t->string('provider_id')->unique();$t->unsignedInteger('cents');$t->string('status');
   $t->unsignedInteger('refunded_cents')->default(0);$t->unsignedInteger('charged_back_cents')->default(0);
   $t->timestamp('paid_at')->nullable();$t->timestamps();
  });
 }
 public function down():void {Schema::dropIfExists('support_payments');Schema::dropIfExists('support_pledges');}
};
