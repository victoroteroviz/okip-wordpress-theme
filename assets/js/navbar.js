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

    // Primer hermano que sea un bloque OKIP (tiene data-block-instance). Deriva el
    // "bloque-cubierta" del orden real del DOM, no de un tipo concreto.
    function nextBlock(el) {
        if (!el) { return null; }
        var n = el.nextElementSibling;
        while (n && !n.hasAttribute('data-block-instance')) { n = n.nextElementSibling; }
        return n;
    }

    function init() {
        var navbar = document.querySelector('[data-okip-navbar]');
        if (!navbar) { return; }

        var toggle = navbar.querySelector('[data-okip-nav-toggle]');
        var nav = navbar.querySelector('[data-okip-nav]');

        var revealMode = navbar.getAttribute('data-reveal-mode') || 'after_hero';
        var offset = window.OKIP.readInt(navbar.getAttribute('data-reveal-offset'), 0);
        var hideOnHero = navbar.getAttribute('data-hide-on-hero') === '1';

        var hero = document.querySelector('[data-okip-hero]');
        // Bloque que cubre al Hero = el PRIMER bloque renderizado tras el Hero, derivado
        // del DOM/orden real (no un selector de tipo fijo). Así, si el admin reordena, el
        // navbar sigue al bloque correcto. Se revela cuando ese bloque sube y tapa al Hero,
        // no por la geometría del Hero (sticky → rect.top engañoso).
        var coverBlock = nextBlock(hero);
        var autoHide = (revealMode === 'after_hero') && hideOnHero && (!!coverBlock || !!hero);

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

            var REVEAL_RATIO = 0.15; // muestra el navbar cuando el bloque-cubierta tapa ~85%
            var docEl = document.documentElement;

            // Revelado forzado durante un snap de traspaso (evento okip:cover-snap, emitido
            // por OKIP.snapCover). El bloque siguiente se ancla BAJO el navbar, así que sin
            // esto la franja superior dejaría ver el Hero hasta que el navbar termine de
            // bajar (~400ms) → parpadeo. Mantenemos el navbar visible durante el snap.
            var forceShowUntil = 0;
            function nowMs() {
                return (window.performance && performance.now) ? performance.now() : Date.now();
            }
            document.addEventListener('okip:cover-snap', function (e) {
                var ms = (e.detail && e.detail.ms) || 700;
                forceShowUntil = nowMs() + ms + 120;
                show();
            });

            function setByPmCovered(covered) {
                if (covered) { show(); } else { hide(); }
            }

            // Si el bloque-cubierta emite el sync `okip:pm-cover` (legacy `parallax-monitor`),
            // lo seguimos para decidir en el mismo frame que el cover. `video-w-title` NO lo
            // emite → se usa la geometría del propio bloque (rect.top), barata y robusta.
            var hasPmSync = !!(coverBlock && coverBlock.hasAttribute('data-okip-pm'));
            if (hasPmSync) {
                document.addEventListener('okip:pm-cover', function (e) {
                    setByPmCovered(!!(e.detail && e.detail.covered));
                });
            }

            function scrollY() {
                return window.scrollY || document.documentElement.scrollTop || document.body.scrollTop || 0;
            }

            // Geometría del Hero cacheada (SOLO para el fallback sin bloque-cubierta). Se
            // recalcula en resize, no en cada frame de scroll → evita reflows forzados
            // (el recorrido por offsetParent es caro y fuerza layout sincrónico).
            var heroTopDoc = 0, heroH = 0;
            function measureHero() {
                if (!hero) { return; }
                var top = 0, el = hero;
                while (el) { top += el.offsetTop || 0; el = el.offsetParent; }
                heroTopDoc = top;
                heroH = hero.offsetHeight || 0;
            }

            var navTicking = false;
            var evalNav = function () {
                navTicking = false;

                // Durante un snap de traspaso, el navbar se mantiene visible (evita el
                // parpadeo de la franja superior mientras el bloque siguiente sube).
                if (nowMs() < forceShowUntil) { show(); return; }

                // Camino preferente: el bloque que cubre al Hero. UNA sola lectura de layout
                // (getBoundingClientRect) por frame; sin recorrer offsetParent.
                if (coverBlock) {
                    if (hasPmSync && docEl.classList.contains('is-pm-sync-ready')) {
                        setByPmCovered(docEl.classList.contains('is-pm-covered'));
                        return;
                    }
                    // Aparece cuando el bloque-cubierta ha subido y su top cae bajo el 15%
                    // superior del viewport (tapa ~85% del Hero), y se mantiene mientras lo cubra.
                    var rectTop = coverBlock.getBoundingClientRect().top;
                    if (rectTop <= window.innerHeight * REVEAL_RATIO - offset) { show(); } else { hide(); }
                    return;
                }

                // Fallback (Hero sin bloque-cubierta): progreso por scroll, con medidas cacheadas.
                if (!heroH || heroH <= 0) { measureHero(); }
                if (!heroH || heroH <= 0) { hide(); return; }
                var threshold = heroTopDoc + heroH * (1 - REVEAL_RATIO) - offset;
                if (scrollY() >= threshold) { show(); } else { hide(); }
            };
            var onNavScroll = function () {
                if (!navTicking) { navTicking = true; window.requestAnimationFrame(evalNav); }
            };
            var onNavResize = function () {
                measureHero();
                onNavScroll();
            };
            measureHero();
            evalNav();
            window.addEventListener('scroll', onNavScroll, { passive: true });
            window.addEventListener('resize', onNavResize, { passive: true });
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
