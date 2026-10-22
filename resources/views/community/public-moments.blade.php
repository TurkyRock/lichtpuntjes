@extends('community.layout')
@section('content')
@php($en=app()->getLocale()==='en')
<h1>{{ $en?'A smile from each other':'Een glimlach van elkaar' }}</h1><p>{{ $en?'Little moments shared by the community. Come and borrow a smile.':'Kleine momenten, gedeeld door de community. Kom gerust een glimlach lenen.' }}</p>
<div class="row"><a class="button" href="/lichtpuntje/delen?lang={{ app()->getLocale() }}">{{ $en?'Share a bright spot':'Deel een lichtpuntje' }}</a><a href="/?lang={{ app()->getLocale() }}">{{ $en?'Read good news':'Lees goed nieuws' }}</a></div>
@forelse($moments as $moment)<article class="panel" id="moment-{{ $moment->id }}"><div class="row">@if($moment->user->avatarUrl())<img class="member-photo" src="{{ $moment->user->avatarUrl() }}" width="44" height="44" alt="">@endif<strong>{{ $moment->user->name }}</strong><small>{{ $moment->moment_date->format('d-m-Y') }}</small></div><p class="comments">{{ $moment->body }}</p>@include('shared.moment-heart')</article>@empty<section class="panel"><p>{{ $en?'The first moments are on their way.':'De eerste momentjes komen eraan.' }}</p></section>@endforelse{{ $moments->links() }}
@endsection
