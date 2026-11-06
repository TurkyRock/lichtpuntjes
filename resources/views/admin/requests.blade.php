@extends('community.layout')
@section('content')
<h1>Vragen over gegevens</h1><p class="admin-intro">Persoonlijke aanvragen voor inzage, correctie of accountverwijdering. Deze horen bij gebruikersbeheer.</p>
<nav class="admin-tabs"><a class="button quiet" href="/beheer/gebruikers">Gebruikers</a><a class="button" href="/beheer/gebruikers?filter=privacy">Gegevensverzoeken</a></nav>
<section class="panel"><form class="admin-user-filters" method="get"><input type="hidden" name="filter" value="privacy"><label>Naam of e-mailadres<input name="q" value="{{ $search }}" maxlength="100"></label><label>Toon<select name="handled"><option value="open" @selected(request('handled','open')==='open')>Open verzoeken</option><option value="all" @selected(request('handled')==='all')>Alle verzoeken</option></select></label><button>Toon verzoeken</button></form></section>
<section class="panel">@forelse($requests as $item)<p><a href="/beheer/gebruikers/{{ $item->user_id }}?section=privacy">{{ $item->name??'Verwijderd account' }}</a> · {{ $item->email }}</p>@include('admin.request-item')@empty<h2>Geen open briefjes</h2><p>Geen gegevensverzoeken bij deze filters.</p>@endforelse{{ $requests->links() }}</section>
@endsection
