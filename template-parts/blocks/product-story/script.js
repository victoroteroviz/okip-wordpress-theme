/*
 * Bloque Product Story (Bloque 4) — reveal por tarjeta + tap del cover.
 * Scope por instancia via [data-okip-ps].
 *
 * Dos responsabilidades:
 *   1) Tap/teclado del cover: el cover es un <button>; al activarlo (tap en touch,
 *      Enter/Espacio con teclado) alterna `is-open` en la tarjeta y descubre la capa
 *      de fondo. En desktop el hover lo descubre por CSS (sin JS). Funciona en TODOS
 *      los modos (incluido is-static) para que el tap móvil siempre revele.
 *   2) Reveal al scroll: con GSAP+ScrollTrigger (desktop) cada tarjeta recibe
 *      `is-revealed` al entrar (fade/slide-up por CSS, NO typewriter). Sin GSAP pero
 *      desktop: IO añade `is-revealed`. Móvil/tablet ≤disable_below, reduce-motion o
 *      anim off: `is-static`, todo legible.
 *
 * El root nunca se transforma (regla del proyecto): se animan las tarjetas. Los
 * estados ocultos viven SOLO con JS + animado (CSS), así que si GSAP falla el texto
 * nunca queda invisible.
 */
(function () {
    'use strict';

    var OKIP = window.OKIP;

    var reduceMotion = (OKIP && typeof OKIP.reduceMotion === 'boolean')
        ? OKIP.reduceMotion
        : !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);

    function stReady() {
        return !!(window.okipGsap && window.okipGsap.ready && window.gsap &&
                  window.okipGsap.hasScrollTrigger && window.ScrollTrigger);
    }

    function toArray(nodes) {
        return (OKIP && OKIP.toArray) ? OKIP.toArray(nodes) : Array.prototype.slice.call(nodes);
    }

    /* Tap/teclado del cover: alterna `is-open` y aria-expanded. */
    function setupCoverToggle(section) {
        var covers = toArray(section.querySelectorAll('[data-okip-ps-cover]'));
        covers.forEach(function (cover) {
            var card = cover.closest('.okip-ps__card');
            if (!card) { return; }
            cover.addEventListener('click', function () {
                var open = card.classList.toggle('is-open');
                cover.setAttribute('aria-expanded', open ? 'true' : 'false');
            });
        });
    }

    /* Marca una tarjeta como revelada (idempotente). */
    function reveal(card) {
        card.classList.add('is-revealed');
    }

    function revealAll(section) {
        toArray(section.querySelectorAll('.okip-ps__card')).forEach(reveal);
    }

    function initPs(section) {
        if (section.__okipPsInit) { return; }
        section.__okipPsInit = true;

        var d            = section.dataset;
        var animOn       = d.anim       === '1';
        var useGsap      = d.useGsap    === '1';
        var useVanilla   = d.useVanilla === '1';
        var disableBelow = (OKIP && OKIP.readInt) ? OKIP.readInt(d.disableBelow, 1024) : 1024;
        var revealMode   = d.reveal || 'fade-up';

        var cards   = toArray(section.querySelectorAll('.okip-ps__card'));
        var isSmall = !!(window.matchMedia && window.matchMedia('(max-width: ' + disableBelow + 'px)').matches);

        // El tap del cover está disponible en todos los modos.
        setupCoverToggle(section);

        var canAnimate = animOn && !reduceMotion && !isSmall;

        /* ---- Modo estático: móvil, reduce-motion o animación apagada ---- */
        if (!canAnimate) {
            section.classList.add('is-static');
            return;
        }

        /* ---- reveal: none → mostrar todo sin animar ---- */
        if (revealMode === 'none') {
            revealAll(section);
            attachResizeStatic(section, disableBelow);
            return;
        }

        /* ---- GSAP + ScrollTrigger (desktop): reveal por tarjeta ---- */
        if (stReady() && useGsap) {
            var ST = window.ScrollTrigger;
            var psId = section.id || d.blockInstance || 'ps';
            var triggerIds = [];
            cards.forEach(function (card, i) {
                var id = psId + '-card-' + i;
                triggerIds.push(id);
                ST.create({
                    id: id,
                    trigger: card,
                    start: 'top 85%',
                    once: true,
                    onEnter: function () { reveal(card); }
                });
            });
            attachResizeDismount(section, cards, disableBelow, triggerIds);
            return;
        }

        /* ---- Fallback vanilla (desktop, sin GSAP): IO añade is-revealed ---- */
        if (useVanilla && 'IntersectionObserver' in window) {
            var io = new IntersectionObserver(function (entries) {
                entries.forEach(function (e) {
                    if (e.isIntersecting) {
                        reveal(e.target);
                        io.unobserve(e.target);
                    }
                });
            }, { threshold: 0.18, rootMargin: '0px 0px -10% 0px' });
            cards.forEach(function (card) { io.observe(card); });
        } else {
            // Sin IO ni vanilla: no dejar nada oculto.
            section.classList.add('is-static');
        }
        attachResizeStatic(section, disableBelow);
    }

    /* Si la ventana encoge bajo el breakpoint: matar ST y dejar todo legible. */
    function attachResizeDismount(section, cards, disableBelow, triggerIds) {
        var rt;
        window.addEventListener('resize', function () {
            window.clearTimeout(rt);
            rt = window.setTimeout(function () {
                var nowSmall = !!(window.matchMedia && window.matchMedia('(max-width: ' + disableBelow + 'px)').matches);
                if (nowSmall && window.ScrollTrigger) {
                    window.ScrollTrigger.getAll().forEach(function (st) {
                        if (st.vars && st.vars.id && triggerIds.indexOf(String(st.vars.id)) !== -1) {
                            st.kill();
                        }
                    });
                    cards.forEach(reveal);
                    section.classList.add('is-static');
                }
            }, 200);
        }, { passive: true });
    }

    /* Garantiza legibilidad si la ventana encoge bajo el breakpoint (rama vanilla). */
    function attachResizeStatic(section, disableBelow) {
        var rt;
        window.addEventListener('resize', function () {
            window.clearTimeout(rt);
            rt = window.setTimeout(function () {
                var nowSmall = !!(window.matchMedia && window.matchMedia('(max-width: ' + disableBelow + 'px)').matches);
                if (nowSmall) { section.classList.add('is-static'); }
            }, 200);
        }, { passive: true });
    }

    function init() {
        document.querySelectorAll('[data-okip-ps]').forEach(initPs);
    }

    if (window.OKIP && window.OKIP.ready) {
        window.OKIP.ready(init);
    } else if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
