<span class="follow-us-icon follow-us-icon-{{ $platform }}" aria-hidden="true">
<svg viewBox="0 0 32 32" focusable="false">
@switch($platform)
@case('instagram')
<rect x="5" y="5" width="22" height="22" rx="6" fill="none" stroke="currentColor" stroke-width="2.5"/><circle cx="16" cy="16" r="5.3" fill="none" stroke="currentColor" stroke-width="2.5"/><circle cx="23" cy="9" r="1.6" fill="currentColor"/>
@break
@case('facebook')
<path fill="currentColor" d="M19 29V18h4l.7-5H19v-2.6c0-1.5.5-2.4 2.6-2.4H24V3.5c-.5-.1-2-.3-3.8-.3-4 0-6.7 2.4-6.7 6.8v3H10v5h3.5v11Z"/>
@break
@case('tiktok')
<path d="M18 4h4c.4 3 2.4 5.2 5.5 5.7v4.2a12 12 0 0 1-5.5-1.8V22a8 8 0 1 1-8-8h1v4.3h-1a3.7 3.7 0 1 0 4 3.7Z" fill="currentColor"/>
@break
@case('linkedin')
<circle cx="7.5" cy="7.5" r="2.5" fill="currentColor"/><path fill="currentColor" d="M5 12h5v15H5Zm8 0h4.7v2c.9-1.6 2.3-2.5 4.5-2.5 4 0 5.8 2.5 5.8 6.7V27h-5v-8c0-2.2-.7-3.4-2.4-3.4-1.9 0-2.6 1.4-2.6 3.4v8h-5Z"/>
@break
@default
<path d="M10 22 22 10M11 10h11v11" fill="none" stroke="currentColor" stroke-width="2.5"/>
@endswitch
</svg>
</span>
