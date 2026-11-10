(() => {
    'use strict';
    const names = { spring: ['Lente', 'Spring'], summer: ['Zomer', 'Summer'], autumn: ['Herfst', 'Autumn'], winter: ['Winter', 'Winter'] };
    function seasonAt(date = new Date()) {
        const month = Number(new Intl.DateTimeFormat('en', { month: 'numeric', timeZone: 'Europe/Amsterdam' }).format(date));
        return month >= 3 && month <= 5 ? 'spring' : month >= 6 && month <= 8 ? 'summer' : month >= 9 && month <= 11 ? 'autumn' : 'winter';
    }
    if (typeof module !== 'undefined' && module.exports) { module.exports = { seasonAt }; return; }
    const page = document.getElementById('weatherPage');
    if (!page) return;
    function apply() {
        const season = page.dataset.seasonPreview === 'true' && names[page.dataset.season] ? page.dataset.season : seasonAt();
        const changed = page.dataset.season !== season;
        for (const name of Object.keys(names)) page.classList.toggle('weather-season-' + name, name === season);
        page.dataset.season = season;
        const english = document.documentElement.lang === 'en';
        page.querySelector('#weatherSeasonLabel').textContent = names[season][english ? 1 : 0];
        const photo = page.querySelector('#weatherNaturePhoto');
        const nextPhoto = '/images/weather/' + season + '-nature.png';
        if (photo.getAttribute('src') !== nextPhoto) photo.src = nextPhoto;
        const descriptions = {
            spring: ['Bloesem en een groen pad in de lente', 'Blossom and a green path in spring'],
            summer: ['Een zonnige bloemenweide bij het water', 'A sunny wildflower meadow by the water'],
            autumn: ['Een bospad tussen gouden herfstbomen', 'A woodland path among golden autumn trees'],
            winter: ['Een besneeuwd bospad in het winterlicht', 'A snowy woodland path in winter light'],
        };
        photo.alt = descriptions[season][english ? 1 : 0];
        for (const image of page.querySelectorAll('[data-season-art]')) {
            const next = '/images/weather/' + season + '-art.png';
            if (image.getAttribute('src') !== next) image.src = next;
        }
        if (changed) page.dispatchEvent(new CustomEvent('weather:seasonchange'));
    }
    apply();
    const timer = setInterval(apply, 60000);
    document.addEventListener('visibilitychange', () => { if (!document.hidden) apply(); });
    window.addEventListener('pagehide', () => clearInterval(timer), { once: true });
})();
