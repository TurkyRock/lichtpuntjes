<?php
namespace App\Services;
class BadgeCatalog {
    public static function stages(?string $locale = null): array {
        $locale = ($locale ?? app()->getLocale()) === 'en' ? 'en' : 'nl';
        $rows = [
            ['points'=>0, 'id'=>'sprout', 'names'=>['nl'=>'Zonnespriet','en'=>'Sun sprout'], 'descriptions'=>['nl'=>'Klein begin. Groot gezellig potentieel.','en'=>'A small start. Big good-company potential.']],
            ['points'=>1, 'id'=>'switch', 'names'=>['nl'=>'Lichtknopje','en'=>'Light switch'], 'descriptions'=>['nl'=>'Klik. Je eerste lichtpunt is binnen.','en'=>'Click. Your first bright point is in.']],
            ['points'=>5, 'id'=>'smile', 'names'=>['nl'=>'Glimlachstarter','en'=>'Smile starter'], 'descriptions'=>['nl'=>'De mondhoeken gaan alvast omhoog.','en'=>'The corners of your mouth are warming up.']],
            ['points'=>20, 'id'=>'frog', 'names'=>['nl'=>'Opkikker','en'=>'Mood lifter'], 'descriptions'=>['nl'=>'Geen prins nodig. Een beetje goed nieuws wel.','en'=>'No prince needed. A little good news will do.']],
            ['points'=>50, 'id'=>'beacon', 'names'=>['nl'=>'Zonnestraal','en'=>'Sunbeam'], 'descriptions'=>['nl'=>'Je geeft het licht inmiddels door.','en'=>'You are passing the light along.']],
            ['points'=>100, 'id'=>'post', 'names'=>['nl'=>'Goednieuwsbezorger','en'=>'Good news courier'], 'descriptions'=>['nl'=>'Deze post mag wél op de deurmat.','en'=>'Now this is mail worth opening.']],
            ['points'=>175, 'id'=>'record', 'names'=>['nl'=>'Sfeermaker','en'=>'Mood maker'], 'descriptions'=>['nl'=>'Jij zet de gezelligheid op repeat.','en'=>'You put good company on repeat.']],
            ['points'=>275, 'id'=>'cannon', 'names'=>['nl'=>'Complimentenkanon','en'=>'Compliment cannon'], 'descriptions'=>['nl'=>'Geladen met aardige woorden. Vuur maar af.','en'=>'Loaded with kind words. Fire away.']],
            ['points'=>400, 'id'=>'shoe', 'names'=>['nl'=>'Wandelend zonnetje','en'=>'Walking sunshine'], 'descriptions'=>['nl'=>'Zelfs je schoenen hebben een goed humeur.','en'=>'Even your shoes are in a good mood.']],
            ['points'=>600, 'id'=>'watering', 'names'=>['nl'=>'Geluksverspreider','en'=>'Good mood gardener'], 'descriptions'=>['nl'=>'Een scheutje aandacht. Kijk, het groeit.','en'=>'A splash of attention. Look, it is growing.']],
            ['points'=>850, 'id'=>'lamp', 'names'=>['nl'=>'Lichtpuntlegende','en'=>'Bright point legend'], 'descriptions'=>['nl'=>'Bij jou staat het leeslampje altijd aan.','en'=>'Your reading lamp is always on.']],
            ['points'=>1200, 'id'=>'kettle', 'names'=>['nl'=>'Gezelligheidscentrale','en'=>'Good company HQ'], 'descriptions'=>['nl'=>'De ketel fluit. De sfeer zit goed.','en'=>'The kettle is whistling. The mood is good.']],
            ['points'=>1600, 'id'=>'deckchair', 'names'=>['nl'=>'Menselijke zonnebank','en'=>'Human sunbed'], 'descriptions'=>['nl'=>'Je omgeving gaat vanzelf wat zonniger zitten.','en'=>'Everyone around you finds a sunnier seat.']],
            ['points'=>2100, 'id'=>'tin', 'names'=>['nl'=>'Hoop in blik','en'=>'Canned hope'], 'descriptions'=>['nl'=>'Lang houdbaar. Meteen te delen.','en'=>'Long shelf life. Ready to share.']],
            ['points'=>2700, 'id'=>'spotlight', 'names'=>['nl'=>'Schijnwerper','en'=>'Spotlight'], 'descriptions'=>['nl'=>'Jij zet mooie verhalen in het licht.','en'=>'You put good stories in the spotlight.']],
            ['points'=>3500, 'id'=>'trophy', 'names'=>['nl'=>'Erelichtpunt','en'=>'Honorary bright point'], 'descriptions'=>['nl'=>'Een eretitel. Een speech mag, maar hoeft niet.','en'=>'An honorary title. No speech required.']],
            ['points'=>4500, 'id'=>'balloon', 'names'=>['nl'=>'Goedgemutste luchtballon','en'=>'Good mood balloon'], 'descriptions'=>['nl'=>'Je tilt de sfeer nog een stukje hoger.','en'=>'You lift the mood a little higher.']],
            ['points'=>6000, 'id'=>'toast', 'names'=>['nl'=>'Ontbijtzonnetje','en'=>'Breakfast sunshine'], 'descriptions'=>['nl'=>'Zelfs de toast staat vrolijk op.','en'=>'Even the toast wakes up smiling.']],
            ['points'=>8000, 'id'=>'umbrella', 'names'=>['nl'=>'Buienbuiger','en'=>'Rain rearranger'], 'descriptions'=>['nl'=>'Een beetje regen? Jij maakt ruimte voor licht.','en'=>'A little rain? You make room for light.']],
            ['points'=>10500, 'id'=>'train', 'names'=>['nl'=>'Goednieuwsexpres','en'=>'Good news express'], 'descriptions'=>['nl'=>'Volgende halte: een fijn moment.','en'=>'Next stop: a lovely moment.']],
            ['points'=>14000, 'id'=>'windmill', 'names'=>['nl'=>'Vrolijkheidsmolen','en'=>'Good mood mill'], 'descriptions'=>['nl'=>'Draait op kleine gebaren. En een beetje wind.','en'=>'Powered by small kindnesses. And a little wind.']],
            ['points'=>18500, 'id'=>'rocket', 'names'=>['nl'=>'Glunderpiloot','en'=>'Glow pilot'], 'descriptions'=>['nl'=>'We hebben een glimlach op de radar.','en'=>'We have a smile on the radar.']],
            ['points'=>24000, 'id'=>'lighthouse', 'names'=>['nl'=>'Baken van gezelligheid','en'=>'Beacon of warmth'], 'descriptions'=>['nl'=>'Bij jou is altijd een lichtje te vinden.','en'=>'There is always a little light with you.']],
            ['points'=>32000, 'id'=>'orbit', 'names'=>['nl'=>'Zonnestelselbeheerder','en'=>'Solar system keeper'], 'descriptions'=>['nl'=>'Het hele stelsel draait een beetje vrolijker.','en'=>'The whole system turns a little more cheerfully.']],
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
