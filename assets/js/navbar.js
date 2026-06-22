/*
 * OKIP — navbar.
 *
 * Visibilidad:
 *   - reveal_mode = after_hero (Home con Hero): nace oculto; aparece cuando el
 *     usuario sale del Hero / llega al segundo bloque, y se oculta al volver.
 *     Detección con IntersectionObserver sobre el Hero; fallback por scrollY.
 *   - reveal_mode = always | manual, o páginas sin Hero: visible desde el inicio.
 *   - Al ocultarse con el menú móvil abierto, el menú se cierra (aria-expanded).
 *
 * Hamburguesa accesible (aria-expanded/controls, Escape, cierre al click).
 * Respeta prefers-reduced-motion (sin transición, pero sigue funcionando).
 */
(function () {
    'use strict';

    function init() {
        var navbar = document.querySelector('[data-okip-navbar]');
        if (!navbar) { return; }

        var toggle = navbar.querySelector('[data-okip-nav-toggle]');
        var nav = navbar.querySelector('[data-okip-nav]');

        var revealMode = navbar.getAttribute('data-reveal-mode') || 'after_hero';
        var offset = parseInt(navbar.getAttribute('data-reveal-offset'), 10) || 0;
        var hideOnHero = navbar.getAttribute('data-hide-on-hero') === '1';
        var useIO = navbar.getAttribute('data-use-io') === '1';

        var hero = document.querySelector('[data-okip-hero]');
        var autoHide = (revealMode === 'after_hero') && hideOnHero && !!hero;

        /* ---------- Mostrar / ocultar ---------- */
        function show() {
            navbar.classList.remove('is-hidden', 'okip-navbar--start-hidden');
        }
        function hide() {
            navbar.classList.add('is-hidden');
            navbar.classList.remove('okip-navbar--start-hidden');
            setMenu(false); // cerrar menú móvil si estaba abierto
        }

        if (autoHide) {
            // Estado inicial: oculto (dentro del Hero).
            navbar.classList.add('is-hidden');
            navbar.classList.remove('okip-navbar--start-hidden');

            if (useIO && 'IntersectionObserver' in window) {
                var io = new IntersectionObserver(function (entries) {
                    var e = entries[0];
                    var inHero = e.isIntersecting && e.intersectionRatio >= 0.5;
                    if (inHero) { hide(); } else { show(); }
                }, {
                    threshold: [0, 0.25, 0.5, 0.75, 1],
                    rootMargin: (-offset) + 'px 0px 0px 0px'
                });
                io.observe(hero);
            } else {
                var onScrollHide = function () {
                    var past = window.scrollY > (hero.offsetHeight * 0.6 - offset);
                    if (past) { show(); } else { hide(); }
                };
                onScrollHide();
                window.addEventListener('scroll', onScrollHide, { passive: true });
            }
        } else {
            show();
        }

        /* ---------- Hamburguesa ---------- */
        function setMenu(open) {
            if (!toggle || !nav) { return; }
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            toggle.setAttribute('aria-label', open ? 'Cerrar menú' : 'Abrir menú');
            nav.classList.toggle('is-open', open);
        }

        if (toggle && nav) {
            toggle.addEventListener('click', function () {
                setMenu(toggle.getAttribute('aria-expanded') !== 'true');
            });
            nav.addEventListener('click', function (e) {
                if (e.target.closest('a')) { setMenu(false); }
            });
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') { setMenu(false); }
            });
        }

        /* ---------- Realce al hacer scroll ---------- */
        var onScroll = function () {
            navbar.classList.toggle('is-scrolled', window.scrollY > 8);
        };
        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });
    }

    if (window.OKIP && window.OKIP.ready) {
        window.OKIP.ready(init);
    } else if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
