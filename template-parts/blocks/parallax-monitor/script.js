/*
 * Bloque Parallax Monitor (Bloque 2) — comportamiento.
 * Scope por instancia via [data-okip-pm].
 *
 * Transición Hero → Bloque 2 y parallax por capas.
 *
 *  - Con GSAP + ScrollTrigger (local): dos timelines scrubbed (suaves):
 *      1) TRANSICIÓN: el bloque sube sobre el Hero (overlap) y el Hero se hunde,
 *         empezando ≈85% del Hero y terminando +15vh más allá (suavidad).
 *      2) PARALLAX: drift por capa (fondo/computadora/texto) a distinta velocidad.
 *    El REVEAL de cada capa se latchea por CLASE (is-bg/computer/text-revealed) en
 *    el nodo INTERIOR (transición CSS), separado del transform de parallax que va
 *    en el nodo EXTERIOR → el texto nunca queda en estado intermedio.
 *
 *  - Sin GSAP: fallback vanilla con requestAnimationFrame + lerp (damping), mismo
 *    sistema de clases de reveal. IntersectionObserver solo activa/desactiva el
 *    bucle; al pausarlo se fija un estado coherente (inicial o final).
 *
 * Respeta prefers-reduced-motion y reduce/desactiva en móvil. No bloquea scroll,
 * no duplica listeners. El hundimiento del Hero lo controla este bloque (el
 * scroll_3d del Hero está desactivado en Home para no duplicar transform).
 */
(function () {
    'use strict';

    var DRIFT = 64; // px de drift de parallax por capa (× su data-speed)
    var LERP = 0.09; // suavizado del fallback vanilla

    var reduceMotion = !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
    var isSmall = !!(window.matchMedia && window.matchMedia('(max-width: 880px)').matches);

    function gsapReady() {
        return !!(window.okipGsap && window.okipGsap.ready && window.gsap);
    }
    function stReady() {
        return gsapReady() && window.okipGsap.hasScrollTrigger && window.ScrollTrigger;
    }

    function clamp(v, a, b) { return v < a ? a : (v > b ? b : v); }
    function lerp(a, b, t) { return a + (b - a) * t; }

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
        computer: 'is-computer-revealed',
        text: 'is-text-revealed'
    };

    function initPm(section) {
        if (section.__okipPmInit) { return; }
        section.__okipPmInit = true;

        var d = section.dataset;
        var animOn     = d.anim === '1';
        var transition = d.transition === '1';
        var parallaxOn = d.parallax === '1';
        var textReveal = d.textReveal === '1';

        var startProg = parseFloat(d.overlapStart);
        if (isNaN(startProg)) { startProg = 0.85; }
        var overlapVh = parseFloat(d.overlapVh) || 0;

        var hero = document.querySelector('[data-okip-hero]');

        // Capas reales (nodos EXTERIORES = parallax). Reveal va por clase latcheada.
        var layers = [];
        section.querySelectorAll('[data-okip-pm-layer]').forEach(function (el) {
            var name = el.getAttribute('data-okip-pm-layer');
            layers.push({
                el: el,
                name: name,
                speed: parseFloat(el.dataset.speed) || 0,
                trigger: Math.max(parseRange(el.dataset.enter, 0, 1)[0], 0.03),
                revealClass: REVEAL_CLASS[name] || ('is-' + name + '-revealed')
            });
        });

        // Computadora: video que puede arrancar al entrar su capa.
        var monitor = section.querySelector('[data-okip-pm-layer="computer"]');
        var cmpVideo = section.querySelector('[data-okip-pm-screen-video]');
        var cmpAutoplay = monitor && monitor.getAttribute('data-autoplay-on-enter') === '1';
        var cmpStarted = false;

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

        // Reveal latcheado por clase (compartido GSAP + vanilla).
        function setReveal(p) {
            for (var i = 0; i < layers.length; i++) {
                var L = layers[i];
                if (p >= L.trigger) { section.classList.add(L.revealClass); }
                else if (p <= 0.02) { section.classList.remove(L.revealClass); }
            }
            if (cmpAutoplay) {
                if (section.classList.contains('is-computer-revealed')) { playComputer(); }
                else if (p <= 0.02) { resetComputer(); }
            }
        }
        function revealAll() {
            layers.forEach(function (L) { section.classList.add(L.revealClass); });
            if (cmpAutoplay) { playComputer(); }
        }

        function vh() { return window.innerHeight || document.documentElement.clientHeight; }
        function overlapPx() { return (overlapVh / 100) * vh(); }

        function applyHeroRecede(p) {
            if (!hero) { return; }
            if (p <= 0.001) {
                hero.style.transform = '';
                hero.style.opacity = '';
                return;
            }
            hero.style.transformOrigin = 'center top';
            hero.style.transform = 'translate3d(0,' + lerp(0, 26, p).toFixed(2) + 'px,0) scale(' + lerp(1, 0.94, p).toFixed(4) + ')';
            hero.style.opacity = lerp(1, 0.62, p).toFixed(3);
        }

        var canTransition = animOn && transition && !reduceMotion && !isSmall && !!hero && layers.length > 0;

        /* ============================================================
           MODO ESTÁTICO: móvil / reduce-motion / sin transición.
           Sin overlap ni parallax: reveal latcheado al entrar (IO).
           ============================================================ */
        if (!canTransition) {
            section.classList.add('is-static');
            function staticReveal() { revealAll(); }
            if (!animOn || reduceMotion || !textReveal) {
                staticReveal();
            } else if ('IntersectionObserver' in window) {
                var ioS = new IntersectionObserver(function (entries, obs) {
                    entries.forEach(function (e) {
                        if (e.isIntersecting) { staticReveal(); obs.disconnect(); }
                    });
                }, { threshold: 0.2 });
                ioS.observe(section);
            } else {
                staticReveal();
            }
            return;
        }

        section.classList.add('is-transitioning');

        if (stReady()) {
            initGsapTransition();
        } else {
            initVanillaTransition();
        }

        /* ============================================================
           GSAP + ScrollTrigger: dos timelines scrubbed (suaves).
           ============================================================ */
        function initGsapTransition() {
            var gsap = window.gsap;

            // 1) TRANSICIÓN Hero → Bloque 2 (overlap + hundimiento del Hero).
            gsap.timeline({
                scrollTrigger: {
                    trigger: hero,
                    start: function () { return 'top+=' + (startProg * hero.offsetHeight) + ' top'; },
                    end: function () { return 'top+=' + (hero.offsetHeight + 0.15 * vh()) + ' top'; },
                    scrub: 0.6,
                    invalidateOnRefresh: true,
                    onUpdate: function (self) {
                        setReveal(self.progress);
                        section.style.pointerEvents = self.progress > 0.55 ? 'auto' : 'none';
                    },
                    onLeave: function () { setReveal(1); },
                    onLeaveBack: function () { setReveal(0); }
                }
            })
            .fromTo(section,
                { y: function () { return overlapPx(); } },
                { y: 0, ease: 'none' }, 0)
            .to(hero,
                { y: 26, scale: 0.94, opacity: 0.62, transformOrigin: 'center top', ease: 'none' }, 0);

            // 2) PARALLAX por capas (una sola timeline, drift paralelo por capa).
            if (parallaxOn) {
                var driftTl = gsap.timeline({
                    scrollTrigger: {
                        trigger: section,
                        start: 'top bottom',
                        end: 'bottom top',
                        scrub: 0.6,
                        invalidateOnRefresh: true
                    }
                });
                layers.forEach(function (L) {
                    if (!L.speed) { return; }
                    driftTl.fromTo(L.el, { y: L.speed * DRIFT }, { y: -L.speed * DRIFT, ease: 'none' }, 0);
                });
            }

            // Estado inicial coherente.
            setReveal(0);

            // Resize: recalcular medidas (start/end son funciones).
            var rt;
            window.addEventListener('resize', function () {
                window.clearTimeout(rt);
                rt = window.setTimeout(function () { window.ScrollTrigger.refresh(); }, 200);
            }, { passive: true });
        }

        /* ============================================================
           Fallback VANILLA: rAF + lerp (damping). Mismo sistema de clases.
           ============================================================ */
        function initVanillaTransition() {
            var current = 0;

            function targetProgress() {
                var h = hero.offsetHeight;
                if (!h || h <= 0) { return 0; }
                var rect = hero.getBoundingClientRect();
                var topDoc = rect.top + window.scrollY;
                var start = topDoc + h * startProg;
                var end = topDoc + h + vh() * 0.15; // ventana ampliada (suaviza)
                if (end <= start) { return window.scrollY >= start ? 1 : 0; }
                return clamp((window.scrollY - start) / (end - start), 0, 1);
            }
            function driftProgress() {
                var rect = section.getBoundingClientRect();
                var denom = (vh() + rect.height) / 2;
                if (denom <= 0) { return 0; }
                return clamp(((vh() / 2) - (rect.top + rect.height / 2)) / denom, -1, 1);
            }

            function applyFrame(p) {
                section.style.transform = 'translate3d(0,' + ((1 - p) * overlapPx()).toFixed(2) + 'px,0)';
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
                    active = false;
                    current = targetProgress(); // estado coherente al pausar
                    applyFrame(current);
                }
            }

            if ('IntersectionObserver' in window) {
                var vis = { hero: false, section: false };
                var io = new IntersectionObserver(function (entries) {
                    entries.forEach(function (e) {
                        if (e.target === hero) { vis.hero = e.isIntersecting; }
                        else if (e.target === section) { vis.section = e.isIntersecting; }
                    });
                    setActive(vis.hero || vis.section);
                }, { threshold: 0, rootMargin: '30% 0px 30% 0px' });
                io.observe(section);
                if (hero) { io.observe(hero); }
            } else {
                active = true;
                window.requestAnimationFrame(loop);
            }

            applyFrame(0);
            window.addEventListener('resize', function () {
                isSmall = !!(window.matchMedia && window.matchMedia('(max-width: 880px)').matches);
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
