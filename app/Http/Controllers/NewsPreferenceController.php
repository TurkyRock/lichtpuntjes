<?php
namespace App\Http\Controllers;
use App\Services\NewsPreferences;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NewsPreferenceController extends Controller
{
    public function edit(Request $request) {
        return view('community.news-preferences',['preferences'=>$request->user()->news_preferences ?? [],'welcome'=>$request->boolean('welcome')]);
    }
    public function update(Request $request) {
        $v=$request->validate([
            'enabled'=>'sometimes|boolean', 'interests'=>'sometimes|array|max:8',
            'interests.*'=>['string','distinct',Rule::in(array_keys(NewsPreferences::TOPICS))],
            'less_topics'=>'sometimes|array|max:8', 'less_topics.*'=>['string','distinct',Rule::in(array_keys(NewsPreferences::TOPICS))],
            'geography'=>['required',Rule::in(['netherlands','mixed','world'])],
            'place'=>['nullable','string','max:100','regex:/^[\pL\pM\pN\s.\x{2019}\x{0027}()\-]+$/u'],
            'age_group'=>['nullable',Rule::in(NewsPreferences::AGES)], 'welcome'=>'sometimes|boolean',
        ]);
        $v['enabled']=$request->boolean('enabled');
        $v['interests']=$v['interests']??[];$v['less_topics']=$v['less_topics']??[];
        // Explicit "less" wins over an accidentally checked conflicting interest.
        $v['interests']=array_values(array_diff($v['interests'],$v['less_topics']));
        $v['place']=trim(preg_replace('/\s+/u',' ', $v['place']??''));
        unset($v['welcome']);
        if(!$v['enabled'])$v=['enabled'=>false];
        $request->user()->forceFill(['news_preferences'=>$v,'news_preferences_answered_at'=>now()])->save();
        $en=app()->getLocale()==='en';
        return redirect($request->boolean('welcome')?($en?'/en':'/').'?view='.($v['enabled']?'for-you':'all'):'/mijn-interesses?lang='.app()->getLocale())->with('status',$en?'Your preferences have been saved.':'Je voorkeuren zijn opgeslagen.');
    }
    public function skip(Request $request) {
        $request->user()->forceFill(['news_preferences_answered_at'=>now()])->save();
        return redirect('/profiel?lang='.app()->getLocale());
    }
    public function destroy(Request $request) {
        $request->user()->forceFill(['news_preferences'=>null,'news_preferences_answered_at'=>now()])->save();
        return redirect('/mijn-interesses?lang='.app()->getLocale())->with('status',app()->getLocale()==='en'?'Your preferences have been removed.':'Je voorkeuren zijn verwijderd. Je ziet weer alle lichtpuntjes.');
    }
    public function less(Request $request) {
        $v=$request->validate(['topic'=>['required',Rule::in(array_keys(NewsPreferences::TOPICS))]]);
        $user=$request->user();
        // Lock so a double click or two tabs cannot overwrite each other's feedback.
        \Illuminate\Support\Facades\DB::transaction(function()use($user,$v){
            $u=\App\Models\User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $p=$u->news_preferences??[];
            $p['less_topics']=array_values(array_unique([...($p['less_topics']??[]),$v['topic']]));
            $p['interests']=array_values(array_diff($p['interests']??[],[$v['topic']]));
            $p['enabled']=true;
            $u->forceFill(['news_preferences'=>$p,'news_preferences_answered_at'=>now()])->save();
        });
        return response()->json(['saved'=>true])->header('Cache-Control','private, no-store');
    }
}
