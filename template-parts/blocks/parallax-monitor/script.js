/*
 * Bloque Parallax Monitor (Bloque 2) — comportamiento.
 * Scope por instancia via [data-okip-pm].
 *
 * Principios (reglas críticas del proyecto):
 *  - El ROOT `.okip-pm` NO recibe parallax (no se mueve como masa → nada "sube de golpe").
 *  - PARALLAX = transform inline SOLO en nodos EXTERIORES de capa
 *      (.okip-pm__bg / .okip-pm__monitor / .okip-pm__text).
 *  - REVEAL  = opacidad/translate por CLASE latcheada en nodos INTERIORES
 *      (.okip-pm__computer-reveal / .okip-pm__text-reveal).
 *      Nunca reveal y parallax en el mismo nodo.
 *
 * Con GSAP + ScrollTrigger:
 *   1) HERO STICKY: el Hero queda fijo por CSS y B2 lo cubre por flujo/z-index.
 *   2) COVER ENTRY: una capa fija de fondo cubre el Hero con progreso determinista.
 *   3) DEPTH ENTRY: un timeline maestro usa data-enter para revelar monitor → texto.
 *      Todas las capas reales terminan en y:0 antes de que B3 cubra B2.
 *   4) HOLD-PIN: el Bloque 2 se pinea sin espacio reservado; B3 sube encima
 *      como panel claro mientras la escena de B2 queda fija.
 *
 * Sin GSAP: fallback vanilla rAF (depth entry) + IO one-shot (reveal). Sin pin.
 * Móvil/tablet, reduce-motion o sin driver entran en is-static.
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
    function setPmSyncReady(ready) {
        document.documentElement.classList.toggle('is-pm-sync-ready', !!ready);
    }
    // is-pm-covering: hook CSS RESERVADO (cover en su rampa, aún no opaco). Hoy sin
    // consumidor; expuesto a propósito para enganches CSS futuros. La visibilidad del
    // navbar usa is-pm-covered (opaco), no esta.
    function setPmCovering(covering) {
        document.documentElement.classList.toggle('is-pm-covering', !!covering);
    }
    function setPmCovered(covered) {
        var de = document.documentElement;
        covered = !!covered;
        if (de.classList.contains('is-pm-covered') === covered) { return; }
        de.classList.toggle('is-pm-covered', covered);
        document.dispatchEvent(new CustomEvent('okip:pm-cover', {
            detail: { covered: covered }
        }));
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
        var parallaxOn = d.parallax   === '1';
        var useGsap    = d.useGsap    !== '0'; // true por defecto
        var useVanilla = d.useVanilla !== '0'; // true por defecto
        var bgPinOn    = d.bgPin      === '1';

        var DRIFT        = parseFloat(d.driftMax)       || 140;
        var bgPinVh      = parseFloat(d.bgPinVh)        || 100;
        var entryScrollVh = clamp(parseFloat(d.entryScrollVh) || 155, 100, 300);
        var coverDelayVh  = clamp(parseFloat(d.coverDelayVh) || 50, 0, 200);
        var coverStartVh  = clamp(parseFloat(d.coverStartVh) || 8, 1, 50);
        var coverRamp     = clamp(parseFloat(d.coverRamp) || 0.45, 0.05, 1);

        // ID de instancia para nombrar ScrollTriggers y evitar colisiones entre bloques.
        var pmId = section.id || section.dataset.blockInstance || 'pm';

        var hero = document.querySelector('[data-okip-hero]');

        // Breakpoint del overlap/pin complejo: en móvil/tablet (≤1024px) NO se usa pin
        // ni se empuja al Bloque 3; flujo vertical normal y entrada estática.
        var OVERLAP_BP = parseInt(d.overlapBp, 10) || 1024;
        function isSmallViewport() {
            return !!(window.matchMedia && window.matchMedia('(max-width:' + OVERLAP_BP + 'px)').matches);
        }
        var isSmall = isSmallViewport();

        // Bloque que sigue a B2 (capturado con el DOM limpio, ANTES de que ScrollTrigger
        // envuelva B2 en un .pin-spacer). Se usa para empujar B3 con margin-top INLINE
        // robusto, sin depender de `.okip-pm + .okip-ic` (que se rompe tras el pin-spacer).
        var followingBlock = section.nextElementSibling;
        var followingIsIc  = !!(followingBlock && followingBlock.hasAttribute('data-okip-ic'));

        // Capas (nodos EXTERIORES): solo para la profundidad de entrada.
        var layers = [];
        section.querySelectorAll('[data-okip-pm-layer]').forEach(function (el) {
            layers.push({
                el:          el,
                name:        el.getAttribute('data-okip-pm-layer'),
                speed:       parseFloat(el.dataset.speed) || 0,
                enter:       parseEnter(el.dataset.enter),
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

        // Fallback reveal: en GSAP la entrada la gobierna el timeline maestro;
        // sin GSAP, estas clases permiten mostrar todo con delays CSS.
        var revealed = false;
        function revealAll() {
            if (revealed) { return; }
            revealed = true;
            section.classList.add('is-bg-revealed', 'is-computer-revealed', 'is-text-revealed');
            if (cmpAutoplay) { playComputer(); }
        }

        // Sync del navbar SIN GSAP (estático/vanilla): emite el MISMO estado
        // compartido (is-pm-sync-ready + is-pm-covered/okip:pm-cover) que la ruta
        // GSAP, calculado por posición del bloque. Así el navbar sigue una única
        // señal y nunca recae en su propio getBoundingClientRect (evita la franja).
        function initCoverSyncFallback() {
            setPmSyncReady(true);
            var ticking = false;
            function evalCovered() {
                ticking = false;
                setPmCovered(section.getBoundingClientRect().top <= 1);
            }
            function onScroll() {
                if (!ticking) { ticking = true; window.requestAnimationFrame(evalCovered); }
            }
            evalCovered();
            window.addEventListener('scroll', onScroll, { passive: true });
            window.addEventListener('resize', onScroll, { passive: true });
        }

        function vh() { return window.innerHeight || document.documentElement.clientHeight; }
        function entryScrollDistance() {
            return vh() * (entryScrollVh / 100);
        }
        function entryExtraDistance() {
            return Math.max(0, entryScrollDistance() - vh());
        }
        function coverGuardDistance() {
            return vh() * (coverDelayVh / 100);
        }
        function parseEnter(value) {
            var parts = String(value || '').split(',');
            var start = parseFloat(parts[0]);
            var end = parseFloat(parts[1]);
            if (isNaN(start)) { start = 0; }
            if (isNaN(end)) { end = 1; }
            start = clamp(start, 0, 1);
            end = clamp(end, start, 1);
            return { start: start, end: end, duration: Math.max(end - start, 0.001) };
        }
        function layerProgress(progress, enter) {
            if (!enter) { return progress; }
            return clamp((progress - enter.start) / enter.duration, 0, 1);
        }
        function layer(name) {
            for (var i = 0; i < layers.length; i++) {
                if (layers[i].name === name) { return layers[i]; }
            }
            return null;
        }

        // El Hero NO se anima: queda estático y el Bloque 2 lo cubre (apilado). Sin recede.

        // En móvil/tablet (≤1024px) NO usamos overlap/pin complejo: la escena entra
        // estática (reveal inmediato) y el Bloque 3 fluye debajo con normalidad.
        var canAnimate = animOn && !reduceMotion && layers.length > 0 && !isSmall;

        /* ============================================================
           MODO ESTÁTICO: móvil/tablet, reduce-motion o sin capas/animación.
           Reveal inmediato, sin parallax ni pin. Flujo vertical limpio.
           ============================================================ */
        if (!canAnimate) {
            section.classList.add('is-static');
            revealAll();
            initCoverSyncFallback();
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
            initCoverSyncFallback();
        }

        /* ============================================================
           GSAP + ScrollTrigger.
           ============================================================ */
        function initGsap() {
            var gsap = window.gsap;
            var ST   = window.ScrollTrigger;

            section.classList.add('is-gsap');

            // El Hero NO se anima: queda ESTÁTICO (position:sticky en CSS, desktop) y el
            // Bloque 2 (z-index 2, opaco) sube por flujo natural y lo cubre (apilado).
            // Sin recede → contenido del Hero inmóvil y sin jank en el navbar.

            function forceLayersRest() {
                layers.forEach(function (L) {
                    if (L.speed) { gsap.set(L.el, { y: 0 }); }
                });
            }

            // Distancia que B3 debe esperar antes de empezar a cubrir B2: el sobrante del
            // depth-entry (más allá del primer viewport) + el hold estático (medio scroll),
            // descontando lo que ya tarda la propia altura de B2. Se traduce en margin-top.
            function followingBlockMargin() {
                var naturalDelay = Math.max(0, (section.offsetHeight || vh()) - vh());
                return Math.max(0, entryExtraDistance() + coverGuardDistance() - naturalDelay);
            }

            // Pin de B2: dura hasta que B3 llega al top del viewport (lo cubre por completo).
            // Se calcula con la ALTURA propia de B2 + el margin de B3, NO con offsetTop
            // (que deja de ser fiable cuando ScrollTrigger envuelve B2 en un .pin-spacer).
            //   distancia (B3.offsetTop − B2.offsetTop) ≡ B2.offsetHeight + margin(B3)
            function holdPinDistance() {
                var fallback = bgPinVh / 100 * vh();
                var base   = section.offsetHeight || vh();
                var margin = (followingBlock && followingIsIc) ? followingBlockMargin() : 0;
                var distance = base + margin;
                return distance > 0 ? distance : fallback;
            }

            // Empuja B3 con margin-top INLINE (autoridad sobre cualquier regla CSS y robusto
            // al .pin-spacer): mientras B2 está pineado y la escena revela + hace su hold,
            // B3 permanece bajo el viewport; solo después sube y cubre.
            function syncFollowingBlockDelay() {
                if (!followingBlock || !followingIsIc) { return; }
                followingBlock.style.marginTop = followingBlockMargin().toFixed(2) + 'px';
            }
            function clearFollowingBlockDelay() {
                if (followingBlock && followingIsIc) { followingBlock.style.marginTop = ''; }
            }

            syncFollowingBlockDelay();

            var cover = section.querySelector('[data-okip-pm-cover]');
            var coverSTs = [];
            // COVER_RAMP: fracción de la ventana del cover (start..top) en la que la
            // opacidad llega a 1. ATAR con cuidado al depth-entry: el cover DEBE quedar
            // opaco ANTES de que `computer`/`text` empiecen a revelarse, o aparecerían
            // sobre un Hero a medio cubrir. Con los defaults (cover_ramp 0.45 ·
            // computer_enter_range 0.28 sobre entry_scroll_vh 155) el cover cierra
            // (~0.45·~100vh) justo antes del reveal del monitor (~0.28·155vh). Si subes
            // cover_ramp o bajas computer_enter_range[0], revisa que se mantenga el orden.
            var COVER_RAMP = coverRamp;

            function coverStartDistance() {
                return Math.round(vh() * (coverStartVh / 100));
            }
            function setCoverHidden() {
                if (!cover) { return; }
                gsap.killTweensOf(cover);
                gsap.set(cover, { yPercent: 100, opacity: 0, visibility: 'hidden' });
                cover.classList.remove('is-active');
            }
            function setCoverProgress(progress) {
                if (!cover) { return; }
                var opacity = clamp(progress / COVER_RAMP, 0, 1);
                if (opacity <= 0) {
                    setCoverHidden();
                    setPmCovering(false);
                    setPmCovered(false);
                    return;
                }
                cover.classList.add('is-active');
                gsap.killTweensOf(cover);
                gsap.set(cover, { yPercent: 0, opacity: opacity, visibility: 'visible' });
                setPmCovering(opacity < 1);
                setPmCovered(opacity >= 1);
            }
            function setCoverBefore() {
                setCoverHidden();
                setPmCovering(false);
                setPmCovered(false);
            }
            function setCoverAfter() {
                setCoverHidden();
                setPmCovering(false);
                setPmCovered(true);
            }
            function killCoverStage() {
                coverSTs.forEach(function (st) { st.kill(); });
                coverSTs = [];
                setCoverBefore();
                setPmSyncReady(false);
            }
            function initCoverStage() {
                if (!cover) { return; }
                gsap.set(cover, { yPercent: 100, opacity: 0, visibility: 'hidden' });
                setPmSyncReady(true);
                coverSTs.push(ST.create({
                    id: pmId + '-cover-stage',
                    trigger: section,
                    start: function () { return 'top bottom-=' + coverStartDistance(); },
                    end: 'top top',
                    invalidateOnRefresh: true,
                    onEnter: function (self) { setCoverProgress(self.progress); },
                    onUpdate: function (self) { setCoverProgress(self.progress); },
                    onLeave: setCoverAfter,
                    onEnterBack: function (self) { setCoverProgress(self.progress); },
                    onLeaveBack: setCoverBefore,
                    onRefresh: function (self) {
                        if (!self.isActive && self.progress <= 0) {
                            setCoverBefore();
                        } else if (!self.isActive && self.progress >= 1) {
                            setCoverAfter();
                        } else {
                            setCoverProgress(self.progress);
                        }
                    }
                }));
            }

            initCoverStage();

            // 2) DEPTH ENTRY: el cover ya tapó el Hero; el scrub gobierna
            //    únicamente monitor → texto.
            //    Se extiende más allá del primer viewport; B3 espera esta distancia
            //    mas un colchon antes de empezar a cubrir la escena.
            if (parallaxOn) {
                var bg = layer('background');
                var cmp = layer('computer');
                var txt = layer('text');
                var bgInner = section.querySelector('.okip-pm__bg-inner');
                var computerReveal = section.querySelector('.okip-pm__computer-reveal');
                var textItems = Array.prototype.slice.call(section.querySelectorAll('.okip-pm__text-reveal > *'));

                if (bg) {
                    gsap.set(bg.el, { y: 0 });
                    if (bgInner) { gsap.set(bgInner, { opacity: 1 }); }
                }

                var entryTl = gsap.timeline({
                    scrollTrigger: {
                        id: pmId + '-depth-entry',
                        trigger: section,
                        start: 'top bottom',
                        end: function () { return '+=' + entryScrollDistance(); },
                        scrub: true,
                        invalidateOnRefresh: true,
                        onLeave: forceLayersRest
                    }
                });

                if (cmp) {
                    entryTl.fromTo(cmp.el, { y: cmp.speed * DRIFT }, { y: 0, ease: 'none', duration: cmp.enter.duration }, cmp.enter.start);
                    if (computerReveal) {
                        entryTl.fromTo(
                            computerReveal,
                            { opacity: 0, y: 46, scale: 0.955 },
                            { opacity: 1, y: 0, scale: 1, ease: 'none', duration: cmp.enter.duration },
                            cmp.enter.start
                        );
                    }
                    entryTl.call(function () {
                        section.classList.add('is-glow-revealed');
                        if (cmpAutoplay) { playComputer(); }
                    }, null, cmp.enter.start);
                }

                if (txt) {
                    entryTl.fromTo(txt.el, { y: txt.speed * DRIFT }, { y: 0, ease: 'none', duration: txt.enter.duration }, txt.enter.start);
                    if (textItems.length) {
                        entryTl.fromTo(
                            textItems,
                            { opacity: 0, y: 34 },
                            { opacity: 1, y: 0, ease: 'none', duration: txt.enter.duration },
                            txt.enter.start
                        );
                    }
                }
            } else {
                revealAll();
            }

            // 4) HOLD-PIN: B2 queda fijo sin reservar espacio (pinSpacing:false). B3
            //    (z-index 3) sube encima y cubre la escena estática de B2. El pin dura
            //    hasta que B3 cubre por completo el viewport; en ese punto B2 se libera
            //    y el pin del carrusel de B3 arranca limpio (handoff secuencial).
            var bgPinST = null;
            if (bgPinOn) {
                bgPinST = ST.create({
                    id:            pmId + '-bgpin',
                    trigger:       section,
                    start:         'top top',
                    end:           function () { return '+=' + holdPinDistance(); },
                    pin:           true,
                    pinSpacing:    false,
                    anticipatePin: 1,
                    invalidateOnRefresh: true,
                    onLeave:       forceLayersRest
                });
            }

            // Resize: en desktop solo refrescamos medidas; si se cruza a móvil/tablet
            // (≤1024px) se desmonta el overlap (mata el pin y quita el empuje de B3)
            // para caer a flujo vertical normal.
            var rt;
            window.addEventListener('resize', function () {
                window.clearTimeout(rt);
                rt = window.setTimeout(function () {
                    if (isSmallViewport()) {
                        if (bgPinST) { bgPinST.kill(); bgPinST = null; }
                        killCoverStage();
                        clearFollowingBlockDelay();
                        forceLayersRest();
                        ST.refresh();
                        return;
                    }
                    syncFollowingBlockDelay();
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
                return clamp((vh() - rect.top) / entryScrollDistance(), 0, 1);
            }
            function applyFrame() {
                var p = parallaxOn ? entryProgress() : 1;
                for (var i = 0; i < layers.length; i++) {
                    var L = layers[i];
                    if (L.speed) {
                        var lp = layerProgress(p, L.enter);
                        L.el.style.transform = 'translate3d(0,' + ((1 - lp) * L.speed * DRIFT).toFixed(2) + 'px,0)';
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
            initCoverSyncFallback();
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
