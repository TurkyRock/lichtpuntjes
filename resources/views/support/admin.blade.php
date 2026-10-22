@extends('community.layout')
@section('content')
<h1>Bijdragen</h1><p>Betaalstatussen bevestigd via Mollie. Testbijdragen staan apart van echte betalingen.</p>
<section class="support-paper"><h2>Ontvangen ({{ app(\App\Services\SupportPayments::class)->mode()==='live'?'live':'test' }})</h2><p>Bruto: €{{ number_format($totals->gross/100,2,',','.') }} · Terugbetaald: €{{ number_format($totals->refunds/100,2,',','.') }} · Teruggeboekt: €{{ number_format($totals->chargebacks/100,2,',','.') }}</p><p class="small">Bedragen vóór betaalverwerkingskosten. Mollie blijft leidend voor je financiële administratie.</p></section>
@foreach($pledges as $pledge)<section class="support-paper"><h2>€{{ number_format($pledge->cents/100,2,',','.') }} · {{ $pledge->frequency==='monthly'?'Maandelijks':'Eenmalig' }}</h2><p>{{ $pledge->user?->name??'Zonder account' }} · {{ $pledge->mode }} · {{ $pledge->status }} · {{ $pledge->subscription_status }}</p><p>{{ $pledge->created_at->format('d-m-Y H:i') }} · {{ $pledge->id }}</p></section>@endforeach
{{ $pledges->links() }}
@endsection
