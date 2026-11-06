<div class="account-keepsakes">
@include('community.activity-calendar')
<section class="panel account-latest-moments"><h2>{{ $profileEn?'Moments to keep':'Om te bewaren' }}</h2>
@forelse($recentMoments->take(2) as $keptMoment)<article class="account-kept-note"><p class="small">{{ $keptMoment->moment_date->format('d-m-Y') }} · {{ $keptMoment->visibility==='private'?($profileEn?'Only for me':'Alleen voor mijzelf'):($profileEn?'Shared':'Gedeeld') }}</p><p class="comments">{{ $keptMoment->bodyFor() }}</p></article>@empty<p>{{ $profileEn?'A coffee in the sunshine, a kind word or a little peace. Your first moment can be something small.':'Een kop koffie in de zon, een lief woord of even rust. Je eerste momentje mag iets kleins zijn.' }}</p>@endforelse
<a href="/mijn-momentjes?lang={{ app()->getLocale() }}">{{ $profileEn?'All my moments →':'Al mijn momentjes →' }}</a>
</section>
</div>
<section class="panel account-recent-activity"><h2>{{ $profileEn?'A little light left behind':'Een beetje licht achtergelaten' }}</h2>
@forelse($events->take(3) as $recentEvent)<div class="paper-activity-row"><span aria-hidden="true">{{ $recentEvent->active?'✦':'·' }}</span><div>{{ $profileEn ? strtr($recentEvent->label,['Fijn dat je er weer bent!'=>'Good to see you again!','Een eigen verhaal gepubliceerd'=>'Your own story is published','Een fijn moment om te bewaren'=>'A moment to keep','Je gaf wat liefde met een hartje'=>'You shared a little love','Een vriendelijke reactie'=>'A kind comment','Een momentje om te bewaren'=>'A moment to keep','Je dagelijkse dosis goed nieuws'=>'Your daily dose of good news']) : $recentEvent->label }}<small>{{ $recentEvent->period }}</small></div><strong>{{ $recentEvent->active?'+'.$recentEvent->points:($profileEn?'withdrawn':'ingetrokken') }}</strong></div>@empty<p>{{ $profileEn?'Read something lovely, leave a kind comment or save your first moment. Every little bit counts.':'Lees iets moois, reageer vriendelijk of bewaar je eerste momentje. Alle kleine beetjes tellen.' }}</p>@endforelse
<p><a href="/profiel?lang={{ app()->getLocale() }}&amp;section=points">{{ $profileEn?'View all my points →':'Bekijk mijn puntenoverzicht →' }}</a></p>
</section>
