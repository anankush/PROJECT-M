(function () {
    const DURATION = 350;

    document.body.style.opacity = '0';
    document.body.style.transition = `opacity ${DURATION}ms ease`;
    requestAnimationFrame(() => {
        requestAnimationFrame(() => {
            document.body.style.opacity = '1';
        });
    });

    document.addEventListener('click', function (e) {
        const link = e.target.closest('a[href]');
        if (!link) return;

        const href = link.getAttribute('href');

        if (
            !href ||
            href.startsWith('http') ||
            href.startsWith('#') ||
            href.startsWith('javascript:') ||
            href.startsWith('mailto:') ||
            href.startsWith('tel:') ||
            link.target === '_blank' ||
            link.hasAttribute('download') ||
            e.ctrlKey || e.metaKey || e.shiftKey
        ) return;

        e.preventDefault();

        document.body.style.opacity = '0';
        setTimeout(() => {
            window.location.href = href;
        }, DURATION);
    });

    window.addEventListener('pageshow', function (e) {
        if (e.persisted) {
            document.body.style.opacity = '0';
            requestAnimationFrame(() => {
                document.body.style.opacity = '1';
            });
        }
    });
})();
