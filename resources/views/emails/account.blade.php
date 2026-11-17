<!doctype html>
<html lang="{{ $mailEn?'en':'nl' }}"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>{{ $subject }}</title></head>
<body style="margin:0;padding:0;background-color:#edf1e7;color:#193f37;font-family:Arial,Helvetica,sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#edf1e7" style="width:100%;background-color:#edf1e7;"><tr><td align="center" style="padding:28px 12px;">
<!--[if mso]><table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0"><tr><td><![endif]-->
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;max-width:600px;">
<tr><td align="center" style="padding:12px 12px 26px;font-family:Georgia,'Times New Roman',serif;font-size:30px;font-weight:bold;line-height:1.3;color:#193f37;"><a href="{{ rtrim(config('app.url'),'/') }}" style="color:#193f37;text-decoration:none;"><span style="color:#e9b535;font-size:36px;">☀</span> Jouw Lichtpuntjes<span style="color:#dcb643;">.</span></a></td></tr>
<tr><td height="7" bgcolor="#f3d474" style="height:7px;background-color:#f3d474;font-size:1px;line-height:7px;">&nbsp;</td></tr>
<tr><td bgcolor="#fffdf5" style="background-color:#fffdf5;border-left:1px solid #e3dfcf;border-right:1px solid #e3dfcf;padding:34px 28px 28px;">
<p style="margin:0 0 16px;font-size:11px;line-height:1.5;letter-spacing:2px;font-weight:bold;color:#547260;">{{ $mailEn?'A LITTLE NOTE FOR YOU':'EEN KLEIN BRIEFJE VOOR JOU' }}</p>
<h1 style="margin:0 0 23px;color:#193f37;font-family:Georgia,'Times New Roman',serif;font-size:29px;line-height:1.25;font-weight:bold;">{{ $greeting }}</h1>
@foreach($introLines as $line)<p style="margin:0 0 19px;color:#294b42;font-size:16px;line-height:1.7;">{{ $line }}</p>@endforeach
@if(!empty($actionText))
<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:26px 0;"><tr><td bgcolor="#f5d76d" align="center" style="background-color:#f5d76d;border:1px solid #e1be4d;padding:15px 22px;mso-padding-alt:15px 22px;"><a href="{{ $actionUrl }}" style="display:inline-block;color:#193f37;font-family:Arial,Helvetica,sans-serif;font-size:16px;font-weight:bold;line-height:1.5;text-decoration:none;">{{ $actionText }}</a></td></tr></table>
@endif
@foreach($outroLines as $line)<p style="margin:0 0 18px;color:#486354;font-size:14px;line-height:1.7;">{{ $line }}</p>@endforeach
<p style="margin:28px 0 0;color:#294b42;font-size:15px;line-height:1.7;">{{ $mailEn?'A sunny greeting,':'Een zonnige groet,' }}<br><strong style="font-family:Georgia,'Times New Roman',serif;font-size:21px;">Jouw Lichtpuntjes</strong></p>
@if(!empty($actionText))
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;table-layout:fixed;margin-top:28px;"><tr><td style="border-top:1px solid #dce2d2;padding-top:18px;color:#657466;font-size:12px;line-height:1.65;word-wrap:break-word;overflow-wrap:anywhere;word-break:break-all;">
<span style="word-break:normal;">{{ $mailEn?'Button not working? Copy this personal link into your browser:':'Werkt de knop niet? Kopieer deze persoonlijke link naar je browser:' }}</span><br><a href="{{ $actionUrl }}" style="color:#316b59;word-break:break-all;">{{ $actionUrl }}</a></td></tr></table>
@endif
</td></tr>
<tr><td align="center" bgcolor="#e0e8d4" style="padding:17px 20px;background-color:#e0e8d4;border:1px solid #d3ddc8;font-family:Georgia,'Times New Roman',serif;font-size:18px;line-height:1.5;color:#365a48;">{{ $mailEn?'A little more light in your day.':'Een beetje meer licht in je dag.' }}</td></tr>
<tr><td align="center" style="padding:23px 12px 8px;color:#617361;font-size:11px;line-height:1.7;">{{ ($mailPurpose??'account')==='contribution' ? ($mailEn?'This is a service email about your contribution.':'Dit is een servicemail over je bijdrage.') : ($mailEn?'This is a service email about your account.':'Dit is een servicemail over je account.') }}<br><a href="{{ rtrim(config('app.url'),'/') }}/privacy?lang={{ $mailEn?'en':'nl' }}" style="color:#486354;">{{ $mailEn?'Privacy statement':'Privacyverklaring' }}</a> · © {{ date('Y') }} Jouw Lichtpuntjes</td></tr>
</table>
<!--[if mso]></td></tr></table><![endif]-->
</td></tr></table></body></html>
