/*
 * Bloque Parallax Monitor (Bloque 2) — comportamiento.
 * Scope por instancia via [data-okip-pm].
 *
 * - Parallax: fondo y monitor se desplazan a distinto ritmo al hacer scroll.
 *   Con GSAP + ScrollTrigger; sin GSAP → solo revelado de entrada (IO + CSS),
 *   sin parallax pesado.
 * - Revelado del texto/monitor con IntersectionObserver (o GSAP).
 * - Respeta prefers-reduced-motion. No duplica listeners. No bloquea scroll.
 */
(function () {
    'use strict';

    var reduceMotion = !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
    var isSmall = window.matchMedia && window.matchMedia('(max-width: 880px)').matches;

    function gsapReady() {
        return !!(window.okipGsap && window.okipGsap.ready && window.gsap);
    }
    function scrollTriggerReady() {
        return gsapReady() && window.okipGsap.hasScrollTrigger;
    }

    function initPm(section) {
        if (section.__okipPmInit) { return; }
        section.__okipPmInit = true;

        var animOn = section.dataset.anim === '1';
        var textReveal = section.dataset.textReveal === '1';
        var strength = parseFloat(section.dataset.strength) || 1;

        var bg = section.querySelector('[data-okip-pm-bg]');
        var monitor = section.querySelector('[data-okip-pm-monitor]');
        var text = section.querySelector('[data-okip-pm-text]');

        /* ---------- Revelado de entrada ---------- */
        function reveal() {
            section.classList.add('is-revealed');
        }

        if (!animOn || reduceMotion || !textReveal) {
            reveal();
        } else if ('IntersectionObserver' in window) {
            var io = new IntersectionObserver(function (entries, obs) {
                entries.forEach(function (e) {
                    if (e.isIntersecting) {
                        reveal();
                        obs.disconnect();
                    }
                });
            }, { threshold: 0.25 });
            io.observe(section);
        } else {
            reveal();
        }

        /* ---------- Parallax (solo GSAP + ScrollTrigger, no en móvil/reduce) ---------- */
        if (animOn && !reduceMotion && !isSmall && scrollTriggerReady()) {
            var gsap = window.gsap;

            if (monitor) {
                var mSpeed = parseFloat(monitor.dataset.speed) || 0;
                gsap.fromTo(monitor,
                    { yPercent: mSpeed * 100 * strength },
                    {
                        yPercent: -mSpeed * 100 * strength,
                        ease: 'none',
                        scrollTrigger: { trigger: section, start: 'top bottom', end: 'bottom top', scrub: true }
                    }
                );
            }
            if (bg) {
                var bSpeed = parseFloat(bg.dataset.speed) || 0;
                gsap.fromTo(bg,
                    { yPercent: bSpeed * 100 * strength },
                    {
                        yPercent: -bSpeed * 100 * strength,
                        ease: 'none',
                        scrollTrigger: { trigger: section, start: 'top bottom', end: 'bottom top', scrub: true }
                    }
                );
            }
        }
    }

    function init() {
        document.querySelectorAll('[data-okip-pm]').forEach(initPm);
    }

    if (window.OKIP && window.OKIP.ready) {
        window.OKIP.ready(init);
    } else if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
