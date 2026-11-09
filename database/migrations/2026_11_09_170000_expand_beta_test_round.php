<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
return new class extends Migration {
 public function up():void {DB::table('beta_participants')->whereNotNull('completed_at')->update(['completed_at'=>null]);}
 public function down():void {/* Existing answers and open rounds remain unchanged. */}
};
