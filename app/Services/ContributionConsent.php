<?php
namespace App\Services;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class ContributionConsent {
 public const VERSION='2026-11-16';
 public static function validate(Request $r):void {
  $r->validate(['sensitive_content'=>'required|in:none,self','sensitive_consent'=>'accepted_if:sensitive_content,self']);
 }
 public static function record(Request $r,string $kind,int $id,int $revision=1,string $visibility='public'):void {
  // The same contribution may be edited to remove sensitive data; consent records expire with it.
  DB::table('contribution_consents')->where('kind',$kind)->where('target_id',$id)->delete();
  if($r->input('sensitive_content')==='self')DB::table('contribution_consents')->insert(['user_id'=>$r->user()->id,'kind'=>$kind,'target_id'=>$id,'revision'=>$revision,'visibility'=>$visibility,'version'=>$kind==='moment'?'2026-11-17':self::VERSION,'created_at'=>now()]);
 }
}
