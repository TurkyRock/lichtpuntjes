@php($en = app()->getLocale() === 'en')
<style>.my-comment{padding:20px 0;border-top:1px solid #dfe6dc}.my-comment:first-of-type{margin-top:18px}.my-comment-meta{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;font-size:12px;color:#536862}.my-comment .comments{background:#f6f8f2;border-radius:12px;padding:14px}.comment-feedback{border-left:3px solid #bc8500;padding:9px 14px;background:#fff6dd;border-radius:0 10px 10px 0;font-size:14px}.revise-comment{margin:15px 0}.revise-comment summary{font-weight:650;color:#00786e;cursor:pointer}.revise-comment form{padding:12px 0}.revise-comment textarea{min-height:110px}.comment-history-note{font-size:12px;color:#536862}</style>
<section class="panel" aria-labelledby="myCommentsTitle"><h2 id="myCommentsTitle">{{ $en ? 'Your latest comments' : 'Je laatste reacties' }}</h2><p class="intro">{{ $en ? 'See what was published, what is awaiting review and what you can improve. Feedback on your own comments is private.' : 'Hier zie je wat geplaatst is, wat nog op keuring wacht en wat je kunt verbeteren. De feedback op je eigen reacties blijft privé.' }}</p>
@forelse($comments as $comment)
<article class="my-comment">
@if($comment->parent_id)
<p class="small">{{ $en ? 'Reply to a comment' : 'Antwoord op een reactie' }}</p>
@endif
<div class="my-comment-meta"><strong>{{ $en ? ['approved'=>'Published','pending'=>'Awaiting review','doubt'=>'Awaiting a human review','rejected'=>'Not published'][$comment->status] : ['approved'=>'Geplaatst','pending'=>'Wacht op keuring','doubt'=>'Wacht op een menselijke blik','rejected'=>'Niet geplaatst'][$comment->status] }}</strong><time datetime="{{ $comment->created_at->toIso8601String() }}">{{ $comment->created_at->format('d-m-Y H:i') }}</time></div><a href="/?story={{ $comment->article_id }}&lang={{ $en ? 'en' : 'nl' }}">{{ $comment->article->{$en ? 'en' : 'nl'}['title'] ?? $comment->article->source_title }}</a><p class="comments">{{ $comment->body }}</p>
@if($comment->status === 'rejected')
<div class="comment-feedback"><strong>{{ $en ? 'Why it was not published' : 'Waarom niet geplaatst?' }}</strong><p>{{ $comment->moderator_note ?: $comment->ai_reason ?: ($en ? 'This comment does not fit our friendly community rules. Try a kind question, appreciation or encouragement.' : 'Deze reactie past niet bij onze vriendelijke huisregels. Probeer een vriendelijke vraag, waardering of aanmoediging.') }}</p></div>
<details class="revise-comment" @if(old('revise_id') == $comment->id) open @endif><summary>{{ $en ? 'Edit and submit for a new review' : 'Aanpassen en opnieuw laten keuren' }}</summary><form method="post" action="/comments/{{ $comment->id }}/revise">
@csrf
<input type="hidden" name="revise_id" value="{{ $comment->id }}"><label for="revise-{{ $comment->id }}">{{ $en ? 'Your revised comment' : 'Je aangepaste reactie' }}</label><textarea id="revise-{{ $comment->id }}" name="body" minlength="3" maxlength="1000" required>{{ old('revise_id') == $comment->id ? old('body', $comment->body) : $comment->body }}</textarea><p class="small">{{ $en ? 'Your revised text is reviewed again before publication. Editing does not earn extra points.' : 'De aangepaste tekst wordt opnieuw gekeurd vóór plaatsing. Aanpassen levert geen extra punten op.' }}</p><button>{{ $en ? 'Review this version' : 'Keur deze versie opnieuw' }}</button></form></details>
@if(!$comment->appealed_at)
<form method="post" action="/comments/{{ $comment->id }}/appeal">
@csrf
<button class="quiet">{{ $en ? 'Ask for a human review' : 'Vraag een menselijke blik' }}</button></form>
@endif
@elseif($comment->status === 'doubt')
<p class="comment-feedback">{{ $en ? 'A moderator is taking a look. Your comment is private until it is approved.' : 'De beheerder kijkt even mee. Je reactie is nog niet openbaar en verschijnt pas na goedkeuring.' }}</p>
@endif
</article>
@empty
<p>{{ $en ? 'No comments yet. Your first kind word can start here.' : 'Nog geen reacties. Je eerste aardige woord kan hier beginnen.' }}</p>
@endforelse
<div class="pagination">
@if($comments->previousPageUrl())
<a href="{{ $comments->previousPageUrl() }}">{{ $en ? 'Previous' : 'Vorige' }}</a>
@endif
@if($comments->hasMorePages())
<a href="{{ $comments->nextPageUrl() }}">{{ $en ? 'Next' : 'Volgende' }}</a>
@endif
</div></section>
