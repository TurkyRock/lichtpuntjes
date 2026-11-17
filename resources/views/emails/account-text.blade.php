Jouw Lichtpuntjes

{{ $greeting }}

@foreach($introLines as $line)
{{ $line }}

@endforeach
@if(!empty($actionText))
{{ $actionText }}:
{!! $actionUrl !!}

@endif
@foreach($outroLines as $line)
{{ $line }}

@endforeach
{{ $mailEn?'A sunny greeting,':'Een zonnige groet,' }}
Jouw Lichtpuntjes

{{ ($mailPurpose??'account')==='contribution' ? ($mailEn?'This is a service email about your contribution.':'Dit is een servicemail over je bijdrage.') : ($mailEn?'This is a service email about your account.':'Dit is een servicemail over je account.') }}
{{ rtrim(config('app.url'),'/') }}/privacy?lang={{ $mailEn?'en':'nl' }}
