<svg class="activity-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
@switch($kind)
@case('return')@case('visit')<circle cx="12" cy="12" r="4"/><path d="M12 1v3m0 16v3M1 12h3m16 0h3M4 4l2 2m12 12 2 2M4 20l2-2M18 6l2-2"/>@break
@case('read')<path d="M12 5c-3-2-7-2-10-1v15c3-1 7-1 10 1 3-2 7-2 10-1V4c-3-1-7-1-10 1Zm0 0v15"/>@break
@case('heart')<path d="M12 21C-5 10 5-1 12 7 19-1 29 10 12 21Z"/>@break
@case('moment')<path d="m4 16-1 5 5-1L21 7l-4-4ZM14 6l4 4M3 21l5-1"/>@break
@case('comment')<path d="M21 11c0 5-4 9-9 9H3l2-5a9 9 0 1 1 16-4Z"/><path d="M8 9h8m-8 4h5"/>@break
@case('story')<path d="M5 2h10l4 4v16H5Z"/><path d="M15 2v5h4M8 11h8m-8 4h8m-8 4h5"/>@break
@default<path d="M4 18c0-7 5-10 15-10m-6-5 6 5-6 5M4 18v3h15v-5"/>
@endswitch
</svg>
