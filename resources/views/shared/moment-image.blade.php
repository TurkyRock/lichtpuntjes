@if($moment->hasMomentImage())
<figure class="moment-small-image moment-small-image--{{ $momentImageContext??'card' }} {{ $moment->image_style==='sketch'?'is-sketch':'is-photo' }}"><img src="{{ $moment->momentImageUrl() }}" width="384" height="384" loading="lazy" decoding="async" alt="{{ app()->getLocale()==='en'?'AI mood image for this moment':'AI-sfeerbeeld bij dit momentje' }}"></figure>
@endif
