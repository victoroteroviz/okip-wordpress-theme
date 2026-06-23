/*
 * OKIP — navbar.
 *
 * Visibilidad:
 *   - reveal_mode = after_hero (Home con Hero + Bloque 2): nace oculto; aparece
 *     cuando el Bloque 2 queda completamente expuesto, y se oculta al volver.
 *     Fallback por Hero solo si no existe Bloque 2.
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
        var pm = document.querySelector('[data-okip-pm]');
        var autoHide = (revealMode === 'after_hero') && hideOnHero && (!!pm || !!hero);

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

            // Umbral principal: el Bloque 2 debe llegar al top del viewport.
            // Esto evita que el navbar aparezca mientras B2 solo cubre parcialmente
            // al Hero. Si no hay Bloque 2, se usa el cálculo estable por Hero.
            var startProg = pm ? parseFloat(pm.getAttribute('data-overlap-start')) : 0.85;
            if (isNaN(startProg)) { startProg = 0.85; }
            var REVEAL_AT = 0.15; // progreso de transición para mostrar el navbar

            function docTop(el) {
                var top = 0;
                while (el) {
                    top += el.offsetTop || 0;
                    el = el.offsetParent;
                }
                return top;
            }
            function scrollY() {
                return window.scrollY || document.documentElement.scrollTop || document.body.scrollTop || 0;
            }

            var navTicking = false;
            var evalNav = function () {
                navTicking = false;

                if (pm) {
                    if (pm.getBoundingClientRect().top <= 1) { show(); } else { hide(); }
                    return;
                }

                var y = scrollY();

                var h = hero.offsetHeight;
                // Guard: si el Hero aún no tiene altura (layout no listo), mantener
                // OCULTO. Nunca mostrar por una medida inválida.
                if (!h || h <= 0) { hide(); return; }
                // Posición por layout, no por getBoundingClientRect(): sticky mantiene
                // rect.top en 0 mientras el scroll real sigue avanzando.
                var topDoc = docTop(hero);
                var start = topDoc + h * startProg - offset;
                var end = topDoc + h;
                var p = (end <= start) ? (y >= start ? 1 : 0)
                    : Math.max(0, Math.min(1, (y - start) / (end - start)));
                var visibleByProgress = p >= REVEAL_AT;
                var visibleByFallback = y >= (topDoc + h * 0.85 - offset);
                if (visibleByProgress || visibleByFallback) { show(); } else { hide(); }
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
