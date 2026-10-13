<?php
namespace App\Services;
class BadgeCatalog {
    public static function stages(?string $locale = null): array {
        $locale = ($locale ?? app()->getLocale()) === 'en' ? 'en' : 'nl';
        $rows = [
            ['points'=>0, 'id'=>'sprout', 'names'=>['nl'=>'Zonnespriet','en'=>'Sun sprout'], 'descriptions'=>['nl'=>'Klein begin. Groot gezellig potentieel.','en'=>'A small start. Big good-company potential.']],
            ['points'=>1, 'id'=>'switch', 'names'=>['nl'=>'Lichtknopje','en'=>'Light switch'], 'descriptions'=>['nl'=>'Klik. Je eerste lichtpunt is binnen.','en'=>'Click. Your first bright point is in.']],
            ['points'=>5, 'id'=>'smile', 'names'=>['nl'=>'Glimlachstarter','en'=>'Smile starter'], 'descriptions'=>['nl'=>'De mondhoeken gaan alvast omhoog.','en'=>'The corners of your mouth are warming up.']],
            ['points'=>10, 'id'=>'frog', 'names'=>['nl'=>'Opkikker','en'=>'Mood lifter'], 'descriptions'=>['nl'=>'Geen prins nodig. Een beetje goed nieuws wel.','en'=>'No prince needed. A little good news will do.']],
            ['points'=>15, 'id'=>'beacon', 'names'=>['nl'=>'Zonnestraal','en'=>'Sunbeam'], 'descriptions'=>['nl'=>'Je geeft het licht inmiddels door.','en'=>'You are passing the light along.']],
            ['points'=>25, 'id'=>'post', 'names'=>['nl'=>'Goednieuwsbezorger','en'=>'Good news courier'], 'descriptions'=>['nl'=>'Deze post mag wél op de deurmat.','en'=>'Now this is mail worth opening.']],
            ['points'=>35, 'id'=>'record', 'names'=>['nl'=>'Sfeermaker','en'=>'Mood maker'], 'descriptions'=>['nl'=>'Jij zet de gezelligheid op repeat.','en'=>'You put good company on repeat.']],
            ['points'=>50, 'id'=>'cannon', 'names'=>['nl'=>'Complimentenkanon','en'=>'Compliment cannon'], 'descriptions'=>['nl'=>'Geladen met aardige woorden. Vuur maar af.','en'=>'Loaded with kind words. Fire away.']],
            ['points'=>75, 'id'=>'shoe', 'names'=>['nl'=>'Wandelend zonnetje','en'=>'Walking sunshine'], 'descriptions'=>['nl'=>'Zelfs je schoenen hebben een goed humeur.','en'=>'Even your shoes are in a good mood.']],
            ['points'=>100, 'id'=>'watering', 'names'=>['nl'=>'Geluksverspreider','en'=>'Good mood gardener'], 'descriptions'=>['nl'=>'Een scheutje aandacht. Kijk, het groeit.','en'=>'A splash of attention. Look, it is growing.']],
            ['points'=>150, 'id'=>'lamp', 'names'=>['nl'=>'Lichtpuntlegende','en'=>'Bright point legend'], 'descriptions'=>['nl'=>'Bij jou staat het leeslampje altijd aan.','en'=>'Your reading lamp is always on.']],
            ['points'=>200, 'id'=>'kettle', 'names'=>['nl'=>'Gezelligheidscentrale','en'=>'Good company HQ'], 'descriptions'=>['nl'=>'De ketel fluit. De sfeer zit goed.','en'=>'The kettle is whistling. The mood is good.']],
            ['points'=>300, 'id'=>'deckchair', 'names'=>['nl'=>'Menselijke zonnebank','en'=>'Human sunbed'], 'descriptions'=>['nl'=>'Je omgeving gaat vanzelf wat zonniger zitten.','en'=>'Everyone around you finds a sunnier seat.']],
            ['points'=>400, 'id'=>'tin', 'names'=>['nl'=>'Hoop in blik','en'=>'Canned hope'], 'descriptions'=>['nl'=>'Lang houdbaar. Meteen te delen.','en'=>'Long shelf life. Ready to share.']],
            ['points'=>600, 'id'=>'spotlight', 'names'=>['nl'=>'Schijnwerper','en'=>'Spotlight'], 'descriptions'=>['nl'=>'Jij zet mooie verhalen in het licht.','en'=>'You put good stories in the spotlight.']],
            ['points'=>1000, 'id'=>'trophy', 'names'=>['nl'=>'Erelichtpunt','en'=>'Honorary bright point'], 'descriptions'=>['nl'=>'Duizend punten. Geen speech nodig, hoor.','en'=>'A thousand points. No speech required.']],
        ];
        return array_map(fn($b) => $b + [
            'name'=>$b['names'][$locale], 'description'=>$b['descriptions'][$locale],
            'icon'=>'/images/badges/'.$b['id'].'.svg',
        ], $rows);
    }
    public static function homePreview(): array {
        return array_values(array_filter(self::stages(), fn($b) => in_array($b['id'], ['smile','beacon','lamp'], true)));
    }
}
