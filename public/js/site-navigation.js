(() => {
    const navigation = document.querySelector('.site-nav');
    const toggle = document.getElementById('siteMenuToggle');
    const links = document.getElementById('siteMenu');
    if (!navigation || !toggle || !links) return;

    const compact = window.matchMedia('(max-width: 1100px)');
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
