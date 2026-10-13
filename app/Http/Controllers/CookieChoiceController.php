<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use App\Support\CookieChoice;
class CookieChoiceController extends Controller {
 public function store(Request $r){
  $v=$r->validate(['statistics'=>'required|boolean']);
  $value=CookieChoice::VERSION.'|'.($v['statistics']?'yes':'no').'|'.now()->timestamp;
  $response=response()->json(['saved'=>true,'statistics'=>(bool)$v['statistics']])->header('Cache-Control','private, no-store');
  $response->withCookie(cookie('lichtpunt_consent',$value,CookieChoice::MINUTES,'/',null,$r->isSecure(),true,false,'lax'));
  if(!$v['statistics']){
   Cookie::queue(Cookie::forget('lichtpunt_visitor','/'));
   foreach(array_keys($r->cookies->all()) as $name){
    if(preg_match('/^_ga(?:_[A-Za-z0-9_]+)?$/D',$name))$response->withCookie(new \Symfony\Component\HttpFoundation\Cookie($name,'',1,'/',null,$r->isSecure(),false,false,'lax'));
   }
  }
  return $response;
 }
}
