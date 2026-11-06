<?php
namespace App\Rules;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;
class FriendlyDisplayName implements ValidationRule
{
    public static function rules(): array
    {
        return ['required','string','min:2','max:40','regex:/^[\pL\pN _.-]+$/u',new self];
    }
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $name = mb_strtolower(Str::ascii((string) $value));
        $name = strtr($name,['0'=>'o','1'=>'i','3'=>'e','4'=>'a','5'=>'s','7'=>'t']);
        $words = preg_split('/[^a-z]+/', $name, -1, PREG_SPLIT_NO_EMPTY);
        $compact = preg_replace('/[^a-z]/','',$name);
        $blocked = ['kut','klootzak','klootviool','lul','sukkel','idioot','imbeciel','eikel','trut','hoer','teringlijer','kankerlijer','kankerhoer','kankermongool','mongool','tyfuslijer','achterlijk','rotmens','loser','fuck','fucker','fuckyou','motherfucker','shit','shithead','asshole','bitch','bastard','dickhead','cunt','retard','nigger','nazi'];
        // Exact words avoid blocking ordinary names that merely contain a fragment.
        $offensive = in_array($compact,$blocked,true) || count(array_intersect($words,$blocked))>0;
        foreach(['ikhaat','haatjullie','haatiedereen','iedereenisdom','jijbentdom','ihateyou','hateeveryone','killall','fuck','kankerhoer','kankerlijer','kankermongool'] as $phrase) {
            $offensive = $offensive || str_contains($compact,$phrase);
        }
        if ($offensive) $fail(app()->getLocale()==='en'?'Choose a friendly username without insults, hate or profanity.':'Kies een vriendelijke gebruikersnaam zonder beledigingen, haat of scheldwoorden.');
    }
}
