/*
 * Bloque Parallax Monitor (Bloque 2) — comportamiento.
 * Scope por instancia via [data-okip-pm].
 *
 * 1) Revelado de entrada (IntersectionObserver + CSS).
 * 2) Parallax REAL por capas (fondo / monitor / texto a velocidades distintas):
 *      - Con GSAP + ScrollTrigger si existen (scrub).
 *      - Sin GSAP → fallback VANILLA: IntersectionObserver activa/desactiva un
 *        bucle requestAnimationFrame que calcula el progreso del bloque en el
 *        viewport y aplica transforms distintos por capa. No solo revelado.
 *
 * No bloquea el scroll. No duplica listeners. Respeta prefers-reduced-motion y
 * reduce/desactiva el parallax fuerte en móvil.
 */
(function () {
    'use strict';

    var BASE = 120; // px de recorrido máximo por capa (× su data-speed)

    var reduceMotion = !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
    var isSmall = !!(window.matchMedia && window.matchMedia('(max-width: 880px)').matches);

    function gsapReady() {
        return !!(window.okipGsap && window.okipGsap.ready && window.gsap);
    }
    function scrollTriggerReady() {
        return gsapReady() && window.okipGsap.hasScrollTrigger;
    }

    function initPm(section) {
        if (section.__okipPmInit) { return; }
        section.__okipPmInit = true;

        var d = section.dataset;
        var animOn      = d.anim === '1';
        var parallaxOn  = d.parallax === '1';
        var useGsap     = d.useGsap === '1';
        var useVanilla  = d.useVanilla === '1';
        var textReveal  = d.textReveal === '1';

        var layers = [];
        section.querySelectorAll('[data-okip-pm-layer]').forEach(function (el) {
            layers.push({ el: el, speed: parseFloat(el.dataset.speed) || 0 });
        });

        /* ---------- Revelado de entrada ---------- */
        function reveal() {
            section.classList.add('is-revealed');
        }

        var revealed = false;
        function doRevealOnce() {
            if (revealed) { return; }
            revealed = true;
            reveal();
            // El parallax arranca tras el revelado para no pisar su transición.
            if (canParallax()) {
                window.setTimeout(startParallax, 750);
            }
        }

        function canParallax() {
            return animOn && parallaxOn && !reduceMotion && !isSmall && layers.length > 0;
        }

        if (!animOn || reduceMotion || !textReveal) {
            reveal();
            revealed = true;
            if (canParallax()) {
                window.setTimeout(startParallax, 50);
            }
        } else if ('IntersectionObserver' in window) {
            var io = new IntersectionObserver(function (entries, obs) {
                entries.forEach(function (e) {
                    if (e.isIntersecting) {
                        doRevealOnce();
                        obs.disconnect();
                    }
                });
            }, { threshold: 0.2 });
            io.observe(section);
        } else {
            doRevealOnce();
        }

        /* ---------- Parallax ---------- */
        function startParallax() {
            if (section.__okipPmParallax) { return; }
            section.__okipPmParallax = true;
            section.classList.add('is-parallax');

            if (useGsap && scrollTriggerReady()) {
                startGsapParallax();
            } else if (useVanilla) {
                startVanillaParallax();
            }
        }

        /* GSAP + ScrollTrigger: cada capa con scrub a distinta velocidad. */
        function startGsapParallax() {
            var gsap = window.gsap;
            layers.forEach(function (l) {
                if (!l.speed) { return; }
                gsap.fromTo(l.el,
                    { y: l.speed * BASE },
                    {
                        y: -l.speed * BASE,
                        ease: 'none',
                        scrollTrigger: {
                            trigger: section,
                            start: 'top bottom',
                            end: 'bottom top',
                            scrub: true
                        }
                    }
                );
            });
        }

        /* Fallback vanilla: IO activa el rAF solo mientras el bloque es visible. */
        function startVanillaParallax() {
            var visible = false;
            var running = false;

            function progress() {
                var vh = window.innerHeight || document.documentElement.clientHeight;
                var rect = section.getBoundingClientRect();
                var denom = (vh + rect.height) / 2;
                if (denom <= 0) { return 0; }
                var p = ((vh / 2) - (rect.top + rect.height / 2)) / denom;
                return p < -1 ? -1 : (p > 1 ? 1 : p);
            }

            function update() {
                var p = progress();
                for (var i = 0; i < layers.length; i++) {
                    var l = layers[i];
                    if (!l.speed) { continue; }
                    var y = p * l.speed * BASE;
                    l.el.style.transform = 'translate3d(0,' + y.toFixed(2) + 'px,0)';
                }
            }

            function frame() {
                if (!visible) { running = false; return; }
                update();
                window.requestAnimationFrame(frame);
            }
            function ensureRunning() {
                if (visible && !running) {
                    running = true;
                    window.requestAnimationFrame(frame);
                }
            }

            if ('IntersectionObserver' in window) {
                var ioP = new IntersectionObserver(function (entries) {
                    visible = entries[0].isIntersecting;
                    ensureRunning();
                }, { threshold: 0, rootMargin: '20% 0px 20% 0px' });
                ioP.observe(section);
            } else {
                visible = true;
                ensureRunning();
                window.addEventListener('scroll', ensureRunning, { passive: true });
            }

            update();
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
