@include('shared.publication-revision-styles')
@php
$revisionEn = app()->getLocale() === 'en';
$revisionCounts = app(\App\Services\PublicationRevisions::class)->forUser(auth()->user());
$revisionScope = $revisionScope ?? 'all';
$showRevisionMoments = $revisionScope !== 'stories' && $revisionCounts['moments'] > 0;
$showRevisionStories = $revisionScope !== 'moments' && $revisionCounts['stories'] > 0;
@endphp
@if($showRevisionMoments || $showRevisionStories)
<aside class="publication-revision-notice" aria-label="{{ $revisionEn ? 'Changes needed before publication' : 'Aanpassen nodig voor publicatie' }}">
<span class="publication-revision-icon" aria-hidden="true">!</span>
<div class="publication-revision-copy"><h2>{{ $revisionEn ? 'Changes needed before publication' : 'Nog aanpassen voor publicatie' }}</h2>
<p>{{ $revisionEn ? 'Some of your contributions need changes before they can be published. Read the feedback, edit your text and submit it for review again.' : 'Er staat nog iets voor je klaar om aan te passen. Lees de feedback, pas je tekst aan en stuur deze opnieuw in voor controle. Tot die tijd is je bijdrage niet gepubliceerd.' }}</p>
<div class="publication-revision-actions">
@if($showRevisionMoments)<a href="/mijn-momentjes?needs_attention=1&amp;lang={{ app()->getLocale() }}#moments-list">{{ $revisionEn ? 'Revise moments' : 'Momentjes aanpassen' }} ({{ $revisionCounts['moments'] }}) <span aria-hidden="true">→</span></a>@endif
@if($showRevisionStories)<a href="/mijn-lichtpuntjes?needs_attention=1&amp;lang={{ app()->getLocale() }}#earlier-stories">{{ $revisionEn ? 'Revise stories' : 'Verhalen aanpassen' }} ({{ $revisionCounts['stories'] }}) <span aria-hidden="true">→</span></a>@endif
</div></div></aside>
@endif
