<svg class="account-menu-icon" width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
@switch($icon)
@case('home')<path d="m3 11 9-8 9 8M5 10v11h14V10"/>@break
@case('pencil')<path d="m4 16-1 5 5-1L21 7l-4-4Z M14 6l4 4M4 16l4 4"/>@break
@case('sun')<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M2 12h2M20 12h2M5 5l1.5 1.5M17.5 17.5 19 19M5 19l1.5-1.5M17.5 6.5 19 5"/>@break
@case('stories')<rect x="4" y="3" width="16" height="18" rx="1"/><path d="M8 8h8M8 12h8M8 16h8"/>@break
@case('trophy')<path d="M7 3h10v7a5 5 0 0 1-10 0ZM7 5H3v3a5 5 0 0 0 5 5M17 5h4v3a5 5 0 0 1-5 5M12 15v5M8 21h8"/>@break
@case('heart')<path d="M12 21 3.5 12.5C-2 6 6 1 12 7c6-6 14-1 8.5 5.5Z"/>@break
@case('plus')<path d="M12 3v18M3 12h18"/>@break
@case('donations')<rect x="3" y="5" width="18" height="14" rx="1"/><path d="M3 9h18M7 14h5"/>@break
@case('invite')<path d="m3 10 18-7-7 18-4-7Z M10 14 21 3"/>@break
@case('settings')<path d="m9 3 1-2h4l1 2 3 2 2 1v4l-2 2v3l-1 2-3 1-2 3-3-2-3-1-1-3v-3l-2-2V6l2-1Z" transform="translate(1 1) scale(.92)"/><circle cx="12" cy="11" r="3"/>@break
@case('trash')<path d="M3 6h18M9 6V3h6v3M5 6l1 15h12l1-15M10 10v7M14 10v7"/>@break
@endswitch
</svg>
