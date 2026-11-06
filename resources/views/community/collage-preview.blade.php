@extends('community.layout')
@section('title','Knipselproef — Jouw Lichtpuntje')
@section('content')
<h1>{{ app()->getLocale()==='en'?'A little paper, a new perspective':'Een beetje papier, een frisse blik' }}</h1><p>{{ app()->getLocale()==='en'?'Three symbolic examples of the new cut-paper image style. These are mood images, not news photographs.':'Drie symbolische voorbeelden van de nieuwe knipselstijl voor artikelbeelden. Dit zijn sfeerbeelden, geen nieuwsfoto’s.' }}</p>
@foreach([
 ['waterlelie','Een reuzenwaterlelie ontdekt','A giant waterlily discovered'],
 ['orang-oetan','Geredde orang-oetans in beschermd bos','Rescued orangutans in protected forest'],
 ['onderzoek','Een frisse blik op onderzoek','A fresh perspective on research']
] as [$image,$titleNl,$titleEn])
<article class="panel"><img src="/images/collage-proef-{{ $image }}.png" width="1536" height="1024" style="display:block;width:100%;height:auto;max-height:330px;object-fit:contain" alt="{{ app()->getLocale()==='en'?$titleEn:$titleNl }}"><h2>{{ app()->getLocale()==='en'?$titleEn:$titleNl }}</h2></article>
@endforeach
<p>{{ app()->getLocale()==='en'?'To try this style on up to three current articles, run:':'Om de stijl bij maximaal drie huidige artikelen op de homepage te proberen:' }} <code>php artisan news:collage-trial</code></p><p>{{ app()->getLocale()==='en'?'Restore the previous images with:':'De eerdere afbeeldingen zet je terug met:' }} <code>php artisan news:collage-trial --restore</code></p>
@endsection
