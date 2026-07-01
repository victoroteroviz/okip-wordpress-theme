/*
 * OKIP — core JS global.
 * Utilidades mínimas compartidas. Se carga siempre, antes que gsap-init.
 */
(function () {
    'use strict';

    window.OKIP = window.OKIP || {};

    OKIP.reduceMotion = !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);

    OKIP.ready = function (fn) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fn);
        } else {
            fn();
        }
    };

    // Breakpoints compartidos (espejo de los @media del tema; ver tokens.css).
    OKIP.breakpoints = { mobile: 768, tablet: 1024 };

    // NodeList/HTMLCollection → Array real (para .map/.forEach/.filter sin sorpresas).
    OKIP.toArray = function (list) {
        return list ? Array.prototype.slice.call(list) : [];
    };

    // Limita value al rango [min, max].
    OKIP.clamp = function (value, min, max) {
        return value < min ? min : (value > max ? max : value);
    };

    // parseInt con fallback EXPLÍCITO (evita el bug del idioma "or-cero" con índice 0).
    OKIP.readInt = function (value, fallback) {
        var n = parseInt(value, 10);
        return isNaN(n) ? fallback : n;
    };

    // parseFloat con fallback explícito.
    OKIP.readFloat = function (value, fallback) {
        var n = parseFloat(value);
        return isNaN(n) ? fallback : n;
    };

    // Throttle por requestAnimationFrame: coalesce llamadas a 1 por frame.
    OKIP.rafThrottle = function (fn) {
        var scheduled = false;
        var lastArgs = null;
        return function () {
            lastArgs = arguments;
            if (scheduled) { return; }
            scheduled = true;
            window.requestAnimationFrame(function () {
                scheduled = false;
                fn.apply(null, lastArgs);
            });
        };
    };

    /*
     * Snap de traspaso entre bloques (scroll-jack). Compartido por los bloques que
     * quedan `position:sticky` y son cubiertos por el bloque siguiente (Hero,
     * video-w-title…): un pequeño giro de rueda hace que el bloque SIGUIENTE (primer
     * hermano con [data-block-instance]) cubra por completo a `section` con una
     * animación suave, en vez del scroll gradual largo.
     *
     * Decisiones (aprendidas a golpes):
     *  - SOLO hacia adelante. El scroll inverso se deja NATIVO → control total del
     *    ratón, sin delays ni fallas visuales encadenadas (el scroll-jack en reversa
     *    "yanqueaba" de vuelta y hacía parpadear el fondo).
     *  - SOLO en la BANDA de traspaso: el borde superior del bloque siguiente entre el
     *    navbar (cubre el 100%) y el viewport (section completo). Fuera de la banda no
     *    se intercepta → el `hold` del bloque siguiente scrollea normal.
     *  - Solo desktop ≥1025px (lockstep con el sticky-cover) y sin reduce-motion. Si
     *    no aplica, queda el traspaso por scroll normal (degradación limpia).
     *  - Emite `okip:cover-snap` al iniciar → el navbar se revela a tiempo (sin dejar
     *    ver la franja del Hero sobre el bloque anclado bajo el navbar).
     *
     * @param {HTMLElement} section  Bloque sticky que será cubierto.
     * @param {Object}      options  { enabled:bool, duration:int(ms) }
     */
    OKIP.snapCover = function (section, options) {
        options = options || {};
        if (options.enabled === false) { return; }
        if (!section || section.__okipSnapCoverInit) { return; }
        if (OKIP.reduceMotion) { return; }
        if (!window.matchMedia || !window.matchMedia('(min-width: 1025px)').matches) { return; }

        // Primer hermano posterior que sea un bloque (el que cubrirá a `section`).
        var next = section.nextElementSibling;
        while (next && !(next.hasAttribute && next.hasAttribute('data-block-instance'))) {
            next = next.nextElementSibling;
        }
        if (!next) { return; }
        section.__okipSnapCoverInit = true;

        var duration = OKIP.clamp(OKIP.readInt(options.duration, 700), 150, 3000);
        var EPS = 2;
        var animating = false;

        function navbarH() {
            var v = getComputedStyle(document.documentElement).getPropertyValue('--okip-navbar-h');
            var n = parseFloat(v);
            return isNaN(n) ? 0 : n;
        }
        function easeInOutCubic(p) {
            return p < 0.5 ? 4 * p * p * p : 1 - Math.pow(-2 * p + 2, 3) / 2;
        }
        function animateBy(delta) {
            if (Math.abs(delta) < EPS) { return; }
            var startY = window.pageYOffset;
            var t0 = null;
            animating = true;
            // CLAVE de fluidez: el CSS pone `html { scroll-behavior: smooth }` (base.css).
            // Con eso, cada window.scrollTo del rAF dispararía un smooth-scroll del navegador
            // que pelea con nuestro easing (rubber-band/jank). Lo anulamos a `auto` durante
            // la animación (inline gana al stylesheet) y lo restauramos al terminar → cada
            // frame es instantáneo y manda nuestra curva. (El smooth de anclas sigue intacto.)
            var docEl = document.documentElement;
            var prevBehavior = docEl.style.scrollBehavior;
            docEl.style.scrollBehavior = 'auto';
            function step(ts) {
                if (t0 === null) { t0 = ts; }
                var p = Math.min(1, (ts - t0) / duration);
                window.scrollTo(0, startY + delta * easeInOutCubic(p));
                if (p < 1) {
                    window.requestAnimationFrame(step);
                } else {
                    docEl.style.scrollBehavior = prevBehavior; // restaurar el smooth global
                    animating = false;
                }
            }
            window.requestAnimationFrame(step);
        }

        window.addEventListener('wheel', function (e) {
            // Mientras anima, bloquea la rueda para que no pelee con el snap.
            if (animating) { e.preventDefault(); return; }
            if (e.deltaY <= 0) { return; } // inverso = nativo (control total)

            var top = next.getBoundingClientRect().top;
            var nav = navbarH();
            var vh = window.innerHeight;
            if (top > nav + EPS && top <= vh + EPS) {
                e.preventDefault();
                try {
                    document.dispatchEvent(new CustomEvent('okip:cover-snap', { detail: { ms: duration } }));
                } catch (err) {}
                animateBy(top - nav); // un gesto pequeño cubre toda la pantalla
            }
        }, { passive: false });
    };
})();
