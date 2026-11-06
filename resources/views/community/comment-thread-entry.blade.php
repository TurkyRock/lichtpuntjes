@php
 $mine=$comment->user_id===$user->id;
 $visible=$comment->status==='approved'&&!$comment->user->is_suspended;
 $shown=$mine||$visible;
 $labels=$en?['approved'=>'Published','pending'=>'Awaiting review','doubt'=>'Awaiting a human review','rejected'=>'Not published']:['approved'=>'Geplaatst','pending'=>'Wacht op keuring','doubt'=>'Wacht op een menselijke blik','rejected'=>'Niet geplaatst'];
@endphp
<div class="conversation-entry" id="comment-{{ $comment->id }}">
 @if($shown)
 <span class="conversation-avatar" aria-hidden="true">@if($comment->user->avatarUrl())<img src="{{ $comment->user->avatarUrl() }}" alt="">@else{{ mb_substr($comment->user->name,0,1) }}@endif</span>
 <div class="conversation-message">
  <div class="conversation-meta"><strong>{{ $mine?($en?'You':'Jij'):$comment->user->name }}</strong><time datetime="{{ $comment->created_at->toIso8601String() }}">{{ $comment->created_at->format('d-m-Y H:i') }}</time>@if($mine)<span class="conversation-state">{{ $labels[$comment->status]??$comment->status }}</span>@endif</div>
  <div class="conversation-body"><p>{{ $comment->bodyFor() }}</p>
   @if($visible && $available && $rootVisible && $canParticipate && !$mine)
   <form method="post" action="/comments/{{ $comment->id }}/heart?lang={{ $en?'en':'nl' }}" class="conversation-heart">@csrf<input type="hidden" name="liked" value="{{ $comment->hearted?0:1 }}"><button class="quiet" aria-pressed="{{ $comment->hearted?'true':'false' }}" aria-label="{{ $comment->hearted?($en?'Remove heart':'Hartje intrekken'):($en?'Give a heart':'Geef een hartje') }}"><span aria-hidden="true">{{ $comment->hearted?'♥':'♡' }}</span> {{ $comment->hearts_count }}</button></form>
   @else<span class="conversation-heart-count" aria-label="{{ $en?'Hearts':'Hartjes' }}">♥ {{ $comment->hearts_count }}</span>@endif
  </div>
  @if($mine)@include('community.comment-feedback')@endif
 </div>
 @else<p class="conversation-missing">{{ $en?'The original comment is no longer available.':'De oorspronkelijke reactie is niet meer beschikbaar.' }}</p>@endif
</div>
