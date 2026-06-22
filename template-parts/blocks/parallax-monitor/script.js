/*
 * Bloque Parallax Monitor (Bloque 2) — comportamiento.
 * Scope por instancia via [data-okip-pm].
 *
 * TRANSICIÓN por PROGRESO de scroll (vanilla, sin GSAP, sin pin):
 *   start = heroTop + heroHeight * overlap_start (≈0.85)
 *   end   = heroTop + heroHeight
 *   progress = clamp((scrollY - start) / (end - start), 0, 1)
 * Con ese progreso:
 *   - el bloque sube SOBRE el Hero (counter-transform del overlap → 0),
 *   - las 3 capas entran en RITMOS distintos (rangos coreografiados),
 *   - el Hero se hunde/aleja ligeramente (solo si no hay GSAP),
 *   - parallax real por capas (drift según posición en viewport).
 *
 * IntersectionObserver SOLO activa/desactiva el bucle rAF (rendimiento). Mientras
 * está activo se calcula todo con getBoundingClientRect(). No bloquea scroll, no
 * duplica listeners, respeta prefers-reduced-motion y reduce/desactiva en móvil.
 *
 * Soporte GSAP futuro: el hundimiento del Hero se omite si GSAP está presente
 * (lo gestiona el scroll_3d del Hero); el resto es independiente de GSAP.
 */
(function () {
    'use strict';

    var BASE = 100; // px de drift de parallax por capa (× su data-speed)
    var ENTER_OFFSET = { background: 40, computer: 68, text: 26 }; // px de slide de entrada

    var reduceMotion = !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
    var isSmall = !!(window.matchMedia && window.matchMedia('(max-width: 880px)').matches);

    function gsapReady() {
        return !!(window.okipGsap && window.okipGsap.ready && window.gsap);
    }

    /* ---------- Utilidades de mapeo ---------- */
    function clamp(v, a, b) { return v < a ? a : (v > b ? b : v); }
    function map(v, a, b) { return (b === a) ? (v >= b ? 1 : 0) : clamp((v - a) / (b - a), 0, 1); }
    function smoothstep(t) { return t * t * (3 - 2 * t); }
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

    function initPm(section) {
        if (section.__okipPmInit) { return; }
        section.__okipPmInit = true;

        var d = section.dataset;
        var animOn      = d.anim === '1';
        var transition  = d.transition === '1';
        var parallaxOn  = d.parallax === '1';
        var textReveal  = d.textReveal === '1';

        var startProg = parseFloat(d.overlapStart);
        if (isNaN(startProg)) { startProg = 0.85; }
        var overlapVh = parseFloat(d.overlapVh) || 0;

        var hero = document.querySelector('[data-okip-hero]');

        // Capas reales (background / computer / text).
        var layers = [];
        section.querySelectorAll('[data-okip-pm-layer]').forEach(function (el) {
            var name = el.getAttribute('data-okip-pm-layer');
            layers.push({
                el: el,
                name: name,
                speed: parseFloat(el.dataset.speed) || 0,
                range: parseRange(el.dataset.enter, 0, 1),
                offset: ENTER_OFFSET[name] || 30
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
            var p = cmpVideo.play();
            if (p && typeof p.catch === 'function') { p.catch(function () {}); }
        }
        function resetComputer() {
            if (!cmpVideo || !cmpStarted) { return; }
            cmpStarted = false;
            try { cmpVideo.pause(); cmpVideo.currentTime = 0; } catch (e) {}
        }

        var canTransition = animOn && transition && !reduceMotion && !isSmall && !!hero && layers.length > 0;

        /* ============================================================
           MODO ESTÁTICO: móvil / reduce-motion / sin transición.
           Sin overlap ni parallax: solo revelado de entrada por IO.
           ============================================================ */
        if (!canTransition) {
            section.classList.add('is-static');

            function staticReveal() {
                section.classList.add('is-revealed');
                if (cmpAutoplay) { playComputer(); }
            }

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

        /* ============================================================
           MODO TRANSICIÓN (desktop, sin reduce-motion).
           ============================================================ */
        section.classList.add('is-transitioning');
        var heroRecede = !gsapReady(); // si hay GSAP, el Hero lo anima su scroll_3d

        function vh() { return window.innerHeight || document.documentElement.clientHeight; }

        function transitionProgress() {
            var rect = hero.getBoundingClientRect();
            var topDoc = rect.top + window.scrollY;
            var h = hero.offsetHeight || rect.height || vh();
            var start = topDoc + h * startProg;
            var end = topDoc + h;
            if (end <= start) { return window.scrollY >= start ? 1 : 0; }
            return clamp((window.scrollY - start) / (end - start), 0, 1);
        }

        function driftProgress() {
            var rect = section.getBoundingClientRect();
            var denom = (vh() + rect.height) / 2;
            if (denom <= 0) { return 0; }
            return clamp(((vh() / 2) - (rect.top + rect.height / 2)) / denom, -1, 1);
        }

        function applyHero(p) {
            if (!heroRecede || !hero) { return; }
            if (p <= 0) {
                hero.style.transform = '';
                hero.style.opacity = '';
                return;
            }
            hero.style.transformOrigin = 'center top';
            hero.style.transform = 'translate3d(0,' + (lerp(0, 28, p)).toFixed(2) + 'px,0) scale(' + lerp(1, 0.94, p).toFixed(4) + ')';
            hero.style.opacity = lerp(1, 0.6, p).toFixed(3);
        }

        function render() {
            var p = transitionProgress();
            var overlapPx = (overlapVh / 100) * vh();

            // El bloque sube sobre el Hero: counter-transform de overlap → 0.
            section.style.transform = 'translate3d(0,' + ((1 - p) * overlapPx).toFixed(2) + 'px,0)';
            // Solo interactuable cuando ya es la escena (evita robar clics en la transición).
            section.style.pointerEvents = p > 0.6 ? 'auto' : 'none';

            var dp = parallaxOn ? driftProgress() : 0;
            var cmpLp = 0;

            for (var i = 0; i < layers.length; i++) {
                var L = layers[i];
                var lp = smoothstep(map(p, L.range[0], L.range[1]));
                if (L.name === 'computer') { cmpLp = lp; }
                var enterY = (1 - lp) * L.offset;
                var driftY = dp * L.speed * BASE;
                var ty = enterY + driftY;
                var sc = lerp(0.965, 1, lp);
                L.el.style.opacity = lp.toFixed(3);
                L.el.style.transform = 'translate3d(0,' + ty.toFixed(2) + 'px,0) scale(' + sc.toFixed(4) + ')';
            }

            // Autoplay de la computadora cuando su capa ya entró; reset al volver.
            if (cmpAutoplay) {
                if (cmpLp > 0.6) { playComputer(); }
                else if (p < layers.reduce(function (m, L) { return L.name === 'computer' ? L.range[0] : m; }, 0.25)) { resetComputer(); }
            }

            applyHero(p);
        }

        /* ---------- Activación del bucle por IO (rendimiento) ---------- */
        var active = false;
        var rafId = 0;
        function loop() {
            if (!active) { rafId = 0; return; }
            render();
            rafId = window.requestAnimationFrame(loop);
        }
        function setActive(v) {
            if (v && !active) { active = true; if (!rafId) { rafId = window.requestAnimationFrame(loop); } }
            else { active = v; }
        }

        if ('IntersectionObserver' in window) {
            var visibleSet = { hero: false, section: false };
            var io = new IntersectionObserver(function (entries) {
                entries.forEach(function (e) {
                    if (e.target === hero) { visibleSet.hero = e.isIntersecting; }
                    else if (e.target === section) { visibleSet.section = e.isIntersecting; }
                });
                setActive(visibleSet.hero || visibleSet.section);
            }, { threshold: 0, rootMargin: '30% 0px 30% 0px' });
            io.observe(section);
            if (hero) { io.observe(hero); }
        } else {
            // Sin IO: bucle continuo (coste bajo) + recálculo en scroll/resize.
            active = true;
            window.requestAnimationFrame(loop);
        }

        // Primer frame inmediato (estado inicial coherente) y en resize.
        render();
        window.addEventListener('resize', function () { isSmall = !!(window.matchMedia && window.matchMedia('(max-width: 880px)').matches); render(); }, { passive: true });
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
