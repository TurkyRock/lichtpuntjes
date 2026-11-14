<?php
namespace App\Http\Controllers;
use App\Models\{LightspotSubmission,PositiveMoment};
use App\Services\ContributionProgress;
use Illuminate\Http\Request;
class ContributionProgressController extends Controller {
 public function show(Request $r,string $kind,int $id,ContributionProgress $progress){$class=$kind==='story'?LightspotSubmission::class:PositiveMoment::class;$record=$class::where('user_id',$r->user()->id)->findOrFail($id);return response()->json($progress->for($record,app()->getLocale()==='en'))->header('Cache-Control','private, no-store');}
}
