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

    // parseInt con fallback EXPLÍCITO (evita el bug de `parseInt(x) || 0` con índice 0).
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
})();
