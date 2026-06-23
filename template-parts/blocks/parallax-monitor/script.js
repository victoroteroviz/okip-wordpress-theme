/*
 * Bloque Parallax Monitor (Bloque 2) — comportamiento.
 * Scope por instancia via [data-okip-pm].
 *
 * Principios (reglas críticas del proyecto):
 *  - El ROOT `.okip-pm` NO recibe parallax (no se mueve como masa → nada "sube de golpe").
 *  - PARALLAX = transform inline SOLO en nodos EXTERIORES de capa
 *      (.okip-pm__bg / .okip-pm__monitor / .okip-pm__text).
 *  - REVEAL  = opacidad/translate por CLASE latcheada en nodos INTERIORES
 *      (.okip-pm__computer-reveal / .okip-pm__text-reveal) + opacidad del fondo.
 *      Nunca reveal y parallax en el mismo nodo.
 *
 * Con GSAP + ScrollTrigger (desktop > disable_below):
 *   1) HERO RECEDE: el Hero se hunde (y/scale/opacity) mientras sale (scrub, rango amplio).
 *   2) REVEAL: una sola vez al entrar el bloque (top 78%). Se añaden las 3 clases a la vez;
 *      el ESCALONADO (fondo → texto → monitor) lo da el CSS (transition-delay) → suave,
 *      nunca intermedio ni atascado.
 *   3) DRIFT (parallax): fondo lento, monitor visible, texto micro — en nodos exteriores.
 *   4) BG-PIN: el Bloque 2 se pinea (pinSpacing:false) como FONDO ESTÁTICO mientras el
 *      Bloque 3 (z-index mayor) sube por scroll ENCIMA de él. No escribe nada sobre B3.
 *
 * Sin GSAP: fallback vanilla rAF (drift) + IO one-shot (reveal). Sin pin (apilado normal).
 * Móvil/tablet (≤disable_below): is-static, reveal inmediato, sin parallax ni pin.
 * Respeta prefers-reduced-motion. No duplica listeners.
 */
(function () {
    'use strict';

    var LERP = 0.09; // suavizado del fallback vanilla

    var reduceMotion = !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);

    function gsapReady() {
        return !!(window.okipGsap && window.okipGsap.ready && window.gsap);
    }
    function stReady() {
        return gsapReady() && window.okipGsap.hasScrollTrigger && window.ScrollTrigger;
    }

    function clamp(v, a, b) { return v < a ? a : (v > b ? b : v); }
    function lerp(a, b, t)  { return a + (b - a) * t; }

    var REVEAL_CLASS = {
        background: 'is-bg-revealed',
        computer:   'is-computer-revealed',
        text:       'is-text-revealed'
    };

    function initPm(section) {
        if (section.__okipPmInit) { return; }
        section.__okipPmInit = true;

        var d          = section.dataset;
        var animOn     = d.anim       === '1';
        var transition = d.transition === '1';
        var parallaxOn = d.parallax   === '1';
        var useGsap    = d.useGsap    !== '0'; // true por defecto
        var useVanilla = d.useVanilla !== '0'; // true por defecto
        var bgPinOn    = d.bgPin      === '1';

        var DRIFT        = parseFloat(d.driftMax)       || 100;
        var disableBelow = parseInt(d.disableBelow, 10) || 1024;
        var bgPinVh      = parseFloat(d.bgPinVh)        || 90;
        var isSmall      = !!(window.matchMedia && window.matchMedia('(max-width: ' + disableBelow + 'px)').matches);

        // ID de instancia para nombrar ScrollTriggers y evitar colisiones entre bloques.
        var pmId = section.id || section.dataset.blockInstance || 'pm';

        var hero = document.querySelector('[data-okip-hero]');

        // Capas (nodos EXTERIORES): solo para el DRIFT (parallax).
        var layers = [];
        section.querySelectorAll('[data-okip-pm-layer]').forEach(function (el) {
            layers.push({
                el:          el,
                name:        el.getAttribute('data-okip-pm-layer'),
                speed:       parseFloat(el.dataset.speed) || 0,
                revealClass: REVEAL_CLASS[el.getAttribute('data-okip-pm-layer')] || ''
            });
        });

        var monitor     = section.querySelector('[data-okip-pm-layer="computer"]');
        var cmpVideo    = section.querySelector('[data-okip-pm-screen-video]');
        var cmpAutoplay = monitor && monitor.getAttribute('data-autoplay-on-enter') === '1';
        var cmpStarted  = false;

        function playComputer() {
            if (!cmpVideo || cmpStarted) { return; }
            cmpStarted = true;
            var pr = cmpVideo.play();
            if (pr && typeof pr.catch === 'function') { pr.catch(function () {}); }
        }

        // REVEAL: añade las 3 clases A LA VEZ; el CSS las escalona (transition-delay).
        var revealed = false;
        function revealAll() {
            if (revealed) { return; }
            revealed = true;
            section.classList.add('is-bg-revealed', 'is-computer-revealed', 'is-text-revealed');
            if (cmpAutoplay) { playComputer(); }
        }

        function vh() { return window.innerHeight || document.documentElement.clientHeight; }

        function applyHeroRecede(p) {
            if (!hero) { return; }
            if (p <= 0.001) {
                hero.style.transform = '';
                hero.style.opacity   = '';
                return;
            }
            hero.style.transformOrigin = 'center top';
            hero.style.transform = 'translate3d(0,' + lerp(0, 24, p).toFixed(2) + 'px,0) scale(' + lerp(1, 0.94, p).toFixed(4) + ')';
            hero.style.opacity   = lerp(1, 0.66, p).toFixed(3);
        }

        var canAnimate = animOn && !reduceMotion && !isSmall && layers.length > 0;

        /* ============================================================
           MODO ESTÁTICO: móvil/tablet, reduce-motion, sin animación.
           Reveal inmediato, sin parallax ni pin. Flujo vertical limpio.
           ============================================================ */
        if (!canAnimate) {
            section.classList.add('is-static');
            revealAll();
            return;
        }

        section.classList.add('is-transitioning');

        if (useGsap && stReady()) {
            initGsap();
        } else if (useVanilla) {
            initVanilla();
        } else {
            revealAll(); // sin driver: al menos mostrar contenido.
        }

        /* ============================================================
           GSAP + ScrollTrigger.
           ============================================================ */
        function initGsap() {
            var gsap = window.gsap;
            var ST   = window.ScrollTrigger;

            // 1) HERO RECEDE: hundimiento del Hero SOLO al salir (último ~20% de su
            //    scroll). Empieza al 80% → el Hero se mantiene limpio y protagonista
            //    hasta que el usuario realmente lo abandona.
            if (transition && hero) {
                gsap.timeline({
                    scrollTrigger: {
                        id: pmId + '-hero',
                        trigger: hero,
                        start: function () { return 'top+=' + (0.80 * hero.offsetHeight) + ' top'; },
                        end:   function () { return 'top+=' + hero.offsetHeight + ' top'; },
                        scrub: 0.6,
                        invalidateOnRefresh: true
                    }
                }).to(hero, { y: 24, scale: 0.94, opacity: 0.66, transformOrigin: 'center top', ease: 'none' }, 0);
            }

            // 2) REVEAL one-shot: SOLO al salir del Hero (~82% de su scroll), no al
            //    asomar el bloque. Así B2 no se "siente presente" antes de tiempo.
            //    El escalonado fondo→texto→monitor lo da el CSS (transition-delay).
            ST.create({
                id:      pmId + '-reveal',
                trigger: hero || section,
                start:   hero
                    ? function () { return 'top+=' + (0.82 * hero.offsetHeight) + ' top'; }
                    : 'top 55%',
                once:    true,
                onEnter: revealAll
            });

            // 3) DRIFT (parallax): SOLO en nodos exteriores con speed.
            if (parallaxOn) {
                var driftTl = gsap.timeline({
                    scrollTrigger: {
                        id: pmId + '-drift',
                        trigger: section,
                        start: 'top bottom',
                        end:   'bottom top',
                        scrub: 0.6,
                        invalidateOnRefresh: true
                    }
                });
                layers.forEach(function (L) {
                    if (!L.speed) { return; }
                    driftTl.fromTo(L.el, { y: L.speed * DRIFT }, { y: -L.speed * DRIFT, ease: 'none' }, 0);
                });
            }

            // 4) BG-PIN: el Bloque 2 queda FIJO como fondo mientras el Bloque 3 sube encima.
            //    pinSpacing:false → no añade espacio; el siguiente bloque (z-index mayor)
            //    se superpone por scroll. No toca el Bloque 3 en absoluto.
            if (bgPinOn) {
                ST.create({
                    id:            pmId + '-bgpin',
                    trigger:       section,
                    start:         'top top',
                    end:           function () { return '+=' + (bgPinVh / 100 * vh()); },
                    pin:           true,
                    pinSpacing:    false,
                    anticipatePin: 1,
                    invalidateOnRefresh: true
                });
            }

            // Resize: refresh en desktop; si pasa a móvil, matar STs propios → estático.
            var rt;
            window.addEventListener('resize', function () {
                window.clearTimeout(rt);
                rt = window.setTimeout(function () {
                    var nowSmall = !!(window.matchMedia && window.matchMedia('(max-width: ' + disableBelow + 'px)').matches);
                    if (nowSmall) {
                        ST.getAll().forEach(function (st) {
                            if (st.vars && st.vars.id && String(st.vars.id).indexOf(pmId + '-') === 0) {
                                st.kill(true);
                            }
                        });
                        section.style.transform = '';
                        layers.forEach(function (L) { L.el.style.transform = ''; });
                        if (hero) { hero.style.transform = ''; hero.style.opacity = ''; }
                        section.classList.remove('is-transitioning');
                        section.classList.add('is-static');
                        revealAll();
                    } else {
                        ST.refresh();
                    }
                }, 200);
            }, { passive: true });
        }

        /* ============================================================
           Fallback VANILLA: rAF (drift) + IO one-shot (reveal).
           Sin pin: la superposición B2→B3 degrada a apilado normal.
           ============================================================ */
        function initVanilla() {
            function driftProgress() {
                var rect  = section.getBoundingClientRect();
                var denom = (vh() + rect.height) / 2;
                if (denom <= 0) { return 0; }
                return clamp(((vh() / 2) - (rect.top + rect.height / 2)) / denom, -1, 1);
            }
            function heroProgress() {
                if (!hero) { return 0; }
                var h = hero.offsetHeight;
                if (!h || h <= 0) { return 0; }
                var rect  = hero.getBoundingClientRect();
                var start = rect.top + window.scrollY + h * 0.80; // recede solo en el último 20%
                var end   = rect.top + window.scrollY + h;
                if (end <= start) { return window.scrollY >= start ? 1 : 0; }
                return clamp((window.scrollY - start) / (end - start), 0, 1);
            }

            var current = 0;
            function applyFrame() {
                var dp = parallaxOn ? driftProgress() : 0;
                for (var i = 0; i < layers.length; i++) {
                    var L = layers[i];
                    if (L.speed) {
                        L.el.style.transform = 'translate3d(0,' + (dp * L.speed * DRIFT).toFixed(2) + 'px,0)';
                    }
                }
                if (transition) {
                    current = lerp(current, heroProgress(), LERP);
                    applyHeroRecede(current);
                }
            }

            var active = false, rafId = 0;
            function loop() {
                if (!active) { rafId = 0; return; }
                applyFrame();
                rafId = window.requestAnimationFrame(loop);
            }
            function setActive(v) {
                if (v) {
                    if (!active) { active = true; if (!rafId) { rafId = window.requestAnimationFrame(loop); } }
                } else if (active) {
                    active = false;
                    if (rafId) { window.cancelAnimationFrame(rafId); rafId = 0; }
                    applyFrame();
                }
            }

            if ('IntersectionObserver' in window) {
                // IO principal: activa/desactiva el bucle rAF (drift + hero recede).
                var vis = { hero: false, section: false };
                var io  = new IntersectionObserver(function (entries) {
                    entries.forEach(function (e) {
                        if (e.target === hero)         { vis.hero    = e.isIntersecting; }
                        else if (e.target === section) { vis.section = e.isIntersecting; }
                    });
                    setActive(vis.hero || vis.section);
                }, { threshold: 0, rootMargin: '20% 0px 20% 0px' });
                io.observe(section);
                if (hero) { io.observe(hero); }

                // IO de reveal: una sola vez, cuando el bloque ya ocupa buena parte del
                // viewport (Hero casi fuera). Threshold alto → no revela antes de tiempo.
                var revealIO = new IntersectionObserver(function (entries, obs) {
                    entries.forEach(function (e) {
                        if (e.isIntersecting) { revealAll(); obs.disconnect(); }
                    });
                }, { threshold: 0.45 });
                revealIO.observe(section);
            } else {
                revealAll();
                active = true;
                window.requestAnimationFrame(loop);
            }

            applyFrame();
            window.addEventListener('resize', applyFrame, { passive: true });
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
