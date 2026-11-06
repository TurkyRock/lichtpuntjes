@php($supportInvitation=request()->attributes->get('support_invitation'))
@if($supportInvitation)
<link rel="stylesheet" href="/css/support-invitations.css?v={{ filemtime(public_path('css/support-invitations.css')) }}">
<div class="support-invitation-slot" data-support-invitation data-kind="{{ $supportInvitation['kind'] }}" data-placement="{{ $supportInvitation['placement'] }}" data-token="{{ csrf_token() }}" data-scope="{{ auth()->check()?'member-'.auth()->id():'guest' }}">
@if($supportInvitation['kind']==='popup')<dialog class="support-invitation-dialog" aria-labelledby="supportInvitationTitle">@include('shared.support-invitation-card',['supportPopup'=>true])</dialog>@else @include('shared.support-invitation-card',['supportPopup'=>false]) @endif
</div><script src="/js/support-invitations.js?v={{ filemtime(public_path('js/support-invitations.js')) }}" defer nonce="{{ request()->attributes->get('csp_nonce') }}"></script>
@endif
