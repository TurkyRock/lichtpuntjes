<?php
namespace App\Services;
class LocalAiSchema {
 public static function convert(array $s):array {
  $out=$s; if(isset($out['type']))$out['type']=strtolower($out['type']);
  if(isset($out['properties']))foreach($out['properties'] as $k=>$v)$out['properties'][$k]=self::convert($v);
  if(($out['type']??null)==='object')$out['additionalProperties']=false;
  if(isset($out['items']))$out['items']=self::convert($out['items']);
  unset($out['propertyOrdering']);
  if(!empty($out['nullable'])){$out['type']=[$out['type']??'string','null'];unset($out['nullable']);}
  return $out;
 }
 public static function valid(mixed $v,array $s,int $depth=0):bool {
  if($depth>20)return false;if($v===null)return !empty($s['nullable'])||in_array('null',(array)($s['type']??[]),true);
  $type=strtolower(is_array($s['type']??null)?($s['type'][0]??'object'):($s['type']??'object'));
  if(!match($type){'object'=>is_array($v)&&($v===[]||!array_is_list($v)),'array'=>is_array($v)&&array_is_list($v),'string'=>is_string($v),'boolean'=>is_bool($v),'integer'=>is_int($v),'number'=>(is_int($v)||is_float($v))&&is_finite((float)$v),default=>false})return false;
  if(isset($s['enum'])&&!in_array($v,$s['enum'],true))return false;
  if($type==='object'){
   foreach($s['required']??[] as $k)if(!array_key_exists($k,$v))return false;
   foreach($v as $k=>$item)if(!isset($s['properties'][$k])||!self::valid($item,$s['properties'][$k],$depth+1))return false;
  }elseif($type==='array'){
   if(count($v)>100)return false;foreach($v as $item)if(!self::valid($item,$s['items']??[],$depth+1))return false;
  }
  return true;
 }
}
