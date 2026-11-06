@if($comment->status === 'rejected')
<div class="comment-feedback"><strong>{{ $en ? 'Why it was not published' : 'Waarom niet geplaatst?' }}</strong><p>{{ $comment->moderator_note ?: $comment->ai_reason ?: ($en ? 'This comment does not fit our friendly community rules. Try a kind question, appreciation or encouragement.' : 'Deze reactie past niet bij onze vriendelijke huisregels. Probeer een vriendelijke vraag, waardering of aanmoediging.') }}</p></div>
@if($available)<details class="revise-comment" @if(old('revise_id') == $comment->id) open @endif><summary>{{ $en ? 'Edit and submit for a new review' : 'Aanpassen en opnieuw laten keuren' }}</summary><form method="post" action="/comments/{{ $comment->id }}/revise">
@csrf
<input type="hidden" name="revise_id" value="{{ $comment->id }}"><label for="revise-{{ $comment->id }}">{{ $en ? 'Your revised comment' : 'Je aangepaste reactie' }}</label><textarea id="revise-{{ $comment->id }}" name="body" minlength="3" maxlength="1000" required>{{ old('revise_id') == $comment->id ? old('body', $comment->body) : $comment->body }}</textarea><p class="small">{{ $en ? 'Your revised text is reviewed again before publication. Editing does not earn extra points.' : 'De aangepaste tekst wordt opnieuw gekeurd vóór plaatsing. Aanpassen levert geen extra punten op.' }}</p><button>{{ $en ? 'Review this version' : 'Keur deze versie opnieuw' }}</button></form></details>@endif
@if(!$comment->appealed_at)
<form method="post" action="/comments/{{ $comment->id }}/appeal">
@csrf
<button class="quiet">{{ $en ? 'Ask for a human review' : 'Vraag een menselijke blik' }}</button></form>
@endif
@elseif($comment->status === 'doubt')
<p class="comment-feedback">{{ $en ? 'A moderator is taking a look. Your comment is private until it is approved.' : 'De beheerder kijkt even mee. Je reactie is nog niet openbaar en verschijnt pas na goedkeuring.' }}</p>
@endif
