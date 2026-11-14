<?php
namespace App\Http\Controllers;
use App\Services\LaptopImages;
use Illuminate\Http\Request;
class LaptopImageController extends Controller {
 public function claim(Request $r,LaptopImages $images){$v=$r->validate(['worker_id'=>'required|string|regex:/^[a-zA-Z0-9_-]{1,80}$/']);return response()->json(['job'=>$images->claim($v['worker_id'])]);}
 public function heartbeat(Request $r,int $id,LaptopImages $images){$v=$r->validate(['lease'=>'required|string|size:64']);return response()->json($images->renew($id,$v['lease']));}
 public function complete(Request $r,int $id,LaptopImages $images){$v=$r->validate(['lease'=>'required|string|size:64','image'=>'required|file|mimetypes:image/png,image/jpeg|max:12288']);return response()->json($images->complete($id,$v['lease'],file_get_contents($r->file('image')->getRealPath())));}
 public function fail(Request $r,int $id,LaptopImages $images){$v=$r->validate(['lease'=>'required|string|size:64','code'=>'required|in:render_error,workflow_error,timeout,upload_error']);$images->fail($id,$v['lease'],$v['code']);return response()->json(['saved'=>true]);}
}
