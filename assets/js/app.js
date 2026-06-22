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
})();
