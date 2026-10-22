@if($generated ?? false)
<a class="ai-image-label" href="{{ isset($seoArticle)?\App\Support\StorySeo::home($locale??app()->getLocale()).'#about-ai':'#about-ai' }}" aria-label="{{ ($locale??app()->getLocale())==='en'?'AI image: read how we use AI':'AI-beeld: lees hoe we AI gebruiken' }}">{{ ($locale??app()->getLocale())==='en'?'AI image':'AI-beeld' }} <span aria-hidden="true">ⓘ</span></a>
@endif
