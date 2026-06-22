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

            // Umbral por PROGRESO de scroll (no solo IO): con la superposición del
            // Bloque 2, el Hero puede seguir intersectando aunque ya esté siendo
            // reemplazado. Aparece cuando la transición hacia el Bloque 2 supera
            // ~0.15 (es decir, pasado el 85% del Hero). Se oculta al volver bajo él.
            var pm = document.querySelector('[data-okip-pm]');
            var startProg = pm ? parseFloat(pm.getAttribute('data-overlap-start')) : 0.85;
            if (isNaN(startProg)) { startProg = 0.85; }
            var REVEAL_AT = 0.15; // progreso de transición para mostrar el navbar

            var navTicking = false;
            var evalNav = function () {
                navTicking = false;
                var h = hero.offsetHeight;
                // Guard: si el Hero aún no tiene altura (layout no listo), mantener
                // OCULTO. Nunca mostrar por una medida inválida.
                if (!h || h <= 0) { hide(); return; }
                var rect = hero.getBoundingClientRect();
                var topDoc = rect.top + window.scrollY;
                var start = topDoc + h * startProg - offset;
                var end = topDoc + h;
                var p = (end <= start) ? (window.scrollY >= start ? 1 : 0)
                    : Math.max(0, Math.min(1, (window.scrollY - start) / (end - start)));
                if (p >= REVEAL_AT) { show(); } else { hide(); }
            };
            var onNavScroll = function () {
                if (!navTicking) { navTicking = true; window.requestAnimationFrame(evalNav); }
            };
            evalNav();
            window.addEventListener('scroll', onNavScroll, { passive: true });
            window.addEventListener('resize', onNavScroll, { passive: true });
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
