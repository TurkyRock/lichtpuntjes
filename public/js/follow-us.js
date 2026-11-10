(() => {
    const trigger = document.getElementById('openFollowUs');
    const dialog = document.getElementById('followUsDialog');
    if (!trigger || !dialog) return;
    trigger.addEventListener('click', () => {
        const language = document.documentElement.lang === 'en' ? 'en' : 'nl';
        for (const label of document.querySelectorAll('[data-follow-nl]')) label.textContent = label.getAttribute('data-follow-' + language);
        dialog.showModal();
    });
    dialog.querySelector('[data-follow-close]').addEventListener('click', () => dialog.close());
    dialog.addEventListener('click', event => {
        if (event.target !== dialog) return;
        const bounds = dialog.getBoundingClientRect();
        if (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom) dialog.close();
    });
    dialog.addEventListener('close', () => trigger.focus());
})();
