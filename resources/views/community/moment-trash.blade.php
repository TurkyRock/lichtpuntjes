@extends('community.layout')
@section('content')
<h1>Prullenbak</h1><p>Je verwijderde momentjes kun je 30 dagen terugzetten. Daarna verwijderen we ze definitief. Herstelde momentjes zijn eerst privé.</p>
<p><a href="/mijn-momentjes">Terug naar mijn momentjes</a></p>
@forelse($moments as $m)<section class="panel"><p>{{ $m->moment_date->format('d-m-Y') }}</p><p>{{ $m->body }}</p><p>Herstelbaar tot {{ $m->deleted_at->copy()->addDays(config('retention.trash_days'))->timezone('Europe/Amsterdam')->format('d-m-Y H:i') }}</p><form method="post" action="/mijn-momentjes/prullenbak/{{ $m->id }}/herstellen">@csrf<button>Privé terugzetten</button></form><form method="post" action="/mijn-momentjes/prullenbak/{{ $m->id }}">@csrf @method('DELETE')<button>Definitief verwijderen</button></form></section>@empty<p>Je prullenbak is leeg.</p>@endforelse
{{ $moments->links() }}
@endsection
