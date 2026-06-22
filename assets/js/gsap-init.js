/*
 * OKIP — inicialización de GSAP.
 *
 * GSAP/ScrollTrigger se cargan localmente y SOLO si existen (ver inc/enqueue.php).
 * Aquí registramos ScrollTrigger si está presente y exponemos el estado para que
 * cada bloque decida si anima con GSAP o usa su fallback CSS.
 *
 * Sin GSAP, este archivo no hace nada y el sitio sigue funcionando.
 */
(function () {
    'use strict';

    var env = window.OKIP_ENV || {};

    window.okipGsap = {
        ready: false,
        hasScrollTrigger: false
    };

    if (typeof window.gsap === 'undefined') {
        // GSAP no disponible: los bloques usarán su fallback.
        return;
    }

    window.okipGsap.ready = true;

    if (env.hasScrollTrigger && typeof window.ScrollTrigger !== 'undefined') {
        window.gsap.registerPlugin(window.ScrollTrigger);
        window.okipGsap.hasScrollTrigger = true;
    }
})();
