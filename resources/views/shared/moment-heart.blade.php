@php($heartEn=app()->getLocale()==='en')
@auth
@if(auth()->id()!==$moment->user_id)
<form method="post" action="/momentjes/{{ $moment->id }}/hart" class="moment-heart" data-moment-heart data-moment-id="{{ $moment->id }}">@csrf<input type="hidden" name="liked" value="{{ in_array($moment->id,$likedMomentIds??[])?0:1 }}"><button type="submit" class="quiet" aria-pressed="{{ in_array($moment->id,$likedMomentIds??[])?'true':'false' }}" aria-label="{{ in_array($moment->id,$likedMomentIds??[])?($heartEn?'Remove heart':'Hartje intrekken'):($heartEn?'Give a heart':'Geef een hartje') }}">{{ in_array($moment->id,$likedMomentIds??[])?'♥':'♡' }} {{ $moment->hearts_count??0 }}</button><span class="moment-heart-status" role="status" aria-live="polite"></span></form>
@else<span class="small">♡ {{ $moment->hearts_count??0 }}</span>@endif
@else<a class="moment-heart-link" href="/inloggen?lang={{ $heartEn?'en':'nl' }}" aria-label="{{ $heartEn?'Sign in to give a heart':'Log in om een hartje te geven' }}">♡ {{ $moment->hearts_count??0 }}</a>@endauth
