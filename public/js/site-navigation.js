(() => {
    const navigation = document.querySelector('.site-nav');
    const toggle = document.getElementById('siteMenuToggle');
    const links = document.getElementById('siteMenu');
    if (!navigation || !toggle || !links) return;

    // Follow the actual content width, including locally customized homepage widths.
    const header = navigation.closest('.site-header');
    const content = document.querySelector('.home-paper-board, #weatherPage');
    const alignToContent = () => {
        if (!header || !content) return;
        const bounds = content.getBoundingClientRect();
        const headerBounds = header.getBoundingClientRect();
        if (bounds.width <= 0) return;
        header.style.setProperty('--site-nav-width', bounds.width + 'px');
        header.style.setProperty('--site-nav-left', (bounds.left - headerBounds.left) + 'px');
        header.classList.add('site-content-aligned');
    };
    alignToContent();
    if (content && typeof ResizeObserver !== 'undefined') {
        new ResizeObserver(alignToContent).observe(content);
    }
    window.addEventListener('resize', alignToContent);

    const compact = window.matchMedia('(max-width: 767px)');
    const setExpanded = expanded => {
        toggle.setAttribute('aria-expanded', String(expanded));
        navigation.classList.toggle('is-open', expanded);
    };
    navigation.classList.add('nav-enhanced');
    toggle.hidden = false;
    setExpanded(!compact.matches);
    toggle.addEventListener('click', () => setExpanded(toggle.getAttribute('aria-expanded') !== 'true'));
    compact.addEventListener('change', () => setExpanded(!compact.matches));
    navigation.addEventListener('keydown', event => {
        if (event.key === 'Escape' && compact.matches && toggle.getAttribute('aria-expanded') === 'true') {
            setExpanded(false);
            toggle.focus();
        }
    });
    links.addEventListener('click', event => {
        if (compact.matches && event.target.closest('a')) {
            setExpanded(false);
            toggle.focus();
        }
    });
})();
