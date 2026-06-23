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
 * Con GSAP + ScrollTrigger:
 *   1) HERO STICKY: el Hero queda fijo por CSS y B2 lo cubre por flujo/z-index.
 *   2) REVEAL: una sola vez al salir del Hero (~82%). Se añaden las 3 clases a la vez;
 *      el ESCALONADO (fondo → texto → monitor) lo da el CSS (transition-delay) → suave,
 *      nunca intermedio ni atascado.
 *   3) DEPTH ENTRY (parallax): fondo, monitor y texto entran con velocidades
 *      distintas y terminan en y:0 cuando B2 queda completo. El hold posterior
 *      no vuelve a mover esas capas.
 *   4) HOLD-PIN: el Bloque 2 se pinea sin espacio reservado; B3 sube encima
 *      como panel claro mientras la escena de B2 queda fija.
 *
 * Sin GSAP: fallback vanilla rAF (depth entry) + IO one-shot (reveal). Sin pin.
 * Móvil/tablet también animan; solo reduce-motion / sin driver entra en is-static.
 * Respeta prefers-reduced-motion. No duplica listeners.
 */
(function () {
    'use strict';

    var reduceMotion = !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);

    function gsapReady() {
        return !!(window.okipGsap && window.okipGsap.ready && window.gsap);
    }
    function stReady() {
        return gsapReady() && window.okipGsap.hasScrollTrigger && window.ScrollTrigger;
    }

    function clamp(v, a, b) { return v < a ? a : (v > b ? b : v); }

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
        var parallaxOn = d.parallax   === '1';
        var useGsap    = d.useGsap    !== '0'; // true por defecto
        var useVanilla = d.useVanilla !== '0'; // true por defecto
        var bgPinOn    = d.bgPin      === '1';

        var DRIFT        = parseFloat(d.driftMax)       || 140;
        var bgPinVh      = parseFloat(d.bgPinVh)        || 100;

        // ID de instancia para nombrar ScrollTriggers y evitar colisiones entre bloques.
        var pmId = section.id || section.dataset.blockInstance || 'pm';

        var hero = document.querySelector('[data-okip-hero]');

        // Capas (nodos EXTERIORES): solo para la profundidad de entrada.
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

        // El Hero NO se anima: queda estático y el Bloque 2 lo cubre (apilado). Sin recede.

        var canAnimate = animOn && !reduceMotion && layers.length > 0;

        /* ============================================================
           MODO ESTÁTICO: reduce-motion o sin capas/animación.
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
            section.classList.remove('is-transitioning');
            section.classList.add('is-static');
            revealAll(); // sin driver: al menos mostrar contenido.
        }

        /* ============================================================
           GSAP + ScrollTrigger.
           ============================================================ */
        function initGsap() {
            var gsap = window.gsap;
            var ST   = window.ScrollTrigger;

            // El Hero NO se anima: queda ESTÁTICO (position:sticky en CSS, desktop) y el
            // Bloque 2 (z-index 2, opaco) sube por flujo natural y lo cubre (apilado).
            // Sin recede → contenido del Hero inmóvil y sin jank en el navbar.

            function forceLayersRest() {
                layers.forEach(function (L) {
                    if (L.speed) { gsap.set(L.el, { y: 0 }); }
                });
            }

            function holdPinDistance() {
                var fallback = bgPinVh / 100 * vh();
                var next = section.nextElementSibling;
                if (!next) { return fallback; }

                // Mantener B2 fijo hasta que el siguiente bloque llegue al top del viewport.
                // Con pinSpacing:false, B3 cubre B2 en lugar de empujarlo.
                var distance = next.offsetTop - section.offsetTop;
                return distance > 0 ? distance : fallback;
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

            // 3) DEPTH ENTRY: SOLO en nodos exteriores con speed.
            //    Termina en top top; desde B2 completo y durante el hold, las capas
            //    quedan en su posición natural (y:0) y no suben con el scroll.
            if (parallaxOn) {
                var driftTl = gsap.timeline({
                    scrollTrigger: {
                        id: pmId + '-drift',
                        trigger: section,
                        start: 'top bottom',
                        end:   'top top',
                        scrub: true,
                        invalidateOnRefresh: true,
                        onLeave: forceLayersRest
                    }
                });
                layers.forEach(function (L) {
                    if (!L.speed) { return; }
                    driftTl.fromTo(L.el, { y: L.speed * DRIFT }, { y: 0, ease: 'none' }, 0);
                });
            }

            // 4) HOLD-PIN: B2 queda fijo sin reservar espacio. B3 (z-index 3)
            //    sube encima y cubre la escena estática de B2.
            if (bgPinOn) {
                ST.create({
                    id:            pmId + '-bgpin',
                    trigger:       section,
                    start:         'top top',
                    end:           function () { return '+=' + holdPinDistance(); },
                    pin:           true,
                    pinSpacing:    false,
                    anticipatePin: 1,
                    invalidateOnRefresh: true,
                    onEnter:       forceLayersRest,
                    onUpdate:      forceLayersRest,
                    onLeave:       forceLayersRest,
                    onEnterBack:   forceLayersRest
                });
            }

            // Resize: B2 anima en todos los tamaños; solo refrescamos medidas.
            var rt;
            window.addEventListener('resize', function () {
                window.clearTimeout(rt);
                rt = window.setTimeout(function () {
                    ST.refresh();
                }, 200);
            }, { passive: true });
        }

        /* ============================================================
           Fallback VANILLA: rAF (depth entry) + IO one-shot (reveal).
           Sin pin: la cobertura B2→B3 degrada a apilado normal.
           ============================================================ */
        function initVanilla() {
            function entryProgress() {
                var rect  = section.getBoundingClientRect();
                return clamp((vh() - rect.top) / vh(), 0, 1);
            }
            function applyFrame() {
                var p = parallaxOn ? entryProgress() : 1;
                for (var i = 0; i < layers.length; i++) {
                    var L = layers[i];
                    if (L.speed) {
                        L.el.style.transform = 'translate3d(0,' + ((1 - p) * L.speed * DRIFT).toFixed(2) + 'px,0)';
                    }
                }
                // El Hero queda estático (sticky CSS); no se anima desde aquí.
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
                // IO principal: activa/desactiva el bucle rAF (depth entry).
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
