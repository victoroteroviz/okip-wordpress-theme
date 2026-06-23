/*
 * Bloque Parallax Monitor (Bloque 2) — comportamiento.
 * Scope por instancia via [data-okip-pm].
 *
 * Transición Hero → Bloque 2 y parallax por capas.
 *
 *  - Con GSAP + ScrollTrigger (local):
 *      1) TRANSICIÓN: el bloque sube sobre el Hero (overlap scrub) y el Hero se hunde.
 *      2) TEXTO: reveal temprano via ScrollTrigger one-shot ("top 75%"), completamente
 *         desacoplado del scrub → nunca queda en estado intermedio / atascado.
 *      3) PARALLAX: drift SOLO en fondo y computadora (texto queda fijo).
 *
 *  - Sin GSAP: fallback vanilla rAF + lerp. Texto via IntersectionObserver one-shot.
 *
 *  - Móvil/tablet (≤1024px): modo is-static, reveal inmediato, sin parallax ni overlap.
 *    Si hay resize a móvil, se matan los ScrollTriggers propios del bloque (por id).
 *
 * Respeta prefers-reduced-motion. No duplica listeners.
 */
(function () {
    'use strict';

    var DRIFT = 64;   // px de drift de parallax por capa (× su data-speed)
    var LERP  = 0.09; // suavizado del fallback vanilla

    var reduceMotion = !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
    var isSmall      = !!(window.matchMedia && window.matchMedia('(max-width: 1024px)').matches); // --okip-bp-tablet

    function gsapReady() {
        return !!(window.okipGsap && window.okipGsap.ready && window.gsap);
    }
    function stReady() {
        return gsapReady() && window.okipGsap.hasScrollTrigger && window.ScrollTrigger;
    }

    function clamp(v, a, b) { return v < a ? a : (v > b ? b : v); }
    function lerp(a, b, t)  { return a + (b - a) * t; }

    function parseRange(str, defA, defB) {
        var a = defA, b = defB;
        if (str) {
            var parts = String(str).split(',');
            if (parts.length === 2) {
                var pa = parseFloat(parts[0]);
                var pb = parseFloat(parts[1]);
                if (!isNaN(pa)) { a = pa; }
                if (!isNaN(pb)) { b = pb; }
            }
        }
        if (b < a) { b = a; }
        return [a, b];
    }

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
        var textReveal = d.textReveal === '1';
        var useGsap    = d.useGsap    !== '0'; // true por defecto
        var useVanilla = d.useVanilla !== '0'; // true por defecto

        // Magnitud base de drift y breakpoint leídos desde config (vía data-*).
        var DRIFT        = parseFloat(d.driftMax)       || 80;
        var disableBelow = parseInt(d.disableBelow, 10) || 1024;
        var isSmall      = !!(window.matchMedia && window.matchMedia('(max-width: ' + disableBelow + 'px)').matches);

        var startProg = parseFloat(d.overlapStart);
        if (isNaN(startProg)) { startProg = 0.85; }
        var overlapVh = parseFloat(d.overlapVh) || 0;

        // ID de instancia para nombrar ScrollTriggers y evitar colisiones entre bloques.
        var pmId = section.id || section.dataset.blockInstance || 'pm';

        var hero = document.querySelector('[data-okip-hero]');

        // Capas: trigger = umbral de reveal (enter_range[0]); end = garantía (enter_range[1]).
        // El reveal es un latch binario; la CSS transition suaviza la aparición.
        // La capa 'text' tiene reveal independiente (no ligado al scrub).
        var layers = [];
        section.querySelectorAll('[data-okip-pm-layer]').forEach(function (el) {
            var name  = el.getAttribute('data-okip-pm-layer');
            var range = parseRange(el.dataset.enter, 0, 1);
            layers.push({
                el:          el,
                name:        name,
                speed:       parseFloat(el.dataset.speed) || 0,
                trigger:     Math.max(range[0], 0.03),
                end:         range[1],
                revealClass: REVEAL_CLASS[name] || ('is-' + name + '-revealed')
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
        function resetComputer() {
            if (!cmpVideo || !cmpStarted) { return; }
            cmpStarted = false;
            try { cmpVideo.pause(); cmpVideo.currentTime = 0; } catch (e) {}
        }

        // Reveal de fondo y computadora (latcheado por clase, compartido GSAP+vanilla).
        // El texto se excluye: tiene su propio trigger para no quedar atascado.
        function setReveal(p) {
            for (var i = 0; i < layers.length; i++) {
                var L = layers[i];
                if (L.name === 'text') { continue; }
                if (p >= L.trigger)  { section.classList.add(L.revealClass); }
                else if (p <= 0.02)  { section.classList.remove(L.revealClass); }
            }
            if (cmpAutoplay) {
                if (section.classList.contains('is-computer-revealed')) { playComputer(); }
                else if (p <= 0.02) { resetComputer(); }
            }
        }

        // Revela todo (garantía en onLeave y modo estático).
        function revealAll() {
            layers.forEach(function (L) { section.classList.add(L.revealClass); });
            if (cmpAutoplay) { playComputer(); }
        }

        function vh()        { return window.innerHeight || document.documentElement.clientHeight; }
        function overlapPx() { return (overlapVh / 100) * vh(); }

        function applyHeroRecede(p) {
            if (!hero) { return; }
            if (p <= 0.001) {
                hero.style.transform = '';
                hero.style.opacity   = '';
                return;
            }
            hero.style.transformOrigin = 'center top';
            hero.style.transform = 'translate3d(0,' + lerp(0, 26, p).toFixed(2) + 'px,0) scale(' + lerp(1, 0.94, p).toFixed(4) + ')';
            hero.style.opacity   = lerp(1, 0.62, p).toFixed(3);
        }

        var canTransition = animOn && transition && !reduceMotion && !isSmall && !!hero && layers.length > 0;

        /* ============================================================
           MODO ESTÁTICO: móvil/tablet (≤1024px), reduce-motion, sin transición.
           Reveal inmediato, sin parallax ni overlap. Layout fluye normal.
           ============================================================ */
        if (!canTransition) {
            section.classList.add('is-static');
            revealAll();
            return;
        }

        section.classList.add('is-transitioning');

        if (useGsap && stReady()) {
            initGsapTransition();
        } else if (useVanilla) {
            initVanillaTransition();
        }
        // Si use_gsap=false y use_vanilla=false → clase is-transitioning sin driver.

        /* ============================================================
           GSAP + ScrollTrigger.
           Texto desacoplado del scrub → estado final siempre limpio.
           ============================================================ */
        function initGsapTransition() {
            var gsap = window.gsap;
            var ST   = window.ScrollTrigger;

            // 1) TRANSICIÓN: overlap del bloque + hundimiento del Hero (scrub).
            //    Fondo y computadora se revelan por progreso. Texto: ST propio (abajo).
            gsap.timeline({
                scrollTrigger: {
                    id: pmId + '-overlap',
                    trigger: hero,
                    start: function () { return 'top+=' + (startProg * hero.offsetHeight) + ' top'; },
                    end:   function () { return 'top+=' + (hero.offsetHeight + 0.15 * vh()) + ' top'; },
                    scrub: 0.6,
                    invalidateOnRefresh: true,
                    onUpdate: function (self) {
                        setReveal(self.progress);
                        section.style.pointerEvents = self.progress > 0.55 ? 'auto' : 'none';
                    },
                    onLeave:     function () { setReveal(1); revealAll(); },
                    onLeaveBack: function () { setReveal(0); }
                }
            })
            .fromTo(section,
                { y: function () { return overlapPx(); } },
                { y: 0, ease: 'none' }, 0)
            .to(hero,
                { y: 26, scale: 0.94, opacity: 0.62, transformOrigin: 'center top', ease: 'none' }, 0);

            // 2) TEXTO: reveal temprano e independiente del scrub. Una sola vez.
            //    Dispara cuando la parte superior del bloque cruza el 75% del viewport.
            //    Una vez revelado, opacity:1 / transform:none es estado final (CSS latch).
            ST.create({
                id:      pmId + '-text',
                trigger: section,
                start:   'top 75%',
                once:    true,
                onEnter: function () { section.classList.add('is-text-revealed'); }
            });

            // 3) PARALLAX drift: SOLO fondo y computadora (texto queda fijo).
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

            // Estado inicial coherente (fondo y computer ocultos; texto: ST lo maneja).
            setReveal(0);

            // Resize: refresh ST en desktop; si cambia a móvil, matar STs propios e ir a estático.
            var rt;
            window.addEventListener('resize', function () {
                window.clearTimeout(rt);
                rt = window.setTimeout(function () {
                    var nowSmall = !!(window.matchMedia && window.matchMedia('(max-width: ' + disableBelow + 'px)').matches);
                    if (nowSmall) {
                        // Matar solo los STs de este bloque (prefijo id).
                        ST.getAll().forEach(function (st) {
                            if (st.vars && st.vars.id && String(st.vars.id).indexOf(pmId + '-') === 0) {
                                st.kill();
                            }
                        });
                        // Limpiar estilos inline.
                        section.style.transform     = '';
                        section.style.pointerEvents = '';
                        layers.forEach(function (L) { L.el.style.transform = ''; });
                        if (hero) { hero.style.transform = ''; hero.style.opacity = ''; }
                        // Pasar a modo estático.
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
           Fallback VANILLA: rAF + lerp. Texto vía IO independiente.
           ============================================================ */
        function initVanillaTransition() {
            var current = 0;

            function targetProgress() {
                var h = hero.offsetHeight;
                if (!h || h <= 0) { return 0; }
                var rect   = hero.getBoundingClientRect();
                var topDoc = rect.top + window.scrollY;
                var start  = topDoc + h * startProg;
                var end    = topDoc + h + vh() * 0.15;
                if (end <= start) { return window.scrollY >= start ? 1 : 0; }
                return clamp((window.scrollY - start) / (end - start), 0, 1);
            }
            function driftProgress() {
                var rect  = section.getBoundingClientRect();
                var denom = (vh() + rect.height) / 2;
                if (denom <= 0) { return 0; }
                return clamp(((vh() / 2) - (rect.top + rect.height / 2)) / denom, -1, 1);
            }

            function applyFrame(p) {
                section.style.transform     = 'translate3d(0,' + ((1 - p) * overlapPx()).toFixed(2) + 'px,0)';
                section.style.pointerEvents = p > 0.55 ? 'auto' : 'none';
                var dp = parallaxOn ? driftProgress() : 0;
                for (var i = 0; i < layers.length; i++) {
                    var L = layers[i];
                    L.el.style.transform = 'translate3d(0,' + (dp * L.speed * DRIFT).toFixed(2) + 'px,0)';
                }
                setReveal(p);
                applyHeroRecede(p);
            }
            function render() {
                var target = targetProgress();
                current = lerp(current, target, LERP);
                if (Math.abs(current - target) < 0.001) { current = target; }
                applyFrame(current);
            }

            var active = false, rafId = 0;
            function loop() {
                if (!active) { rafId = 0; return; }
                render();
                rafId = window.requestAnimationFrame(loop);
            }
            function setActive(v) {
                if (v) {
                    if (!active) { active = true; if (!rafId) { rafId = window.requestAnimationFrame(loop); } }
                } else if (active) {
                    active  = false;
                    current = targetProgress();
                    applyFrame(current);
                }
            }

            if ('IntersectionObserver' in window) {
                // IO principal: activa/desactiva el bucle rAF.
                var vis = { hero: false, section: false };
                var io  = new IntersectionObserver(function (entries) {
                    entries.forEach(function (e) {
                        if (e.target === hero)         { vis.hero    = e.isIntersecting; }
                        else if (e.target === section) { vis.section = e.isIntersecting; }
                    });
                    setActive(vis.hero || vis.section);
                }, { threshold: 0, rootMargin: '30% 0px 30% 0px' });
                io.observe(section);
                if (hero) { io.observe(hero); }

                // IO de texto: reveal temprano, una sola vez. Threshold bajo (10%).
                var textIO = new IntersectionObserver(function (entries, obs) {
                    entries.forEach(function (e) {
                        if (e.isIntersecting) {
                            section.classList.add('is-text-revealed');
                            obs.disconnect();
                        }
                    });
                }, { threshold: 0.1 });
                textIO.observe(section);
            } else {
                // Sin IO: revelar texto de inmediato y correr rAF siempre.
                section.classList.add('is-text-revealed');
                active = true;
                window.requestAnimationFrame(loop);
            }

            applyFrame(0);
            window.addEventListener('resize', function () {
                isSmall = !!(window.matchMedia && window.matchMedia('(max-width: ' + disableBelow + 'px)').matches);
                render();
            }, { passive: true });
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
