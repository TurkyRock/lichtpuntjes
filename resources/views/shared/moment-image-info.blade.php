@if($moment->hasMomentImage())
<details class="moment-image-info"><summary aria-label="{{ app()->getLocale()==='en'?'About this image':'Over deze afbeelding' }}" title="{{ app()->getLocale()==='en'?'About this image':'Over deze afbeelding' }}"><span aria-hidden="true">ⓘ</span></summary><p>{{ app()->getLocale()==='en'?'AI-generated symbolic mood image.':'AI-gegenereerd symbolisch sfeerbeeld.' }}</p></details>
@endif
