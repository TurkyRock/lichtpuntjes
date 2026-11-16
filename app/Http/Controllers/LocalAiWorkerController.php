<?php
namespace App\Http\Controllers;
use App\Services\LocalAi;
use Illuminate\Http\Request;
class LocalAiWorkerController extends Controller {
 public function claim(LocalAi $ai){return response()->json(['job'=>$ai->claim()]);}
 public function heartbeat(Request $r,int $id,LocalAi $ai){$v=$r->validate(['lease'=>'required|string|size:64']);return response()->json($ai->heartbeat($id,$v['lease']));}
 public function complete(Request $r,int $id,LocalAi $ai){
  $v=$r->validate(['lease'=>'required|string|size:64']);
  // This is authenticated machine JSON, not a browser form. Laravel's global form
  // normalisers convert "" to null and trim text recursively. Preserve the original
  // model output; the job's strict schema still validates every field before saving.
  try{$body=json_decode($r->getContent(),true,512,JSON_THROW_ON_ERROR);}catch(\JsonException $e){abort(422,'Ongeldig JSON-antwoord.');}
  abort_unless(is_array($body)&&array_key_exists('result',$body)&&is_array($body['result']),422,'Resultaat ontbreekt of heeft een ongeldig formaat.');
  return response()->json($ai->complete($id,$v['lease'],$body['result']));
 }
 public function fail(Request $r,int $id,LocalAi $ai){$v=$r->validate(['lease'=>'required|string|size:64']);return response()->json($ai->fail($id,$v['lease']));}
}
