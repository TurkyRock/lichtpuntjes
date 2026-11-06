@php($pointsEn=app()->getLocale()==='en')
<section class="badge-points-guide" aria-labelledby="badgePointsTitle">
<h2 id="badgePointsTitle">{{ $pointsEn?'How to earn bright points':'Zo verdien je lichtpunten' }}</h2>
<div class="badge-earn-grid">
<div class="badge-earn-card"><h3>{{ $pointsEn?'Read good news':'Lees goed nieuws' }}</h3><p>{{ $pointsEn?'One point per article, once a day. Up to five a day.':'Eén punt per artikel, eenmaal per dag. Maximaal vijf per dag.' }}</p></div>
<div class="badge-earn-card"><h3>{{ $pointsEn?'Save a little moment':'Schrijf een momentje' }}</h3><p>{{ $pointsEn?'One point a day after approval. Private or shared: both count.':'Eén punt per dag na goedkeuring. Privé of gedeeld: allebei tellen mee.' }}</p></div>
<div class="badge-earn-card"><h3>{{ $pointsEn?'Leave a kind comment':'Reageer vriendelijk' }}</h3><p>{{ $pointsEn?'One point per approved comment, up to five a day.':'Eén punt per goedgekeurde reactie, maximaal vijf per dag.' }}</p></div>
<div class="badge-earn-card"><h3>{{ $pointsEn?'Share a bright spot':'Deel een lichtpuntje' }}</h3><p>{{ $pointsEn?'One point per article shared, up to three a day.':'Eén punt per gedeeld artikel, maximaal drie per dag.' }}</p></div>
<div class="badge-earn-card"><h3>{{ $pointsEn?'Give a little love':'Geef een hartje' }}</h3><p>{{ $pointsEn?'One point per article or public moment each day. Your own posts do not count.':'Eén punt per bericht of openbaar momentje per dag. Je eigen bijdragen tellen niet mee.' }}</p></div>
<div class="badge-earn-card"><h3>{{ $pointsEn?'Invite a friend':'Nodig iemand uit' }}</h3><p>{{ $pointsEn?'Five points when your friend verifies their email and posts a first approved comment.':'Vijf punten zodra je vriend zijn e-mail bevestigt en een eerste goedgekeurde reactie plaatst.' }}</p></div>
</div>
<details><summary>{{ $pointsEn?'A few useful details':'Nog even handig om te weten' }}</summary><p>{{ $pointsEn?'You need a verified, active account. Sharing counts once per article each day. Removing a heart and adding it again does not earn extra points. Friend invitations have a limit of five rewards per month. Badges follow your active points; moderation may change your total.':'Je hebt een bevestigd, actief account nodig. Delen telt eenmaal per artikel per dag. Een hartje weghalen en opnieuw geven levert geen extra punten op. Voor uitnodigingen geldt maximaal vijf beloningen per maand. Badges volgen je actieve lichtpunten; door moderatie kan je totaal veranderen.' }}</p></details>
<div class="badge-guide-footer"><a class="badge-rules-button" href="/huisregels?lang={{ app()->getLocale() }}">{{ $pointsEn?'Read the points rules':'Bekijk de puntenregels' }} →</a></div>
</section>
