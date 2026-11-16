<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Services\NewsPreferences;
class NewsPreferencesController extends Controller {
 public static function rules():array {return ['personalisation'=>'sometimes|boolean','age_group'=>['nullable',Rule::in(NewsPreferences::AGES)],'place'=>'nullable|string|max:100','interests'=>'sometimes|array|max:8','interests.*'=>['string',Rule::in(array_keys(NewsPreferences::TOPICS))],'geography'=>'nullable|in:mixed,netherlands,world'];}
 public static function save(Request $r,\App\Models\User $u):void {
  $v=$r->validate(self::rules());$enabled=$r->boolean('personalisation');
  $u->forceFill(['news_preferences'=>$enabled?['enabled'=>true,'age_group'=>$v['age_group']??null,'place'=>trim($v['place']??''),'interests'=>array_values(array_unique($v['interests']??[])),'geography'=>$v['geography']??'mixed']:['enabled'=>false],'news_preferences_answered_at'=>now()])->save();
 }
 public function update(Request $r){self::save($r,$r->user());return back()->with('status',app()->getLocale()==='en'?'News preferences saved.':'Nieuwsvoorkeuren opgeslagen.');}
}
