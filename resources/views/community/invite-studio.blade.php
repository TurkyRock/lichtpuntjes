@php
 $en=$profileEn;
 $inviteLink=url('/aanmelden').'?'.http_build_query(['lang'=>app()->getLocale(),'invite'=>$user->invite_code]);
 $inviteText=$en?'Here is something that makes me smile: positive news and little joys. Maybe Jouw Lichtpuntje is a place for you too. ☀':'Hier word ik blij van: positief nieuws en kleine gelukjes. Misschien is Jouw Lichtpuntje ook iets voor jou. ☀';
 $fullInvite=$inviteText."\n\n".$inviteLink;
 $platforms=[
 ['whatsapp','WhatsApp','https://wa.me/?text='.rawurlencode($fullInvite)],
 ['facebook','Facebook','https://www.facebook.com/sharer/sharer.php?u='.rawurlencode($inviteLink)],
 ['messenger','Messenger',null],['instagram','Instagram',null],['tiktok','TikTok',null],
 ['linkedin','LinkedIn','https://www.linkedin.com/sharing/share-offsite/?url='.rawurlencode($inviteLink)],
 ['threads','Threads','https://www.threads.com/intent/post?text='.rawurlencode($fullInvite)],
 ['x','X','https://twitter.com/intent/tweet?text='.rawurlencode($inviteText).'&url='.rawurlencode($inviteLink)],
 ['pinterest','Pinterest','https://www.pinterest.com/pin/create/button/?url='.rawurlencode($inviteLink).'&media='.rawurlencode(url('/images/invite-sun-envelope-v1.png')).'&description='.rawurlencode($inviteText)],
 ['telegram','Telegram','https://t.me/share/url?url='.rawurlencode($inviteLink).'&text='.rawurlencode($inviteText)],
 ['signal','Signal',null],['email',$en?'Email':'E-mail','mailto:?subject='.rawurlencode($en?'A little sunshine for you':'Een lichtpuntje voor jou').'&body='.rawurlencode($fullInvite)]
 ];
@endphp
<link rel="stylesheet" href="/css/invite-studio.css?v={{ filemtime(public_path('css/invite-studio.css')) }}">
<section id="inviteTitle" class="invite-studio" data-invite-studio data-language="{{ $en?'en':'nl' }}" data-invite-url="{{ $inviteLink }}">
 <div class="invite-intro">
  <div><h2>{{ $en?'A little sunshine is even better shared':'Een lichtpuntje is leuker als je het deelt' }}</h2><p>{{ $en?'Give someone else a lovely place full of positive news and little joys.':'Gun iemand anders ook een fijne plek vol positief nieuws en kleine gelukjes.' }}</p></div>
  <img class="invite-envelope" src="/images/invite-sun-envelope-v1.png" width="190" height="190" alt="">
 </div>
 <div class="invite-quick invite-paper invite-paper--yellow"><p>{{ $en?'Invite a friend, family member or colleague ♡':'Nodig een vriend, familielid of collega uit ♡' }}</p><div class="invite-quick-buttons">
  <a class="button" data-invite-platform="whatsapp" href="{{ $platforms[0][2] }}" target="_blank" rel="noopener noreferrer"><img src="/images/share/whatsapp.svg" alt="" width="25" height="25">{{ $en?'Send a little sunshine':'Stuur een lichtpuntje' }} <span aria-hidden="true">›</span></a>
  <button type="button" data-invite-copy="link"><img src="/images/share/copy.svg" alt="" width="25" height="25">{{ $en?'Copy link':'Kopieer link' }}</button>
 </div></div>
 <div class="invite-middle">
  <div class="invite-message invite-paper"><h3>{{ $en?'Here is something you can send':'Dit kun je erbij sturen' }}</h3><label for="inviteMessage">{{ $en?'Make this message your own':'Maak dit berichtje gerust persoonlijk' }}</label><textarea id="inviteMessage" maxlength="600" rows="3">{{ $inviteText }}</textarea><button class="quiet" type="button" data-invite-copy="message">{{ $en?'Copy message + link':'Kopieer bericht + link' }}</button></div>
  <div class="invite-card-wrap"><div class="invite-share-card" aria-label="{{ $en?'Preview of the downloadable card':'Voorbeeld van het deelkaartje' }}"><strong>{{ $en?'A little good news does you good':'Een beetje goed nieuws doet goed' }}</strong><img src="/images/invite-sun-envelope-v1.png" width="160" height="160" alt=""><span>Jouw Lichtpuntje</span></div><button type="button" data-invite-download>{{ $en?'Download share card':'Download deelkaartje' }}</button></div>
 </div>
 <div class="invite-options invite-paper"><h3>{{ $en?'Share it your way':'Deel op jouw manier' }}</h3><div class="invite-social-grid">
 @foreach($platforms as [$key,$label,$href])
 @if($href)<a class="button quiet invite-social" data-invite-platform="{{ $key }}" href="{{ $href }}" @if($key!=='email')target="_blank" rel="noopener noreferrer"@endif>@else<button type="button" class="quiet invite-social" data-invite-help="{{ $key }}" aria-haspopup="dialog">@endif
 @if(in_array($key,['whatsapp','facebook','instagram','tiktok','linkedin','email'],true))<img src="/images/share/{{ $key }}.svg" alt="" width="24" height="24">@else<span class="invite-brand invite-brand--{{ $key }}" aria-hidden="true">{{ ['messenger'=>'ϟ','threads'=>'@','x'=>'𝕏','pinterest'=>'P','telegram'=>'➤','signal'=>'◌'][$key] }}</span>@endif
 <span>{{ $label }}</span>@if($href)</a>@else</button>@endif
 @endforeach
 </div><div class="invite-options-footer"><p>{{ $en?'Instagram or TikTok? Use the card and add your link.':'Instagram of TikTok? Gebruik het kaartje en plak je link erbij.' }}</p><button type="button" class="quiet" data-invite-native><img src="/images/share/native.svg" width="20" height="20" alt="">{{ $en?'More sharing options':'Meer deelopties' }}</button></div></div>
 <div class="invite-bottom">
  <div class="invite-link-note invite-paper"><label for="inviteLink">{{ $en?'Your invitation link':'Jouw uitnodigingslink' }}</label><div><input id="inviteLink" readonly value="{{ $inviteLink }}" aria-label="{{ $en?'Your personal invitation link':'Jouw persoonlijke uitnodigingslink' }}"><button type="button" class="quiet" data-invite-copy="link">{{ $en?'Copy link':'Kopieer link' }}</button></div></div>
  <p class="invite-reward"><span aria-hidden="true">★</span><span><strong>{{ $en?'5 bright points':'5 lichtpunten' }}</strong><br>{{ $en?'once your friend verifies their email and posts a first positive comment.':'zodra je vriend zijn e-mail bevestigt en een eerste positieve reactie plaatst.' }}</span></p>
 </div>
 <p id="inviteStatus" class="invite-status" role="status" aria-live="polite"></p>
 <noscript><p>{{ $en?'Use the sharing links above or select and copy your invitation link.':'Gebruik de deellinks hierboven of selecteer en kopieer je uitnodigingslink.' }}</p></noscript>
 <dialog class="invite-dialog" data-invite-dialog aria-labelledby="inviteDialogTitle">
  <button type="button" class="quiet invite-dialog-close" data-invite-close aria-label="{{ $en?'Close':'Sluiten' }}">×</button>
  <h2 id="inviteDialogTitle"></h2><p data-invite-instructions></p>
  <div class="invite-dialog-actions"><button type="button" data-invite-copy="message">{{ $en?'Copy message + link':'Kopieer bericht + link' }}</button><button type="button" class="quiet" data-invite-download>{{ $en?'Download share card':'Download deelkaartje' }}</button><button type="button" class="quiet" data-invite-native>{{ $en?'Open sharing menu':'Open deelmenu' }}</button><a class="button quiet" data-invite-open target="_blank" rel="noopener noreferrer"></a></div>
  <textarea class="invite-manual-copy" data-invite-manual-copy readonly rows="5" aria-label="{{ $en?'Message to copy':'Bericht om te kopiëren' }}" hidden></textarea><p class="invite-status" data-invite-dialog-status role="status" aria-live="polite"></p>
 </dialog>
</section>
<script src="/js/invite-studio.js?v={{ filemtime(public_path('js/invite-studio.js')) }}" defer nonce="{{ request()->attributes->get('csp_nonce') }}"></script>
