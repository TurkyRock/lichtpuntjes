@extends('community.layout')
@section('content')
@php
 $en=app()->getLocale()==='en';
 $euro=fn($cents)=>'€ '.number_format($cents/100,$cents%100===0?0:2,$en?'.':',',$en?',':'.');
 $hasPlans=$monthlyPledges->isNotEmpty();
 $showThanks=$hasPaid||$monthlyCents>0;
 $testVisible=$mode==='test'&&($history->total()||$attempts->total()||$hasPlans);
 $paymentLabels=$en?['paid'=>'Paid','pending'=>'Pending','open'=>'Awaiting payment','failed'=>'Failed','canceled'=>'Cancelled','expired'=>'Expired','authorized'=>'Awaiting settlement']:['paid'=>'Betaald','pending'=>'In behandeling','open'=>'Wacht op betaling','failed'=>'Mislukt','canceled'=>'Geannuleerd','expired'=>'Verlopen','authorized'=>'Wacht op verwerking'];
@endphp
<link rel="stylesheet" href="/css/contributions-post.css?v={{ filemtime(public_path('css/contributions-post.css')) }}">
<h1>{{ $en?'My contributions':'Mijn bijdragen' }}</h1>
<div class="contributions-post">
@if($testVisible)<p class="post-test-note" role="status">{{ $en?'Test environment: these are test payments. Live contributions are not included here.':'Testomgeving: dit zijn testbetalingen. Livebijdragen worden hier niet meegeteld.' }}</p>@endif
@if($showThanks)
 <div class="post-donor-hero" data-contribution-state="supported">
  <div class="post-thank-envelope"><img src="/images/contributions-thank-envelope-v1.png" width="400" height="400" alt=""><div class="post-letter-writing"><h2>{{ $en?'Thank you!':'Dank je wel!' }}</h2><p>{{ $en?'You help me keep Jouw Lichtpuntje free and without advertising.':'Jij helpt mij Jouw Lichtpuntje gratis en zonder reclame te houden.' }}</p><p class="post-letter-greeting">{{ $en?'With love,':'Lieve groet,' }}</p><span class="post-ink-signature">Erik</span></div></div>
  <div class="post-donor-summary">
   <section class="post-total-paper"><div><h2>{{ $en?'Total contributed':'Totaal gesteund' }}</h2><strong class="post-total-amount">{{ $euro($netCents) }}</strong><p>{{ $paidCount }} {{ $en?($paidCount===1?'paid contribution':'paid contributions'):($paidCount===1?'betaalde bijdrage':'betaalde bijdragen') }}</p></div>
    @if($monthlyCents>0)<div class="post-monthly-stamp"><img src="/images/account-paper-sun.svg" width="45" height="45" alt=""><strong>{{ $euro($monthlyCents) }}</strong><span>{{ $en?'per month active':'per maand actief' }}</span></div>@endif
   </section>
   @if($adjustedCents>0)<p class="post-adjustment-note">{{ $en?'Refunds and chargebacks have been deducted from your total.':'Terugbetalingen en terugboekingen zijn van je totaal afgetrokken.' }}</p>@endif
   <p class="post-purpose">{{ $en?'With your support I can keep Jouw Lichtpuntje free and without advertising. You help with hosting, news processing, AI images and further development of the website.':'Met jouw steun kan ik Jouw Lichtpuntje gratis en zonder reclame houden. Je helpt mee met de hosting, nieuwsverwerking, AI-beelden en de verdere ontwikkeling van de website.' }}</p>
  </div>
 </div>
@elseif($hasPlans||$hasPending)
 <section class="post-paper post-pending-intro" data-contribution-state="awaiting"><h2>{{ $en?'A little sunshine is on its way':'Er is een lichtpuntje onderweg' }}</h2><p>{{ $en?'Your contribution is not fully confirmed yet. Once a payment is confirmed, it appears in your overview.':'Je bijdrage is nog niet helemaal bevestigd. Zodra een betaling is bevestigd, verschijnt deze in je overzicht.' }}</p>@if($hasPlans)<p>{{ $en?'You can manage your monthly request below.':'Je kunt je maandelijkse aanvraag hieronder beheren.' }}</p>@endif</section>
@else
 <section class="post-invite-card" data-contribution-state="empty">
  <div class="post-invite-heading"><h2>{{ $en?'A small gesture, a big thank you':'Een klein gebaar, een groot dankjewel' }}</h2><span class="post-sun-stamp" aria-hidden="true"><img src="/images/account-paper-sun.svg" width="57" height="57" alt=""></span></div>
  <div class="post-coffee-row"><p class="post-coffee-joke">{{ $en?'The sunshine is free. Hosting, sadly, still sends a bill. 😉':'De zon schijnt gratis. De hosting krijgt helaas wel een rekening. 😉' }}</p><img src="/images/contributions-coffee-v1.png" width="230" height="230" alt=""></div>
  <p>{{ $en?'With your contribution I can keep Jouw Lichtpuntje free and without advertising. You help with hosting, news processing, AI images and further development.':'Met jouw bijdrage kan ik Jouw Lichtpuntje gratis en zonder reclame houden. Je helpt mee met de hosting, nieuwsverwerking, AI-beelden en de verdere ontwikkeling.' }}</p>
  <p>{{ $en?'You are welcome with or without a contribution.':'Je bent ook zonder bijdrage welkom.' }}</p>
  <div class="post-donation-choices"><a class="button post-cut-button post-cut-button--yellow" data-support-choice="once" href="/steun?frequency=once&amp;lang={{ $en?'en':'nl' }}">☕ {{ $en?'Support once':'Eenmalig steunen' }}</a><a class="button post-cut-button post-cut-button--sage" data-support-choice="monthly" href="/steun?frequency=monthly&amp;lang={{ $en?'en':'nl' }}">♡ {{ $en?'Support monthly':'Maandelijks steunen' }}</a></div>
  <div class="post-invite-signoff"><p>{{ $en?'Thank you for reading along, enjoying along and spreading little joys!':'Dank je wel voor het meelezen, meegenieten en het verspreiden van lichtpuntjes!' }}</p><span class="post-ink-signature">Erik</span></div>
 </section>
@endif
@if($hasPlans)
 <section class="post-monthly-management" aria-labelledby="monthlyManageTitle"><h2 id="monthlyManageTitle">{{ $en?'Manage monthly support':'Maandelijkse bijdrage beheren' }}</h2>
 @foreach($monthlyPledges as $plan)
 @php
  $planLabel=$plan->subscription_status==='active'?($en?'Active':'Actief'):($plan->subscription_status==='suspended'?($en?'Temporarily paused':'Tijdelijk gepauzeerd'):($en?'Awaiting confirmation':'Wacht op bevestiging'));
 @endphp
 <div class="post-plan"><div class="post-plan-summary"><p><strong>{{ $euro($plan->cents) }} {{ $en?'per month':'per maand' }}</strong><br>{{ $planLabel }}</p><p>{{ $en?'You can stop at any time.':'Je kunt op ieder moment stoppen.' }}</p></div>
 <details class="post-manage-details" @if($errors->has('stop')) open @endif><summary class="post-cut-button post-cut-button--yellow">{{ $en?'Manage contribution':'Bijdrage beheren' }} <span aria-hidden="true">⌄</span></summary><form action="/mijn-bijdragen/{{ $plan->id }}/stop?lang={{ $en?'en':'nl' }}" method="post">@csrf<p>{{ $en?'A collection already in progress may still be processed. Your earlier contributions remain in this overview.':'Een al ingezette incasso kan nog worden verwerkt. Je eerdere bijdragen blijven in dit overzicht staan.' }}</p><label><input type="checkbox" name="confirm" value="1" required> {{ $en?'I want to stop this monthly contribution.':'Ik wil deze maandelijkse bijdrage stoppen.' }}</label><button class="post-cut-button post-cut-button--sage">{{ $en?'Stop monthly support':'Maandelijkse bijdrage stoppen' }}</button></form></details></div>
 @endforeach
 </section>
@endif
@if($history->total()>0)
 <section class="post-history" aria-labelledby="contributionHistoryTitle"><h2 id="contributionHistoryTitle">{{ $en?'Your contributions':'Jouw bijdragen' }}</h2><div class="post-history-paper"><div class="post-table-scroll"><table><thead><tr><th>{{ $en?'Date':'Datum' }}</th><th>{{ $en?'Amount':'Bedrag' }}</th><th>{{ $en?'Type':'Type' }}</th><th>{{ $en?'Status':'Status' }}</th></tr></thead><tbody>
 @foreach($history as $payment)
 @php
  $date=($payment->paid_at?:$payment->created_at)->copy()->timezone('Europe/Amsterdam')->locale($en?'en':'nl');
  $status=$paymentLabels[$payment->status]??($en?'Processing':'In verwerking');
  $refunded=(int)$payment->refunded_cents;$charged=(int)$payment->charged_back_cents;
  if($payment->status==='paid'&&!$payment->paid_at)$status=$en?'Awaiting confirmation':'Wacht op bevestiging';
  elseif($refunded+$charged>=(int)$payment->cents&&$refunded+$charged>0)$status=$charged>0?($en?'Charged back / refunded':'Teruggeboekt / terugbetaald'):($en?'Refunded':'Terugbetaald');
  elseif($refunded+$charged>0)$status=$en?'Partially refunded / charged back':'Deels terugbetaald / teruggeboekt';
 @endphp
 <tr><td><time datetime="{{ $date->toDateString() }}">{{ $date->translatedFormat('j F Y') }}</time></td><td><strong>{{ $euro($payment->cents) }}</strong>@if($refunded+$charged>0)<span class="post-returned">{{ $euro(min($payment->cents,$refunded+$charged)) }} {{ $en?'returned':'terug' }}</span>@endif</td><td>{{ $payment->pledge->frequency==='monthly'?($en?'Monthly':'Maandelijks'):($en?'Once':'Eenmalig') }}</td><td><span class="post-payment-status">{{ $status }}</span></td></tr>
 @endforeach
 </tbody></table></div></div><nav class="post-pagination" aria-label="{{ $en?'Browse contributions':'Bladeren door bijdragen' }}">@if($history->previousPageUrl())<a class="button post-cut-button post-cut-button--sage" href="{{ $history->previousPageUrl() }}">{{ $en?'Newer contributions':'Nieuwere bijdragen' }}</a>@endif @if($history->hasMorePages())<a class="button post-cut-button post-cut-button--yellow" href="{{ $history->nextPageUrl() }}">{{ $en?'Earlier contributions':'Eerdere bijdragen' }} →</a>@endif</nav></section>
@endif
@if($attempts->total()>0)
 <section class="post-attempts post-paper"><h2>{{ $en?'Payment requests':'Betaalverzoeken' }}</h2><p>{{ $en?'These requests have no confirmed payment and do not count towards your total.':'Bij deze aanvragen is nog geen betaling bevestigd. Ze tellen niet mee in je totaal.' }}</p>
 @foreach($attempts as $attempt)<p class="post-attempt-row"><time datetime="{{ $attempt->created_at->toDateString() }}">{{ $attempt->created_at->timezone('Europe/Amsterdam')->format('d-m-Y') }}</time><strong>{{ $euro($attempt->cents) }}</strong><span>{{ $paymentLabels[$attempt->status]??($en?'Awaiting confirmation':'Wacht op bevestiging') }}</span></p>@endforeach
 <nav class="post-pagination" aria-label="{{ $en?'Browse payment requests':'Bladeren door betaalverzoeken' }}">@if($attempts->previousPageUrl())<a class="button post-cut-button post-cut-button--sage" href="{{ $attempts->previousPageUrl() }}">{{ $en?'Previous':'Vorige' }}</a>@endif @if($attempts->hasMorePages())<a class="button post-cut-button post-cut-button--sage" href="{{ $attempts->nextPageUrl() }}">{{ $en?'More requests':'Meer aanvragen' }}</a>@endif</nav>
 </section>
@endif
</div>
@endsection
