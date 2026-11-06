<?php
namespace App\Http\Controllers;
use App\Rules\FriendlyDisplayName;
use Illuminate\Http\Request;
class ProfileNameController extends Controller
{
    public function update(Request $request)
    {
        $request->merge(['name'=>trim((string)$request->input('name'))]);
        $validated=$request->validate(['name'=>FriendlyDisplayName::rules()]);
        $request->user()->update(['name'=>$validated['name']]);
        return redirect('/privacy/verzoeken?lang='.app()->getLocale())->with('status',app()->getLocale()==='en'?'Your username has been updated.':'Je gebruikersnaam is aangepast.');
    }
}
