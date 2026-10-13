/* First-party consent gate: Google is not contacted before explicit permission. */
(() => {
    'use strict';
    const config = window.lichtpuntAnalyticsConfig;
    if (!config || !/^G-[A-Z0-9]{4,20}$/.test(config.id) || window.location.pathname !== '/') return;
    const id = config.id, disableKey = 'ga-disable-' + id;
    const lifetime = 180 * 24 * 60 * 60;
    let allowed = config.allowed === true, expires = Number(config.expires) || 0, loaded = false;
    window[disableKey] = true;
    const channel = typeof window.BroadcastChannel === 'function' ? new window.BroadcastChannel('lichtpunt-statistics') : null;
    if (channel) channel.onmessage = event => { if (event.data?.allowed === false) stop(); };

    function language() { return document.documentElement.lang === 'en' ? 'en' : 'nl'; }
    function safeReferrer() {
        try { const url = new URL(document.referrer); return ['https:', 'http:'].includes(url.protocol) ? url.origin + '/' : ''; }
        catch (_) { return ''; }
    }
    function pageFields() {
        // Never send query strings, hashes, personal page titles or private referral paths.
        return {page_location: window.location.origin + '/', page_title: 'Lichtpuntjes', page_referrer: safeReferrer(), language: language(), send_to: id};
    }
    function permitted() { return allowed && Date.now() < expires; }
    function gtag() { window.dataLayer.push(arguments); }
    function stop() {
        const wasAllowed = allowed;
        allowed = false;
        window[disableKey] = true;
        if (loaded && wasAllowed) {
            // Unload the library and its listeners instead of sending denied-consent pings.
            window.location.reload();
        }
    }
    function start() {
        if (!permitted() || loaded) return;
        loaded = true;
        window[disableKey] = false;
        window.dataLayer = window.dataLayer || [];
        gtag('consent', 'default', {
            analytics_storage: 'granted', ad_storage: 'denied', ad_user_data: 'denied', ad_personalization: 'denied'
        });
        gtag('set', 'ads_data_redaction', true);
        gtag('js', new Date());
        gtag('config', id, {...pageFields(), send_page_view: false,
            allow_google_signals: false, allow_ad_personalization_signals: false,
            cookie_domain: 'none', cookie_path: '/', cookie_expires: lifetime, cookie_update: false,
            cookie_flags: window.location.protocol === 'https:' ? 'SameSite=Lax;Secure' : 'SameSite=Lax'
        });
        gtag('event', 'page_view', pageFields());
        const script = document.createElement('script');
        script.async = true;
        script.nonce = config.nonce;
        script.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(id);
        document.head.appendChild(script);
    }
    document.addEventListener('lichtpunt:statistics-consent', event => {
        if (event.detail?.allowed !== true) { if(channel)channel.postMessage({allowed:false});stop(); return; }
        allowed = true; expires = Date.now() + lifetime * 1000; start();
    });
    document.addEventListener('lichtpunt:article-open', event => {
        if (!permitted() || !loaded) return;
        const detail = event.detail;
        const categories = ['community', 'media', 'sport', 'energy', 'nature', 'science', 'people'];
        if (!detail || !/^\d{1,15}$/.test(String(detail.id)) || !categories.includes(detail.category)) return;
        gtag('event', 'article_view', {...pageFields(), article_id: String(detail.id), article_category: detail.category});
    });
    // Expired permission must also stop measurements in a tab left open for a long time.
    setInterval(() => { if (allowed && !permitted()) stop(); }, 60000);
    start();
})();
