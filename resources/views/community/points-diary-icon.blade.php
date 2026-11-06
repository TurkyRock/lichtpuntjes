<svg class="diary-activity-icon" width="31" height="31" viewBox="0 0 32 32" fill="none" aria-hidden="true">
@if($kind==='heart')
<path d="M16 27 5 16C-3 6 10-1 16 8 23-1 36 7 27 17Z" fill="#d88f7c" stroke="#a65b53" stroke-width="1.3"/><path d="M6 12Q5 6 11 6" stroke="#fce1ca" stroke-width="2" stroke-linecap="round"/>
@elseif($kind==='read')
<path d="M3 7Q10 4 16 8V28Q10 24 3 26ZM16 8Q23 4 29 7V26Q22 24 16 28Z" fill="#f2dfb9" stroke="#aa8650" stroke-width="1.5"/><path d="M7 10 12 11M7 15 12 16M20 11 25 10M20 16 25 15M16 8V27" stroke="#c5a474" stroke-width="1.3"/>
@elseif($kind==='return')
<path d="M16 0 18 7 15 7ZM27 4 24 11 21 9ZM32 16 25 18 25 15ZM27 28 21 24 23 21ZM16 32 14 25 17 25ZM4 27 8 21 11 24ZM0 16 7 14 7 17ZM4 4 11 8 9 11Z" fill="#e3bc4f"/><circle cx="16" cy="16" r="7" fill="#efd16d"/>
@elseif($kind==='comment')
<path d="M3 5 29 4 28 23 15 23 7 29 7 23 3 23Z" fill="#8fafa4" stroke="#607f72" stroke-width="1.3"/><path d="M8 11H24M8 16H20" stroke="#fff9e8" stroke-width="1.6" stroke-linecap="round"/>
@elseif($kind==='moment')
<path d="M10 5Q14 0 18 5L23 15H30V27H2V15H9Z" fill="#ecd69d" stroke="#ab8b49" stroke-width="1.3"/><path d="M4 18H28M11 12H20" stroke="#fff4d1" stroke-width="2"/>
@elseif($kind==='share')
<path d="m2 13 28-10-9 27-6-12Z" fill="#b5cbb1" stroke="#708f73" stroke-width="1.3"/><path d="M15 18 25 8" stroke="#fff9e8" stroke-width="1.8"/>
@elseif($kind==='invite')
<path d="M3 10 16 2 29 10V28H3Z" fill="#e5c58c" stroke="#aa8550" stroke-width="1.3"/><path d="m3 10 13 10 13-10M3 28 13 18M29 28 19 18" stroke="#ab8956" stroke-width="1.2"/><path d="m16 17-5-5c-4-5 2-8 5-4 3-4 9-1 5 4Z" fill="#d78e7b"/>
@else
<path d="m16 2 4 9 10 2-7 7 1 10-8-5-9 5 2-10-7-7 10-2Z" fill="#e7c45d" stroke="#aa8d48" stroke-width="1.3"/>
@endif
</svg>
