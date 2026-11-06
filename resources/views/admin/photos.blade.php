@extends('community.layout')
@section('content')
<h1>Een gezicht bij de naam</h1><p class="admin-intro">Controleer echte, veilige portretfoto’s. AI beoordeelt de foto; het verifieert geen identiteit.</p>
<nav class="admin-tabs" aria-label="Fotofilters">@foreach(['review'=>'Te beoordelen','approved'=>'Goedgekeurd','rejected'=>'Afgewezen'] as $key=>$label)<a class="button {{ $filter===$key?'':'quiet' }}" href="?status={{ $key }}" @if($filter===$key)aria-current="page"@endif>{{ $label }}</a>@endforeach</nav>
<section class="panel">@forelse($photos as $photo)@include('admin.photo-review')@empty<h2>Deze fotobak is leeg</h2><p>Hier verschijnen foto’s zodra ze bij dit filter passen.</p>@endforelse{{ $photos->links() }}</section>
@endsection
