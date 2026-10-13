<?php
namespace App\Support;
use Illuminate\Http\Request;
class CookieChoice {
 const VERSION='v2';
 const MINUTES=259200;
 public static function value(Request $r):?string {
  $parts=explode('|',(string)$r->cookie('lichtpunt_consent'));
  if(count($parts)!==3||$parts[0]!==self::VERSION||!in_array($parts[1],['yes','no'],true)||!ctype_digit($parts[2]))return null;
  $time=(int)$parts[2];return $time<=now()->timestamp&&$time>now()->timestamp-self::MINUTES*60?$parts[1]:null;
 }
 public static function allowed(Request $r):bool{return self::value($r)==='yes';}
}
