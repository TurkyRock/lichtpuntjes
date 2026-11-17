@if($pledge->stopped_at || in_array($pledge->subscription_status,['canceled','completed']))
<p class="support-monthly-status">{{ $en?'Your monthly support has stopped. Thank you for your earlier contribution.':'Je maandelijkse bijdrage is gestopt. Dank je wel voor je eerdere bijdrage.' }}</p>
@elseif(!($subscriptionProblem??false) && $pledge->subscription_status==='active')
<p class="support-monthly-status">{{ $en?'Your monthly support is active. You can manage or stop it in My donations.':'Je maandelijkse bijdrage is actief. Je kunt deze beheren of stoppen bij Mijn donaties.' }}</p>
@elseif(!($subscriptionProblem??false) && $pledge->subscription_status==='suspended')
<p class="support-monthly-status">{{ $en?'Your monthly support is currently paused. View its status in My donations.':'Je maandelijkse afspraak is op dit moment gepauzeerd. Bekijk de status bij Mijn donaties.' }}</p>
@else
<p class="support-monthly-status" role="status">{{ $en?'Your first payment is confirmed. Your monthly arrangement has not yet been fully confirmed. You do not need to pay again. View its status in My donations.':'Je eerste betaling is bevestigd. Je maandelijkse afspraak is nog niet volledig bevestigd. Je hoeft niet opnieuw te betalen. Je vindt de status bij Mijn donaties.' }}</p>
@endif
