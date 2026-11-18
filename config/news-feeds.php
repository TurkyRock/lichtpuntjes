<?php
return [
    'enabled'=>true,
    'strict_publisher_rights'=>true,
    'international_per_hour'=>2,
    'regional_per_hour'=>2,
    'queue_pause_at'=>10,
    'sources'=>[
        'horizon'=>[
            'name'=>'Horizon Magazine','feed'=>'https://projects.research-and-innovation.ec.europa.eu/en/horizon-magazine/articles.xml',
            'hosts'=>['projects.research-and-innovation.ec.europa.eu'],'paths'=>['/en/horizon-magazine/'],
            'license'=>'CC BY 4.0','license_url'=>'https://creativecommons.org/licenses/by/4.0/',
            'policy'=>'https://projects.research-and-innovation.ec.europa.eu/en/horizon-magazine/republish-our-stories',
            'commercial_reuse'=>true,'adaptations'=>true,'reviewed'=>'2026-11-18','author_required'=>true,'language'=>'en',
            'credit'=>'This article was originally published in Horizon, the EU Research and Innovation magazine.',
        ],
        'scidev'=>[
            'name'=>'SciDev.Net','feed'=>'https://www.scidev.net/global/feed/',
            'hosts'=>['www.scidev.net'],'paths'=>['/global/'],
            'license'=>'CC BY 3.0','license_url'=>'https://creativecommons.org/licenses/by/3.0/',
            'policy'=>'https://www.scidev.net/global/content/policies.html',
            'commercial_reuse'=>true,'adaptations'=>true,'reviewed'=>'2026-11-18','author_required'=>true,'language'=>'en',
            'credit'=>'Originally published by SciDev.Net.',
        ],
        'nasa'=>[
            'name'=>'NASA','feed'=>'https://www.nasa.gov/feed/',
            // Only NASA news releases; no APOD, partner sites or third-party image stories.
            'hosts'=>['www.nasa.gov'],'paths'=>['/news-release/'],
            'license'=>'NASA informational reuse','license_url'=>'https://www.nasa.gov/nasa-brand-center/images-and-media/',
            'policy'=>'https://www.nasa.gov/nasa-brand-center/images-and-media/',
            'commercial_reuse'=>true,'adaptations'=>true,'reviewed'=>'2026-11-18','author_required'=>false,'language'=>'en',
            'credit'=>'Based on a NASA news release. NASA does not endorse this summary.',
        ],
        'rijksoverheid'=>[
            'name'=>'Rijksoverheid','feed'=>'https://feeds.rijksoverheid.nl/nieuws.rss',
            'hosts'=>['www.rijksoverheid.nl'],'feed_hosts'=>['feeds.rijksoverheid.nl'],'paths'=>['/actueel/nieuws/'],
            'license'=>'CC0 1.0','license_url'=>'https://creativecommons.org/publicdomain/zero/1.0/',
            'policy'=>'https://www.rijksoverheid.nl/service/copyright',
            'commercial_reuse'=>true,'adaptations'=>true,'reviewed'=>'2026-11-18','author_required'=>false,'language'=>'nl',
            'credit'=>'Gebaseerd op informatie van Rijksoverheid. Deze samenvatting is niet door de bron goedgekeurd.',
        ],
        'utrecht'=>[
            'name'=>'Gemeente Utrecht','feed'=>'https://www.utrecht.nl/nieuws/rss.xml',
            'hosts'=>['www.utrecht.nl'],'paths'=>['/nieuws/nieuwsbericht-gemeente-utrecht/'],
            'license'=>'Teksthergebruik met bronvermelding','license_url'=>'https://www.utrecht.nl/over-deze-website/colofon',
            'policy'=>'https://www.utrecht.nl/over-deze-website/colofon',
            'commercial_reuse'=>true,'adaptations'=>true,'reviewed'=>'2026-11-18','author_required'=>false,'language'=>'nl',
            'places'=>['utrecht'],'regions'=>['utrecht'],
            'credit'=>'Bron: Gemeente Utrecht. Deze samenvatting is niet door de gemeente goedgekeurd.',
        ],
    ],
];
