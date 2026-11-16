@extends('community.layout')
@section('content')
@php($locale=app()->getLocale()==='en'?'en':'nl')
<h1>{{ $locale==='en'?'Privacy at Jouw Lichtpuntjes':'Privacy bij Jouw Lichtpuntjes' }}</h1><p>{{ $locale==='en'?'Your stories deserve care, and so do your data.':'Je verhalen verdienen zorg, en je gegevens ook.' }}</p>
@foreach(config('privacy-content') as $section)<section class="panel"><h2>{{ $section[$locale][0] }}</h2>@foreach(explode("\n",$section[$locale][1]) as $paragraph)<p>{{ $paragraph }}</p>@endforeach</section>@endforeach
<p><a href="mailto:{{ config('privacy.email') }}">{{ config('privacy.email') }}</a> · <a href="/privacy/verzoeken?lang={{ $locale }}">{{ $locale==='en'?'My details':'Mijn gegevens' }}</a> · <a href="/cookies?lang={{ $locale }}">Cookies</a> · <a href="/contact?lang={{ $locale }}">Contact</a></p>
<p><a href="https://www.autoriteitpersoonsgegevens.nl/themas/basis-avg/privacyrechten-avg" rel="noopener noreferrer" target="_blank">Autoriteit Persoonsgegevens</a> · <a href="https://policies.google.com/privacy" rel="noopener noreferrer" target="_blank">Google</a> · <a href="https://www.mollie.com/legal/privacy" rel="noopener noreferrer" target="_blank">Mollie</a></p>
@endsection
