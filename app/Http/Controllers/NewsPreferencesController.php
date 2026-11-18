<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Services\NewsPreferences;
class NewsPreferencesController extends Controller {
 public static function rules():array {return ['personalisation'=>'sometimes|boolean','learn_from_reading'=>'sometimes|boolean','age_group'=>['nullable',Rule::in(NewsPreferences::AGES)],'place'=>'nullable|string|max:100','region'=>'nullable|string|max:100','interests'=>'sometimes|array|max:8','interests.*'=>['string',Rule::in(array_keys(NewsPreferences::TOPICS))],'geography'=>'nullable|in:mixed,netherlands,world'];}
 public static function save(Request $r,\App\Models\User $u):void {
  $v=$r->validate(self::rules());$enabled=$r->boolean('personalisation');
  app(NewsPreferences::class)->save($u,$enabled?['enabled'=>true,'learn_from_reading'=>$r->boolean('learn_from_reading'),'age_group'=>$v['age_group']??null,'place'=>trim($v['place']??''),'region'=>trim($v['region']??''),'interests'=>array_values(array_unique($v['interests']??[])),'geography'=>$v['geography']??'mixed']:['enabled'=>false]);
 }
 public function update(Request $r){self::save($r,$r->user());return back()->with('status',app()->getLocale()==='en'?'News preferences saved.':'Nieuwsvoorkeuren opgeslagen.');}
}
