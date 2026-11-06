@extends('community.layout')
@section('content')
<h1>Kleine gelukjes, grote glimlach</h1><p class="admin-intro">Beheer de openbare momentjes. Privémomentjes worden hier nooit getoond.</p>
<nav class="admin-tabs" aria-label="Momentfilters">@foreach(['review'=>'Te beoordelen','approved'=>'Geplaatst','rejected'=>'Afgewezen'] as $key=>$label)<a class="button {{ $filter===$key?'':'quiet' }}" href="?status={{ $key }}" @if($filter===$key)aria-current="page"@endif>{{ $label }}</a>@endforeach</nav>
<form class="admin-search" method="get"><input type="hidden" name="status" value="{{ $filter }}"><label for="moment-author">Zoek op gebruikersnaam</label><input id="moment-author" name="q" maxlength="100" value="{{ request('q') }}"><button>Zoeken</button></form>
<section class="panel">@forelse($moments as $moment)@include('admin.moment-review')@empty<h2>Helemaal bij</h2><p>Geen openbare momentjes bij dit filter.</p>@endforelse{{ $moments->links() }}</section>
@endsection
