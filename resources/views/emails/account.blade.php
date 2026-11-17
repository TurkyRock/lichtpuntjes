<!doctype html>
<html lang="{{ $mailEn?'en':'nl' }}"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>{{ $subject }}</title></head>
<body style="margin:0;padding:0;background-color:#dfe8d6;color:#193f37;font-family:Arial,Helvetica,sans-serif;-webkit-text-size-adjust:100%;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#dfe8d6" style="width:100%;background-color:#dfe8d6;"><tr><td align="center" style="padding:28px 12px;">
<!--[if mso]><table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0"><tr><td><![endif]-->
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;max-width:600px;">
<tr><td align="left" style="padding:10px 4px 26px;font-family:Georgia,'Times New Roman',serif;font-size:29px;font-weight:bold;line-height:1.3;color:#193f37;"><a href="{{ rtrim(config('app.url'),'/') }}" style="color:#193f37;text-decoration:none;"><span style="color:#c99e22;font-family:Georgia,serif;">&#9728;&#65038;</span> Jouw Lichtpuntjes<span style="color:#c99e22;">.</span></a><p style="margin:7px 0 0;color:#526b58;font-family:Arial,Helvetica,sans-serif;font-size:12px;font-weight:normal;line-height:1.6;">{{ $mailEn?'Good news to pass on.':'Goed nieuws om door te geven.' }}</p></td></tr>
<tr><td bgcolor="#fffdf5" style="background-color:#fffdf5;padding:26px 24px 32px;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;margin-bottom:20px;"><tr><td valign="middle" style="padding-right:12px;color:#547260;font-size:11px;line-height:1.6;letter-spacing:1.8px;font-weight:bold;">{{ $mailEn?'POST WITH A SMILE':'POST MET EEN GLIMLACH' }}</td><td width="104" align="center" valign="middle" bgcolor="#f4d76d" style="width:104px;padding:8px 5px;background-color:#f4d76d;border:3px dashed #fffdf5;"><span aria-hidden="true" style="font-family:Georgia,serif;font-size:30px;line-height:1.2;color:#54713e;">&#9728;&#65038;</span><br><span style="font-family:Georgia,serif;font-size:11px;font-style:italic;line-height:1.6;color:#365448;">{{ $mailEn?'a little sunshine':'een beetje zon' }}</span></td></tr></table>
<h1 style="margin:0 0 23px;color:#193f37;font-family:Georgia,'Times New Roman',serif;font-size:29px;line-height:1.25;font-weight:bold;">{{ $greeting }}</h1>
@foreach($introLines as $line)<p style="margin:0 0 19px;color:#294b42;font-size:16px;line-height:1.7;">{{ $line }}</p>@endforeach
@if(!empty($actionText))
<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:24px 0;"><tr><td bgcolor="#326653" align="center" style="background-color:#326653;padding:0;mso-padding-alt:13px 20px;"><a href="{{ $actionUrl }}" style="display:inline-block;background-color:#326653;color:#fffdf5;font-family:Arial,Helvetica,sans-serif;font-size:15px;font-weight:bold;line-height:22px;text-decoration:none;padding:13px 20px;mso-padding-alt:0;border:1px solid #326653;">{{ $actionText }}&nbsp; &#8594;</a></td></tr></table>
@endif
@foreach($outroLines as $line)<p style="margin:0 0 18px;color:#486354;font-size:14px;line-height:1.7;">{{ $line }}</p>@endforeach
<p style="margin:28px 0 0;color:#294b42;font-size:15px;line-height:1.7;">{{ $mailEn?'A sunny greeting,':'Een zonnige groet,' }}<br><strong style="font-family:Georgia,'Times New Roman',serif;font-size:21px;">Erik · Jouw Lichtpuntjes</strong></p>
@if(!empty($actionText))
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="width:100%;table-layout:fixed;margin-top:28px;"><tr><td style="border-top:1px solid #dce2d2;padding-top:18px;color:#657466;font-size:12px;line-height:1.65;word-wrap:break-word;overflow-wrap:anywhere;word-break:break-all;">
<span style="word-break:normal;">{{ $mailEn?'Button not working? Copy this personal link into your browser:':'Werkt de knop niet? Kopieer deze persoonlijke link naar je browser:' }}</span><br><a href="{{ $actionUrl }}" style="color:#316b59;word-break:break-all;">{{ $actionUrl }}</a></td></tr></table>
@endif
</td></tr>
<tr><td height="7" bgcolor="#fffdf5" style="height:7px;font-size:1px;line-height:7px;border-bottom:5px dotted #dfe8d6;background-color:#fffdf5;">&nbsp;</td></tr>
<tr><td align="center" style="padding:24px 16px 8px;font-family:Georgia,'Times New Roman',serif;font-size:19px;font-style:italic;line-height:1.5;color:#365a48;">{{ $mailEn?'A small gesture. A bright little moment.':'Een klein gebaar. Een groot lichtpuntje.' }}</td></tr>
<tr><td align="center" style="padding:23px 12px 8px;color:#617361;font-size:11px;line-height:1.7;">{{ ($mailPurpose??'account')==='contribution' ? ($mailEn?'This is a service email about your contribution.':'Dit is een servicemail over je bijdrage.') : ($mailEn?'This is a service email about your account.':'Dit is een servicemail over je account.') }}<br><a href="{{ rtrim(config('app.url'),'/') }}/privacy?lang={{ $mailEn?'en':'nl' }}" style="color:#486354;">{{ $mailEn?'Privacy statement':'Privacyverklaring' }}</a> · © {{ date('Y') }} Jouw Lichtpuntjes</td></tr>
</table>
<!--[if mso]></td></tr></table><![endif]-->
</td></tr></table></body></html>
