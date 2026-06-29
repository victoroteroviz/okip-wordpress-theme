/*
 * OKIP — bloque Video con Título (video-w-title).
 *
 * Solo REVEAL de entrada (determinista). El overlap de salida NO vive aquí: es
 * `position: sticky` por CSS (transition.mode = sticky-cover; ver style.css +
 * assets/css/transitions.css) → suave a cualquier velocidad, sin ScrollTrigger.
 *
 * Reveal robusto:
 *  - El estado inicial oculto lo ARMA este script (clase `is-anim-armed`). Si el
 *    script no corre, el texto queda visible (nunca oculto permanentemente).
 *  - Disparo determinista por IO de "línea de disparo": revela cuando el top del
 *    bloque cruza el 15% superior del viewport (el bloque cubre ~85%), UNA vez.
 *    Mismo punto que el reveal del navbar → coherente, sin estados a medias.
 *  - Sin IO / reduce-motion / data-anim=0 → revela de inmediato sin armar.
 *
 * No depende de GSAP ni de selectores de otros bloques. Flag `__okipVwtInit` evita
 * doble init.
 */
(function () {
    'use strict';

    var REVEAL_RATIO = 0.15; // top del bloque bajo el 15% superior = cubre ~85%

    function reduceMotion() {
        return (window.OKIP && window.OKIP.reduceMotion) ||
            (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
    }

    function reveal(section) {
        section.classList.add('is-revealed');
    }

    function setupSection(section) {
        if (section.__okipVwtInit) { return; }
        section.__okipVwtInit = true;

        var animEnabled = section.getAttribute('data-anim') === '1';

        // Sin animación, reduce-motion o sin IO → mostrar de inmediato (no armar).
        if (!animEnabled || reduceMotion() || typeof window.IntersectionObserver !== 'function') {
            reveal(section);
            return;
        }

        // Armar el estado inicial oculto SOLO ahora (por JS).
        section.classList.add('is-anim-armed');

        // Caso "ya en vista": si el top ya cruzó el umbral, revelar sin esperar al IO.
        if (section.getBoundingClientRect().top <= window.innerHeight * REVEAL_RATIO) {
            reveal(section);
            return;
        }

        // IO de "línea de disparo": root reducido a una banda fina en el 15% superior;
        // intersecta cuando el TOP del bloque la cruza (el bloque cubre ~85%).
        var io = new window.IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    reveal(entry.target);
                    io.unobserve(entry.target);
                }
            });
        }, { rootMargin: '-15% 0px -85% 0px', threshold: 0 });

        io.observe(section);
    }

    function init() {
        var sections = document.querySelectorAll('[data-okip-vwt]');
        for (var i = 0; i < sections.length; i++) {
            setupSection(sections[i]);
        }
    }

    if (window.OKIP && window.OKIP.ready) {
        window.OKIP.ready(init);
    } else if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
