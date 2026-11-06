@extends('community.layout')
@section('content')
@php($en=app()->getLocale()==='en')
<section class="support-paper support-thanks"><span class="support-sun" aria-hidden="true">☀</span>
@if($problem)<h1>{{ $en?'We are checking your contribution':'We controleren je bijdrage' }}</h1><p>{{ $en?'We have not confirmed everything yet. You do not need to pay again. Refresh this page in a moment.':'We hebben nog niet alles kunnen bevestigen. Je hoeft niet opnieuw te betalen. Vernieuw deze pagina over een moment.' }}</p>
@elseif($pledge->status==='paid')<h1>{{ $en?'Thank you for your sunshine!':'Dank je wel voor jouw beetje zon!' }}</h1><p>{{ $en?'Your contribution has been received. Thank you for helping Lichtpuntjes shine.':'Je bijdrage is ontvangen. Bedankt dat je Lichtpuntjes helpt stralen.' }}</p>@if($pledge->frequency==='monthly' && !$pledge->stopped_at)<p>{{ $en?'You can manage your monthly support in your account.':'Je beheert je maandelijkse bijdrage in je account.' }}</p>@endif
@elseif(in_array($pledge->status,['failed','canceled','expired']))<h1>{{ $en?'Your payment did not complete':'Je betaling is niet afgerond' }}</h1><p>{{ $en?'No problem. Thank you for being here.':'Geen probleem. Fijn dat je er bent.' }}</p>
@else<h1>{{ $en?'Your contribution is being processed':'Je bijdrage wordt verwerkt' }}</h1><p>{{ $en?'We are waiting for payment confirmation. Please check this page again in a moment.':'We wachten nog op de betaalbevestiging. Bekijk deze pagina zo nog een keer.' }}</p>@endif
<a class="support-button" href="/">{{ $en?'Back to the bright spots':'Terug naar de lichtpuntjes' }}</a> @auth<a href="/mijn-bijdragen">{{ $en?'My contributions':'Mijn bijdragen' }}</a>@endauth</section>
@if($showThanks??false)
<dialog id="supportThanksDialog" class="support-thanks-dialog" aria-labelledby="supportThanksTitle" aria-describedby="supportThanksText"><button type="button" class="support-thanks-close" data-close-support-thanks aria-label="{{ $en?'Close thank-you message':'Bedankje sluiten' }}">×</button><span class="support-thanks-paper-sun" aria-hidden="true"></span><p class="support-kicker">{{ $en?'A little note from me':'Een briefje van mij' }}</p><h2 id="supportThanksTitle">{{ $en?'How lovely. Thank you!':'Wat ontzettend fijn, dank je wel!' }}</h2><p id="supportThanksText">{{ $en?'Your contribution helps me keep Lichtpuntje free and without advertising. I really appreciate that. Thank you for helping this place shine.':'Met jouw bijdrage help je mij Lichtpuntje gratis en reclamevrij te houden. Dat waardeer ik enorm. Bedankt dat je deze plek helpt stralen.' }}</p><p class="support-popup-signature">Erik</p><a class="support-button" href="/{{ $en?'en':'' }}">{{ $en?'Back to the bright spots':'Terug naar de lichtpuntjes' }} ☀</a></dialog>
@endif
@endsection
@if($showThanks??false)
@push('page-styles')<script src="/js/support-thanks.js?v={{ filemtime(public_path('js/support-thanks.js')) }}" defer></script>@endpush
@endif
