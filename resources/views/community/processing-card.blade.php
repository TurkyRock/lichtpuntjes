@php
$progressEn=app()->getLocale()==='en';
$progressKind=$progressRecord instanceof \App\Models\LightspotSubmission?'story':'moment';
$progress=app(\App\Services\ContributionProgress::class)->for($progressRecord,$progressEn);
@endphp
<div class="processing-card" data-processing-card data-progress-imagealt="{{ $progressEn?'AI mood image':'AI-sfeerbeeld' }}" data-progress-code="{{ $progress['code'] }}" data-progress-url="/mijn-verwerking/{{ $progressKind }}/{{ $progressRecord->id }}?lang={{ app()->getLocale() }}" data-progress-place="{{ $progressEn?'Place {n} in the queue':'Plek {n} in de wachtrij' }}">
<div class="processing-card-title"><span class="processing-paper-sun" aria-hidden="true">☀</span><strong data-progress-heading>{{ $progress['heading'] }}</strong></div>
<p class="processing-position" data-progress-position @if(!$progress['position']) hidden @endif>{{ $progress['position']?($progressEn?'Place '.$progress['position'].' in the queue':'Plek '.$progress['position'].' in de wachtrij'):'' }}</p>
<p data-progress-message>{{ $progress['message'] }}</p>
<ol class="processing-steps" aria-label="{{ $progressEn?'Progress':'Voortgang' }}">
@foreach([$progressEn?'Saved':'Bewaard',$progressEn?'Review':'Controle',$progressEn?'Image':'Beeld',$progressEn?'Ready':'Klaar'] as $step)
<li @if($loop->iteration<=$progress['stage']) class="is-done" @endif @if($loop->iteration===$progress['stage']) aria-current="step" @endif><span aria-hidden="true">{{ $loop->iteration }}</span>{{ $step }}</li>
@endforeach
</ol>
<a class="processing-result" data-progress-result @if($progress['link']) href="{{ $progress['link'] }}" @else hidden @endif>{{ $progressEn?'View my story →':'Bekijk mijn verhaal →' }}</a>
<span class="processing-announcement" aria-live="polite" aria-atomic="true" data-progress-announce></span>
</div>
