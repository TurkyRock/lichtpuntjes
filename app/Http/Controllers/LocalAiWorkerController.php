<?php
namespace App\Http\Controllers;
use App\Services\LocalAi;
use Illuminate\Http\Request;
class LocalAiWorkerController extends Controller {
 public function claim(LocalAi $ai){return response()->json(['job'=>$ai->claim()]);}
 public function heartbeat(Request $r,int $id,LocalAi $ai){$v=$r->validate(['lease'=>'required|string|size:64']);return response()->json($ai->heartbeat($id,$v['lease']));}
 public function complete(Request $r,int $id,LocalAi $ai){$v=$r->validate(['lease'=>'required|string|size:64','result'=>'required|array']);return response()->json($ai->complete($id,$v['lease'],$v['result']));}
 public function fail(Request $r,int $id,LocalAi $ai){$v=$r->validate(['lease'=>'required|string|size:64']);return response()->json($ai->fail($id,$v['lease']));}
}
