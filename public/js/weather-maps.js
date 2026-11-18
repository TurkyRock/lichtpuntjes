(() => {
    'use strict';
    const page = document.getElementById('weatherPage');
    if (!page) return;
    const cards = [...page.querySelectorAll('[data-weather-map]')];
    const select = page.querySelector('#weatherCity');
    const en = document.documentElement.lang === 'en';
    let place;
    const urls = kind => {
        const params = new URLSearchParams({p: `${place.latitude};${place.longitude};6`, l: kind === 'sun' ? 'clouds-total' : 'radar', pin: `${place.latitude};${place.longitude};dot;${place.name}`});
        return {embed:'https://embed.ventusky.com/?'+params, external:'https://www.ventusky.com/?'+params};
    };
    function open(card) {
        if (!place) return;
        const viewport = card.querySelector('.weather-map-viewport');
        let frame = viewport.querySelector('iframe');
        if (!frame) {
            frame = document.createElement('iframe');
            frame.title = card.querySelector('h2').textContent.trim();
            frame.referrerPolicy = 'no-referrer';
            frame.setAttribute('sandbox', 'allow-scripts allow-same-origin allow-popups allow-popups-to-escape-sandbox');
            frame.setAttribute('allow', 'geolocation \'none\'; camera \'none\'; microphone \'none\'');
            frame.addEventListener('load', () => {
                card.querySelector('[data-map-status]').textContent = en ? 'Use the time and legend inside the map. No image? Open Ventusky using the link.' : 'Gebruik het tijdstip en de legenda in de kaart. Geen beeld? Open Ventusky via de link.';
            });
            viewport.append(frame);
        }
        frame.src = urls(card.dataset.weatherMap).embed;
        card.querySelector('.weather-map-intro').hidden = true;
        card.querySelector('[data-map-close]').hidden = false;
        card.querySelector('[data-map-status]').textContent = en ? 'Opening the external map… You can also use the Ventusky link.' : 'Externe kaart wordt geopend… Je kunt ook de Ventusky-link gebruiken.';
    }
    function update(detail) {
        if (!detail || !Number.isFinite(Number(detail.latitude)) || !Number.isFinite(Number(detail.longitude)) || Math.abs(Number(detail.latitude)) > 90 || Math.abs(Number(detail.longitude)) > 180) return;
        place = {latitude:Number(detail.latitude).toFixed(2), longitude:Number(detail.longitude).toFixed(2), name: String(detail.name || (en?'Your area':'Jouw omgeving'))};
        for (const card of cards) {
            card.querySelector('[data-map-place]').textContent = place.name;
            card.querySelector('[data-map-external]').href = urls(card.dataset.weatherMap).external;
            if (card.querySelector('iframe')) open(card);
        }
    }
    for (const card of cards) {
        card.querySelector('[data-map-open]').addEventListener('click', () => open(card));
        card.querySelector('[data-map-close]').addEventListener('click', () => {
            card.querySelector('iframe')?.remove();
            card.querySelector('.weather-map-intro').hidden = false;
            card.querySelector('[data-map-close]').hidden = true;
            card.querySelector('[data-map-status]').textContent = '';
        });
    }
    const option = select?.selectedOptions[0];
    if (option) update({latitude:option.dataset.latitude,longitude:option.dataset.longitude,name:option.textContent});
    page.addEventListener('weather:placechange', event => update(event.detail));
})();
