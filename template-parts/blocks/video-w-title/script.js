/*
 * OKIP — bloque Video con Título (video-w-title).
 *
 * Dos comportamientos independientes, ambos defensivos:
 *
 *  1) REVEAL de entrada (IntersectionObserver): añade `is-revealed` cuando la
 *     sección entra en viewport; el escalonado lo da el CSS (transition-delay).
 *     Sin IO, con `data-anim=0` o `prefers-reduced-motion` → revela de inmediato.
 *
 *  2) OVERLAP de salida (HOLD-PIN, solo desktop + GSAP+ScrollTrigger): la sección
 *     se auto-pinea (fija, `pinSpacing:false`) mientras el bloque siguiente —de
 *     z-index mayor, ej. industry-carousel (z3)— sube desde la base y la cubre.
 *     Reproduce el traspaso tipo Hero→bloque. El pin dura la altura propia de la
 *     sección (justo lo que el bloque siguiente tarda en llegar al top). En
 *     ≤`data-overlap-bp` px, reduce-motion o sin GSAP → flujo apilado normal.
 *
 * No depende de GSAP para el reveal ni de selectores de otros bloques.
 */
(function () {
    'use strict';

    function reduceMotion() {
        return (window.OKIP && window.OKIP.reduceMotion) ||
            (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
    }

    function stReady() {
        return !!(window.okipGsap && window.okipGsap.ready && window.gsap &&
                  window.okipGsap.hasScrollTrigger && window.ScrollTrigger);
    }

    /* ---------- 1) Reveal de entrada ---------- */
    function reveal(section) {
        section.classList.add('is-revealed');
    }

    function setupReveal(section) {
        var animEnabled = section.getAttribute('data-anim') === '1';

        // Sin animación o reduce-motion → mostrar de inmediato.
        if (!animEnabled || reduceMotion() || typeof window.IntersectionObserver !== 'function') {
            reveal(section);
            return;
        }

        var io = new window.IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    reveal(entry.target);
                    io.unobserve(entry.target);
                }
            });
        }, { threshold: 0.25 });

        io.observe(section);
    }

    /* ---------- 2) Overlap de salida (hold-pin) ---------- */
    function setupOverlap(section) {
        var overlapOn = section.getAttribute('data-overlap') === '1';
        if (!overlapOn || reduceMotion() || !stReady()) { return; }

        // Bloque siguiente: solo tiene sentido pinear si hay algo que suba a cubrir.
        var following = section.nextElementSibling;
        if (!following) { return; }

        var bp = (window.OKIP && window.OKIP.readInt)
            ? window.OKIP.readInt(section.getAttribute('data-overlap-bp'), 1024)
            : (parseInt(section.getAttribute('data-overlap-bp'), 10) || 1024);

        function isSmall() {
            return !!(window.matchMedia && window.matchMedia('(max-width: ' + bp + 'px)').matches);
        }
        if (isSmall()) { return; }

        var ST = window.ScrollTrigger;
        var stId = (section.id || 'okip-vwt') + '-overlap';

        // HOLD-PIN: la sección queda fija (sin reservar espacio) y el bloque siguiente
        // sube por encima. El pin dura la altura propia de la sección, que es justo la
        // distancia que el bloque siguiente recorre hasta llegar al top del viewport.
        var pinST = ST.create({
            id:                  stId,
            trigger:             section,
            start:               'top top',
            end:                 function () { return '+=' + (section.offsetHeight || window.innerHeight); },
            pin:                 true,
            pinSpacing:          false,
            anticipatePin:       1,
            invalidateOnRefresh: true
        });

        // Resize: si se cruza a móvil/tablet, desmontar el pin (flujo apilado normal).
        var rt;
        window.addEventListener('resize', function () {
            window.clearTimeout(rt);
            rt = window.setTimeout(function () {
                if (isSmall()) {
                    if (pinST) { pinST.kill(); pinST = null; }
                } else if (ST) {
                    ST.refresh();
                }
            }, 200);
        }, { passive: true });
    }

    function setupSection(section) {
        if (section.__okipVwtInit) { return; }
        section.__okipVwtInit = true;

        setupReveal(section);
        setupOverlap(section);
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
