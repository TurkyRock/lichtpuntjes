(() => {
    'use strict';
    const page = document.getElementById('weatherPage');
    const links = [...document.querySelectorAll('[data-weather-link]')];
    if (!page && !links.length) return;
    const select = page?.querySelector('#weatherCity');
    const allowed = select ? [...select.options].map(option => option.value) : ['arnhem','apeldoorn','deventer','amsterdam','rotterdam','utrecht','groningen','leeuwarden','enschede','eindhoven','maastricht','middelburg','zwolle'];
    const en = () => document.documentElement.lang === 'en';
    const text = (nl, english) => en() ? english : nl;
    const node = (tag, value, className) => {
        const item = document.createElement(tag);
        if (value !== undefined) item.textContent = value;
        if (className) item.className = className;
        return item;
    };
    const numeric = value => typeof value === 'number' && Number.isFinite(value);
    const number = (value, decimals = 0) => numeric(value) ? new Intl.NumberFormat(en() ? 'en-GB' : 'nl-NL', { maximumFractionDigits: decimals }).format(value) : '—';
    const temperature = value => number(value) + '°';
    const duration = seconds => {
        if (!numeric(seconds)) return '—';
        const minutes = Math.max(0, Math.round(seconds / 60));
        return Math.floor(minutes / 60) + (en() ? 'h ' : 'u ') + String(minutes % 60).padStart(2, '0');
    };
    const symbol = (code, isDay = true) => {
        if (!numeric(code)) return '☀';
        if (code >= 95) return '⛈';
        if (code >= 71 && code <= 77 || code >= 85 && code <= 86) return '❄';
        if (code >= 51) return '☂';
        if (code >= 3) return '☁';
        return isDay ? (code === 0 ? '☀' : '⛅') : '☾';
    };
    const iconName = (code, isDay = true) => {
        if (!numeric(code)) return 'cloud';
        if (code >= 95) return 'storm';
        if (code >= 71 && code <= 77 || code >= 85 && code <= 86) return 'snow';
        if (code >= 51) return 'rain';
        if (code >= 3) return 'cloud';
        return !isDay ? 'moon' : code === 0 ? 'sun' : 'partly-cloudy';
    };
    function paintIcon(target, code, isDay = true) {
        const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        svg.setAttribute('viewBox', '0 0 32 32');
        svg.setAttribute('aria-hidden', 'true');
        svg.classList.add('weather-drawn-icon');
        const use = document.createElementNS('http://www.w3.org/2000/svg', 'use');
        use.setAttribute('href', '/images/weather/icons.svg#' + iconName(code, isDay));
        svg.append(use);
        target.replaceChildren(svg);
    }
    const savedCity = () => { try { return localStorage.getItem('lichtpunt_weather_city'); } catch { return null; } };
    const requested = new URLSearchParams(location.search).get('city');
    let city = [requested, savedCity(), page?.dataset.defaultCity, 'arnhem'].find(value => allowed.includes(value));
    let locationPoint = null;
    let locationAttempt = 0;
    const locateButton = page?.querySelector('#weatherLocate');
    let generation = 0;
    let forecast;
    let activeDay = 0;
    if (select) select.value = city;
    const write = (id, value) => { const target = page?.querySelector('#' + id); if (target) target.textContent = value; };
    function updateLinks(data) {
        for (const link of links) {
            const url = new URL(link.href, location.origin);
            if (locationPoint) url.searchParams.delete('city');
            else url.searchParams.set('city', city);
            link.href = url.pathname + url.search;
            link.querySelector('[data-weather-temperature]').textContent = temperature(data?.current?.temperature);
            link.querySelector('[data-weather-icon]').textContent = data ? symbol(data.current?.code, data.current?.is_day !== 0) : '☁';
            const label = data
                ? text('Weer in ', 'Weather in ') + data.name + ': ' + temperature(data.current?.temperature) + (data.stale ? text(' · eerder opgehaald', ' · previously fetched') : '')
                : text('Bekijk het weer · temperatuur tijdelijk niet beschikbaar', 'View the weather · temperature temporarily unavailable');
            link.setAttribute('aria-label', label);
            link.title = label;
        }
    }
    function renderDay(index) {
        activeDay = index;
        const day = forecast.days[index];
        const locale = en() ? 'en-GB' : 'nl-NL';
        const date = new Date(day.date + 'T12:00:00Z');
        write('weatherDate', forecast.name + ' · ' + new Intl.DateTimeFormat(locale, { weekday: 'long', day: 'numeric', month: 'long', timeZone: 'Europe/Amsterdam' }).format(date));
        const level = numeric(day.light_fraction) ? Math.min(3, Math.floor(day.light_fraction * 4)) : null;
        const labels = en() ? ['Cosy weather expected','A little sun expected','Nice and bright expected','Lots of sunshine expected'] : ['Knusweer verwacht','Af en toe zon verwacht','Lekker licht verwacht','Volop zon verwacht'];
        const levelLabel = level === null ? text('Zonverwachting nog niet beschikbaar', 'Sunshine forecast not yet available') : labels[level];
        write('weatherLevel', levelLabel);
        const needle = page.querySelector('#weatherNeedle');
        needle.hidden = level === null;
        if (level !== null) needle.style.transform = 'rotate(' + [315,45,225,135][level] + 'deg)';
        page.querySelector('#weatherDial').setAttribute('aria-label', text('Lichtwijzer: ', 'Light meter: ') + levelLabel + (level === null ? '' : ' · ' + number(day.light_fraction * 100) + text('% van de daglichttijd zon', '% of daylight with sunshine')));
        write('weatherMax', temperature(day.max));
        write('weatherSun', duration(day.sun));
        write('weatherLightDuration', duration(day.light));
        const fraction = numeric(day.light_fraction) ? Math.max(0, Math.min(1, day.light_fraction)) : null;
        const fill = page.querySelector('#weatherLightFill');
        fill.style.width = fraction === null ? '0%' : fraction * 100 + '%';
        const comparison = fraction === null
            ? text('Zonverwachting nog niet beschikbaar.', 'Sunshine forecast not yet available.')
            : number(fraction * 100) + text('% van de daglichttijd zonneschijn verwacht.', '% of daylight with sunshine expected.');
        write('weatherLightShare', comparison);
        page.querySelector('#weatherLightTrack').setAttribute('aria-label', duration(day.sun) + text(' zonneschijn bij ', ' sunshine in ') + duration(day.light) + text(' daglicht. ', ' daylight. ') + comparison);
        ['10:00','14:00','20:00'].forEach((time, i) => {
            const hour = day.hours.find(item => item.time === time);
            const partIcon = page.querySelector('#weatherPart' + i + 'Icon');
            if (hour) paintIcon(partIcon, hour.code, time !== '20:00');
            else partIcon.textContent = '—';
            write('weatherPart' + i + 'Temp', temperature(hour?.temperature));
            write('weatherPart' + i + 'Sun', numeric(hour?.sun) ? number(hour.sun / 60) : '—');
            write('weatherPart' + i + 'Rain', number(hour?.rain_probability));
        });
        write('weatherRain', number(day.rain, 1) + ' mm');
        write('weatherDaylight', text('Zon op ', 'Sunrise ') + (day.sunrise || '—') + ' · ' + text('zon onder ', 'sunset ') + (day.sunset || '—'));
        let message;
        if (numeric(day.rain) && day.rain >= 2 && numeric(day.max) && day.max < 12) message = text('Koud en nat buiten? Warme sokken aan, een dekentje erbij. Tijd om lekker tegen elkaar te kruipen.', 'Cold and wet outside? Time for warm socks, a blanket and someone to snuggle up with.');
        else if (numeric(day.rain) && day.rain >= 2) message = text('De planten krijgen een drankje. Jij verdient iets warms en een fijn verhaal.', 'The plants are getting a drink. You deserve something warm and a lovely story.');
        else if (level !== null && level >= 1) {
            const seasons = {spring: text('lentedag', 'spring day'), summer: text('zomerdag', 'summer day'), autumn: text('herfstdag', 'autumn day'), winter: text('winterdag', 'winter day')};
            const sun = level === 1 ? text('af en toe zon', 'a little sunshine') : level === 2 ? text('fijne zonnige momenten', 'lovely sunny moments') : text('volop zon', 'plenty of sunshine');
            message = text('Een ' + seasons[page.dataset.season || 'autumn'] + ' met ' + sun + '. Precies goed voor een oppepper in je dag!', 'A ' + seasons[page.dataset.season || 'autumn'] + ' with ' + sun + '. A little boost for your day!');
        }
        else message = text('De zon neemt wat rust. Een klein gelukje hoeft dat niet te doen.', 'The sun is taking a rest. Your little moments of joy do not have to.');
        write('weatherMessage', message);
        const season = page.dataset.season || 'autumn';
        const outside = {
            spring: text('Spot de eerste bloesem of een frisgroen blaadje. Een klein ommetje kan al iets moois opleveren.', 'Look for blossom or a fresh green leaf. A little walk can bring something lovely.'),
            summer: text('Een rustig ommetje of een kleine picknick. Zoek een fijn plekje en neem iets te drinken mee.', 'A quiet walk or a little picnic. Find a lovely spot and bring something to drink.'),
            autumn: text('Zoek het mooiste herfstblaadje op je route. De frisse lucht doet goed!', 'Look for the prettiest autumn leaf along your route. Fresh air feels good!'),
            winter: text('Even naar buiten voor frisse lucht. Warme jas aan, en daarna weer fijn naar binnen.', 'Step outside for a breath of fresh air. Wrap up warmly, then enjoy coming back inside.'),
        };
        write('weatherOutside', numeric(day.rain) && day.rain >= 2 ? text('Zoek in de uurverwachting een droog moment voor jouw kleine buitenplan.', 'Look for a dry moment in the hourly forecast for your little outdoor plan.') : outside[season]);
        write('weatherInside', season === 'summer'
            ? text('Zet een koel drankje klaar, lees iets leuks en geniet van het moment.', 'Pour a cool drink, read something lovely and enjoy the moment.')
            : season === 'spring' ? text('Zet iets fris op tafel, stuur iemand een lief berichtje en geniet van een rustig moment.', 'Put something fresh on the table, send someone a kind message and enjoy a quiet moment.')
            : text('Zet iets warms, lees iets leuks en maak het even fijn voor jezelf.', 'Make something warm, read something lovely and take a little time for yourself.'));
        page.querySelectorAll('#weatherWeek button').forEach((button, i) => button.setAttribute('aria-pressed', String(i === index)));
        const hours = page.querySelector('#weatherHours');
        hours.replaceChildren();
        for (const hour of day.hours) {
            const card = node('div', undefined, 'weather-hour');
            card.append(node('strong', hour.time), node('b', temperature(hour.temperature)), node('span', '☀ ' + (numeric(hour.sun) ? number(hour.sun / 60) : '—') + ' min'), node('span', '☂ ' + number(hour.rain_probability) + '%'), node('span', number(hour.rain, 1) + ' mm'));
            hours.append(card);
        }
        if (!day.hours.length) hours.append(node('p', text('Nog geen uurverwachting beschikbaar.', 'Hourly forecast not yet available.')));
        hours.scrollLeft = 0;
    }
    function render(data) {
        forecast = data;
        const week = page.querySelector('#weatherWeek');
        week.replaceChildren();
        data.days.forEach((day, index) => {
            const date = new Date(day.date + 'T12:00:00Z');
            const dayLabel = new Intl.DateTimeFormat(en() ? 'en-GB' : 'nl-NL', { weekday: 'short', day: 'numeric', month: 'short', timeZone: 'Europe/Amsterdam' }).format(date);
            const button = node('button');
            button.type = 'button';
            const summary = dayLabel + ': ' + temperature(day.max) + text(' maximum, ', ' maximum, ') + temperature(day.min) + text(' minimum, ', ' minimum, ') + duration(day.sun) + text(' zonneschijn, ', ' sunshine, ') + number(day.rain, 1) + text(' mm neerslag', ' mm precipitation');
            button.title = summary;
            button.setAttribute('aria-label', summary);
            button.setAttribute('aria-pressed', String(index === 0));
            const dayIcon = node('span', undefined, 'weather-day-icon');
            paintIcon(dayIcon, day.code);
            button.append(node('strong', new Intl.DateTimeFormat(en() ? 'en-GB' : 'nl-NL', {weekday:'short',timeZone:'Europe/Amsterdam'}).format(date).replace('.', '')), dayIcon, node('b', temperature(day.max)), node('span', temperature(day.min) + text(' minimum', ' minimum')), node('span', '☀ ' + duration(day.sun)), node('span', '☂ ' + number(day.rain, 1) + ' mm'));
            button.addEventListener('click', () => renderDay(index));
            week.append(button);
        });
        renderDay(0);
        const updated = new Intl.DateTimeFormat(en() ? 'en-GB' : 'nl-NL', { day:'numeric', month:'short', hour:'2-digit', minute:'2-digit', timeZone:'Europe/Amsterdam' }).format(new Date(data.fetched_at * 1000));
        write('weatherUpdated', text('Opgehaald ', 'Fetched ') + updated);
        page.querySelector('#weatherContent').hidden = false;
        page.querySelector('#weatherStatus').classList.toggle('weather-status-notice', Boolean(data.stale));
        write('weatherStatus', data.stale ? text('De weerbron is tijdelijk niet bereikbaar. Je ziet de laatst opgehaalde verwachting; controleer de ophaaltijd onderaan.', 'The weather provider is temporarily unavailable. Showing the last forecast; check the fetch time below.') : text('Verwachting voor ', 'Forecast for ') + data.name + text(' · tijden in Nederland.', ' · Netherlands local time.'));
    }
    async function load() {
        const request = ++generation;
        if (page) {
            page.querySelector('#weatherContent').hidden = true;
            page.querySelector('#weatherStatus').classList.remove('weather-status-notice');
            write('weatherStatus', text('De weersverwachting wordt opgehaald…', 'Fetching the forecast…'));
        }
        const controller = new AbortController();
        const timer = setTimeout(() => controller.abort(), 25000);
        try {
            const response = await fetch('/weer/data?' + (locationPoint ? new URLSearchParams(locationPoint).toString() : 'city=' + encodeURIComponent(city)), { headers: { Accept: 'application/json' }, signal: controller.signal, credentials: 'same-origin' });
            if (!response.ok) throw new Error('Weather unavailable');
            const data = await response.json();
            if (!Array.isArray(data.days) || !data.days.length) throw new Error('Invalid weather response');
            if (request !== generation) return;
            updateLinks(data);
            if (page) render(data);
        } catch {
            if (request !== generation) return;
            updateLinks(null);
            page?.querySelector('#weatherStatus').classList.add('weather-status-notice');
            write('weatherStatus', text('Het weer is even niet beschikbaar. Probeer het straks opnieuw met de knop Toon. Ook zonder voorspelling is er een lichtpuntje te vinden.', 'The forecast is temporarily unavailable. Try again using Show. There is a bright side even without a forecast.'));
        } finally { clearTimeout(timer); }
    }
    page?.querySelector('.weather-location').addEventListener('submit', event => {
        event.preventDefault();
        if (select.value === 'device') { load(); return; }
        locationAttempt++;
        if (locateButton) locateButton.disabled = false;
        write('weatherLocationFeedback', '');
        locationPoint = null;
        city = select.value;
        try { localStorage.setItem('lichtpunt_weather_city', city); } catch { /* browsing without storage still works */ }
        const url = new URL(location.href);
        url.searchParams.set('city', city);
        history.replaceState(null, '', url.pathname + url.search + url.hash);
        load();
    });
    locateButton?.addEventListener('click', () => {
        const attempt = ++locationAttempt;
        const feedback = message => write('weatherLocationFeedback', message);
        if (!navigator.geolocation || !window.isSecureContext) {
            feedback(text('Locatie bepalen is hier niet beschikbaar. Kies zelf een plaats.', 'Location is unavailable here. Please choose a place.'));
            return;
        }
        locateButton.disabled = true;
        feedback(text('Je locatie wordt bepaald…', 'Finding your location…'));
        navigator.geolocation.getCurrentPosition(position => {
            if (attempt !== locationAttempt) return;
            locateButton.disabled = false;
            const latitude = position.coords.latitude;
            const longitude = position.coords.longitude;
            if (!numeric(latitude) || !numeric(longitude) || Math.abs(latitude) > 90 || Math.abs(longitude) > 180) {
                feedback(text('Je locatie kon niet worden bepaald. Kies zelf een plaats.', 'Your location could not be found. Please choose a place.'));
                return;
            }
            locationPoint = {latitude: latitude.toFixed(2), longitude: longitude.toFixed(2)};
            let option = select.querySelector('option[value="device"]');
            if (!option) { option = node('option', text('Jouw omgeving', 'Your area')); option.value = 'device'; select.append(option); }
            select.value = 'device';
            feedback('');
            // Coordinates stay out of the page URL and browser storage.
            const url = new URL(location.href); url.searchParams.delete('city');
            history.replaceState(null, '', url.pathname + url.search + url.hash);
            load();
        }, error => {
            if (attempt !== locationAttempt) return;
            locateButton.disabled = false;
            feedback(error.code === 1
                ? text('Geen locatietoegang. Je kunt zelf een plaats kiezen.', 'Location permission was not granted. You can choose a place yourself.')
                : text('Je locatie kon niet worden bepaald. Probeer opnieuw of kies een plaats.', 'Your location could not be found. Try again or choose a place.'));
        }, {enableHighAccuracy:false, timeout:12000, maximumAge:300000});
    });
    select?.addEventListener('change', () => page.querySelector('.weather-location').requestSubmit());
    page?.addEventListener('weather:seasonchange', () => { if (forecast) renderDay(activeDay); });
    load();
})();
