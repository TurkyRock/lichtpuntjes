@extends('community.layout')
@section('content')
<h1>{{ app()->getLocale()==='en'?'News archive':'Nieuwsarchief' }}</h1>
<p>{{ app()->getLocale()==='en'?'Older bright spots remain available here.':'Lichtpuntjes ouder dan 90 dagen blijven hier leesbaar.' }}</p>
<p><a href="/">{{ app()->getLocale()==='en'?'Back to recent news':'Terug naar het laatste nieuws' }}</a></p>
@forelse($articles as $a)<article class="panel"><p>{{ $a->published_at?->format('d-m-Y') }}</p><h2><a href="{{ \App\Support\StorySeo::path($a,app()->getLocale()) }}">{{ $a->{app()->getLocale()}['title']??$a->source_title }}</a></h2><p>{{ $a->{app()->getLocale()}['teaser']??'' }}</p></article>@empty<p>{{ app()->getLocale()==='en'?'The archive is still empty.':'Het archief is nog leeg.' }}</p>@endforelse
{{ $articles->links() }}
@endsection
