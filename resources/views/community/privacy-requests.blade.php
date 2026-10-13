@extends('community.layout')
@section('content')
@php($en=app()->getLocale()==='en')
<h1>{{ $en?'Your data, your choice':'Jouw gegevens, jouw keuze' }}</h1>
<p>{{ $en?'Requests are private and reviewed by the administrator. Requesting deletion does not immediately delete your account.':'Verzoeken zijn privé en worden door de beheerder behandeld. Een verwijderverzoek wist je account niet direct.' }}</p>
@if(!$admin)
<section class="panel"><form method="post" action="/privacy/verzoeken">@csrf
<label for="kind">{{ $en?'What would you like?':'Wat wil je regelen?' }}</label><select id="kind" name="kind">@foreach(['access'=>['Inzage of een kopie','Access or a copy'],'correction'=>['Gegevens aanpassen','Correct data'],'deletion'=>['Account en gegevens verwijderen','Delete account and data'],'objection'=>['Bezwaar of beperking','Object or restrict processing'],'other'=>['Andere privacyvraag','Other privacy question']] as $key=>$label)<option value="{{ $key }}" @selected(old('kind')===$key)>{{ $label[$en?1:0] }}</option>@endforeach</select>
<label for="message">{{ $en?'Explanation':'Toelichting' }}</label><textarea id="message" name="message" required minlength="5" maxlength="2000">{{ old('message') }}</textarea><p class="small">{{ $en?'Do not send passwords or a copy of your ID. For deletion, mention which public contributions your request covers.':'Stuur geen wachtwoorden of kopie van je identiteitsbewijs. Geef bij verwijderen aan op welke openbare bijdragen je verzoek betrekking heeft.' }}</p><button>{{ $en?'Submit request':'Verzoek versturen' }}</button></form></section>
@endif
@foreach($requests as $item)<section class="panel"><strong>#{{ $item->id }} · {{ $item->kind }} · {{ $item->resolved_at?($en?'Handled':'Afgehandeld'):($en?'Open':'Open') }}</strong><p class="small">{{ $item->created_at }} @if($admin) · {{ $item->name ?? '' }} · {{ $item->email ?? '' }} @endif</p><p style="white-space:pre-wrap">{{ $item->message }}</p>
@if($admin&&!$item->resolved_at)<form method="post" action="/beheer/privacy/{{ $item->id }}">@csrf<label><input type="checkbox" required name="handled" value="1"> Ik heb het verzoek daadwerkelijk behandeld en de gebruiker geïnformeerd.</label><p class="small">Deze knop verwijdert geen gegevens. Voer inzage, correctie of verwijdering eerst zorgvuldig uit, inclusief openbare bijdragen en relevante kopieën.</p><button>Markeer als afgehandeld</button></form>@endif</section>@endforeach
{{ $requests->links() }}
@endsection
