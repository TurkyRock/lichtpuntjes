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
        write('weatherMax', temperature(day.max));
        ['10:00','14:00','20:00'].forEach((time, i) => {
            const hour = day.hours.find(item => item.time === time);
            const partIcon = page.querySelector('#weatherPart' + i + 'Icon');
            if (!partIcon) return;
            if (hour) paintIcon(partIcon, hour.code, time !== '20:00');
            else partIcon.textContent = '—';
            write('weatherPart' + i + 'Temp', temperature(hour?.temperature));
            write('weatherPart' + i + 'Sun', numeric(hour?.sun) ? number(hour.sun / 60) : '—');
            write('weatherPart' + i + 'Rain', number(hour?.rain_probability));
        });
        write('weatherRain', number(day.rain, 1) + ' mm');
        write('weatherOutfitTitle', index === 0 ? text('Wat doe ik vandaag aan?', 'What should I wear today?') : text('Wat trek ik deze dag aan?', 'What should I wear this day?'));
        const daytime = day.hours.filter(h => h.time >= '08:00' && h.time <= '20:00');
        const temperatures = daytime.map(h => h.temperature).filter(numeric);
        const low = temperatures.length ? Math.min(...temperatures) : day.min;
        const high = temperatures.length ? Math.max(...temperatures) : day.max;
        let outfit = !numeric(low) || !numeric(high) ? text('Nog te weinig gegevens voor kledingadvies. Bekijk de verwachting voordat je vertrekt.', 'Not enough data for clothing advice. Check the forecast before leaving.')
            : low < 5 ? text('Een warme jas, een trui en dichte schoenen. Neem een sjaal mee voor de frisse uren.', 'A warm coat, a jumper and closed shoes. Bring a scarf for the chilly hours.')
            : low < 12 ? text('Een jas met een trui of vest eronder. Met laagjes kun je onderweg iets uitdoen.', 'A jacket with a jumper or cardigan underneath. Layers let you adjust along the way.')
            : low < 18 ? text('Een lichte jas of vest over je shirt is fijn voor de koelere uren.', 'A light jacket or cardigan over your shirt works for the cooler hours.')
            : text('Luchtige kleding is fijn. Neem een dun vest mee als je later thuiskomt.', 'Light clothing is comfortable. Bring a thin cardigan if you are returning late.');
        if (numeric(day.rain) && day.rain >= 1) outfit += text(' Kies een waterdichte jas en schoenen die tegen een bui kunnen.', ' Choose a waterproof jacket and shoes that can handle a shower.');
        write('weatherOutfit', outfit);
        const localParts = Object.fromEntries(new Intl.DateTimeFormat('en-GB', {timeZone:'Europe/Amsterdam',year:'numeric',month:'2-digit',day:'2-digit',hour:'2-digit',hourCycle:'h23'}).formatToParts().map(p => [p.type,p.value]));
        const todayDate = localParts.year + '-' + localParts.month + '-' + localParts.day;
        const hourStart = time => String(Number(time.slice(0, 2)) - 1).padStart(2, '0') + ':00';
        const candidates = daytime.filter(hour => {
            const start = hourStart(hour.time);
            return (day.date !== todayDate || hour.time > localParts.hour + ':00')
                && day.sunrise && day.sunset && start >= day.sunrise && hour.time <= day.sunset
                && numeric(hour.rain_probability) && hour.rain_probability <= 30
                && numeric(hour.rain) && hour.rain <= 0.1 && numeric(hour.code) && hour.code < 51;
        });
        const best = candidates.sort((a,b) => a.rain_probability - b.rain_probability || (b.sun || 0) - (a.sun || 0))[0];
        const start = best ? hourStart(best.time) : '';
        write('weatherOutdoorTime', best ? text('Rond ' + start + '–' + best.time + ' lijkt het overwegend droog. Fijn voor een klein ommetje.', 'Around ' + start + '–' + best.time + ' looks mostly dry. A chance for a short walk.') : text('Nog geen duidelijk droog daglichtmoment in de resterende verwachting. Bekijk de uren of maak het binnen fijn met een mooi verhaal.', 'No clear dry daylight window in the remaining forecast. Check the hours or enjoy a lovely story indoors.'));

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
        page.querySelectorAll('#weatherWeek button').forEach((button, i) => button.setAttribute('aria-pressed', String(i === index)));
        const hours = page.querySelector('#weatherHours');
        hours.replaceChildren();
        const currentTime = day.date === todayDate ? localParts.hour + ':00' : null;
        for (const hour of day.hours) {
            const card = node('div', undefined, 'weather-hour');
            const time = node('strong', hour.time);
            if (hour.time === currentTime) {
                card.setAttribute('aria-current', 'time');
                time.textContent += text(' · Nu', ' · Now');
            }
            card.append(time, node('b', temperature(hour.temperature)), node('span', '☀ ' + (numeric(hour.sun) ? number(hour.sun / 60) : '—') + ' min'), node('span', '☂ ' + number(hour.rain_probability) + '%'), node('span', number(hour.rain, 1) + ' mm'));
            hours.append(card);
        }
        if (!day.hours.length) hours.append(node('p', text('Nog geen uurverwachting beschikbaar.', 'Hourly forecast not yet available.')));
        scrollToCurrentHour();
    }
    function scrollToCurrentHour() {
        const hours = page.querySelector('#weatherHours');
        const current = hours.querySelector('[aria-current="time"]');
        hours.scrollLeft = 0;
        if (current) hours.scrollLeft = current.getBoundingClientRect().left - hours.getBoundingClientRect().left;
    }
    page?.querySelector('#weatherHourlyDetails').addEventListener('toggle', event => {
        if (event.currentTarget.open) scrollToCurrentHour();
    });
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
        const updated = new Intl.DateTimeFormat(en() ? 'en-GB' : 'nl-NL', { day:'numeric', month:'short', hour:'2-digit', minute:'2-digit', timeZone:'Europe/Amsterdam' }).format(new Date(data.fetched_at * 1000));
        write('weatherUpdated', text('Opgehaald ', 'Fetched ') + updated);
        page.querySelector('#weatherContent').hidden = false;
        page.querySelector('#weatherForecastDetails').hidden = false;
        renderDay(0);
        page.querySelector('#weatherStatus').classList.toggle('weather-status-notice', Boolean(data.stale));
        write('weatherStatus', data.stale ? text('Je ziet de laatst opgehaalde verwachting. De ophaaltijd staat onderaan.', 'Showing the last forecast. The update time is at the bottom.') : '');
    }
    async function load() {
        const request = ++generation;
        const option = select?.selectedOptions[0];
        const place = locationPoint
            ? {...locationPoint, name:text('Jouw omgeving','Your area')}
            : option ? {latitude:option.dataset.latitude, longitude:option.dataset.longitude, name:option.textContent} : null;
        if (page) {
            page.querySelector('#weatherStatus').classList.remove('weather-status-notice');
            write('weatherStatus', forecast
                ? text('Nieuwe verwachting ophalen…', 'Fetching the new forecast…')
                : text('De weersverwachting wordt opgehaald…', 'Fetching the forecast…'));
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
            if (page) {
                if (place) page.dispatchEvent(new CustomEvent('weather:placechange', {detail: place}));
                render(data);
            }
        } catch {
            if (request !== generation) return;
            if (!forecast) updateLinks(null);
            page?.querySelector('#weatherStatus').classList.add('weather-status-notice');
            const retry = text('Ophalen mislukt. Probeer opnieuw met Toon.', 'Could not load the forecast. To retry, choose Show.');
            write('weatherStatus', forecast ? retry + text(' Je ziet nog het weer voor ', ' Still showing the forecast for ') + forecast.name + '.' : retry);
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
