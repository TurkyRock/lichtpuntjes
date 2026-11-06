@extends('community.layout')
@push('page-styles')<link rel="stylesheet" href="/css/story-studio.css?v={{ filemtime(public_path('css/story-studio.css')) }}"><script src="/js/story-studio.js?v={{ filemtime(public_path('js/story-studio.js')) }}" defer></script>@endpush
@section('content')
@php($en=app()->getLocale()==='en')
<div class="story-studio">
<h1>{{ $en?'Revise my story':'Mijn verhaal aanpassen' }}</h1>
<a class="story-edit-back" href="/mijn-lichtpuntjes?lang={{ app()->getLocale() }}">← {{ $en?'My stories':'Mijn verhalen' }}</a>
<div class="story-edit-paper"><section class="story-writing-paper">
<span class="story-clothespin" aria-hidden="true"></span>
@if($submission)<p class="story-review-note">{{ $submission->moderator_note?:($submission->reason[$en?'en':'nl']??'') }}</p>@endif
@if(auth()->user()->hasVerifiedEmail()&&auth()->user()->community_accepted_at)
@include('community.story-form')
@else
<p>{{ $en?'Confirm your email address and accept the community rules in your account before submitting.':'Bevestig je e-mailadres en accepteer de huisregels in je account voordat je een verhaal instuurt.' }}</p><a class="story-cut-button" href="/profiel?lang={{ app()->getLocale() }}">{{ $en?'Go to my account':'Naar mijn account' }}</a>
@endif
</section></div></div>
@endsection
