@php
 $en=app()->getLocale()==='en';
 $canParticipate=$user->hasVerifiedEmail() && $user->community_accepted_at && !$user->is_suspended;
@endphp
<link rel="stylesheet" href="/css/comment-board.css?v={{ filemtime(public_path('css/comment-board.css')) }}">
<section class="conversation-board" id="myCommentsTitle" aria-label="{{ $en?'Your conversations':'Jouw gesprekken' }}">
 <div class="conversation-summary">
  <p class="conversation-note"><span aria-hidden="true">♥</span><span><b>{{ $commentTotals['hearts'] }}</b> {{ $en?($commentTotals['hearts']===1?'heart':'hearts'):($commentTotals['hearts']===1?'hartje':'hartjes') }}<br>{{ $en?'on your comments':'op jouw reacties' }}</span></p>
  <p class="conversation-note conversation-note--blue"><svg aria-hidden="true" width="34" height="34" viewBox="0 0 32 32"><path d="M4 4 28 3 29 23 14 24 6 30 6 24 3 23Z" fill="currentColor"/></svg><span><b>{{ $commentTotals['replies'] }}</b> {{ $en?($commentTotals['replies']===1?'reply':'replies'):($commentTotals['replies']===1?'antwoord':'antwoorden') }}<br>{{ $en?'to your comments':'op jouw reacties' }}</span></p>
  <p class="conversation-private">{{ $en?'Only you can see feedback on your comments.':'Alleen jij ziet de feedback op jouw reacties.' }}</p>
 </div>
 @forelse($comments as $root)
 @php
  $available=$root->article && \App\Models\Article::visible()->whereKey($root->article_id)->exists();
  $rootVisible=$root->status==='approved' && !$root->user->is_suspended;
  $title=$root->article?->{$en?'en':'nl'}['title']??$root->article?->source_title??($en?'Article no longer available':'Artikel niet meer beschikbaar');
  $photo=$available?($root->article->photo['url']??''):'';
  $photo=preg_match('~^/news-images/[a-f0-9]{64}\\.(?:jpg|png)$~',$photo)?$photo:null;
 @endphp
 <article class="conversation-card {{ $available?'':'conversation-card--archived' }}" id="conversation-{{ $root->id }}">
  <header class="conversation-heading">
   <div class="conversation-polaroid" aria-hidden="true">@if($photo)<img src="{{ $photo }}" alt="" loading="lazy">@else<span>☀</span>@endif</div>
   <div class="conversation-title"><h2>{{ $title }}</h2>@if(!$available)<p>{{ $en?'This article is no longer on the site. Your comments are kept here.':'Dit artikel staat niet meer op de site. Je reacties blijven hier bewaard.' }}</p>@else<p>{{ $en?'A conversation with a little sunshine':'Een gesprek met een beetje zon' }}</p>@endif</div>
   @if($available)<a class="button" href="/?story={{ $root->article_id }}&amp;lang={{ $en?'en':'nl' }}">{{ $en?'Read article ↗':'Naar artikel ↗' }}</a>@else<span class="conversation-unavailable">{{ $en?'Article unavailable':'Artikel niet meer beschikbaar' }}</span>@endif
  </header>
  <div class="conversation-entries">
   @include('community.comment-thread-entry',['comment'=>$root])
   @if($root->threadReplies->isNotEmpty())<div class="conversation-replies">
    @foreach($root->threadReplies as $comment)@include('community.comment-thread-entry',['comment'=>$comment])@endforeach
   </div>@endif
  </div>
  @if($available && $rootVisible && $canParticipate)
  <form class="conversation-compose" method="post" action="/comments/{{ $root->id }}/reply?lang={{ $en?'en':'nl' }}">
   @csrf
   <label class="conversation-compose-label" for="reply-{{ $root->id }}">{{ $en?'Your friendly reply':'Jouw vriendelijke antwoord' }}</label>
   <textarea id="reply-{{ $root->id }}" name="body" rows="2" minlength="3" maxlength="1000" required placeholder="{{ $en?'Write a kind reply…':'Schrijf een vriendelijk antwoord…' }}">{{ old('reply_root')==$root->id?old('body'):'' }}</textarea><input type="hidden" name="reply_root" value="{{ $root->id }}">
   <button>{{ $en?'Reply':'Antwoorden' }}</button>
  </form>
  @endif
 </article>
 @empty
 <div class="conversation-empty">
  <img src="/images/account-paper-sun.svg" width="80" height="80" alt="">
  <h2>{{ $en?'A kind word starts something lovely':'Een aardig woord maakt iets moois los' }}</h2>
  <p>{{ $en?'Leave a comment on a story that makes you smile. You will find your conversations, hearts and replies here.':'Reageer op een verhaal dat je blij maakt. Hier vind je daarna je gesprekken, hartjes en antwoorden bij elkaar.' }}</p>
  <a class="button" href="/?lang={{ $en?'en':'nl' }}">{{ $en?'Discover the stories':'Ontdek de lichtpuntjes' }}</a>
 </div>
 @endforelse
 <nav class="pagination" aria-label="{{ $en?'Conversations pagination':'Bladeren door gesprekken' }}">@if($comments->previousPageUrl())<a class="button quiet" href="{{ $comments->previousPageUrl() }}">{{ $en?'Previous':'Vorige' }}</a>@endif @if($comments->hasMorePages())<a class="button quiet" href="{{ $comments->nextPageUrl() }}">{{ $en?'Next':'Volgende' }}</a>@endif</nav>
</section>
