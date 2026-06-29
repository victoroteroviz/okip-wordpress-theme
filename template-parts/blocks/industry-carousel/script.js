/*
 * Bloque Industry Carousel (Bloque 3) — scroll-driven.
 * Scope por instancia via [data-okip-ic].
 *
 * Desktop con GSAP + ScrollTrigger (>disable_below px):
 *   UN SOLO ScrollTrigger pin+scrub.
 *   - start: 'top top' (cuando el bloque toca el tope del viewport).
 *   - end: calculado para que la cinta recorra desde el primer ítem centrado
 *     hasta el último ítem centrado (medidas reales, invalidateOnRefresh).
 *   - La cinta se mueve con transform inline (GSAP tween).
 *   - Índice activo: Math.round(progress * (itemCount-1)).
 *   - Texto naranja: cambia con setActive(idx).
 *   No hay ST separado de overlay (causa conflicto con el pin).
 *
 * Móvil / sin GSAP: is-static, scroll horizontal nativo, IO para activo.
 *
 * No autoplay. No intervalos. Respeta prefers-reduced-motion.
 */
(function () {
    'use strict';

    var reduceMotion = !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);

    function stReady() {
        return !!(window.okipGsap && window.okipGsap.ready && window.gsap &&
                  window.okipGsap.hasScrollTrigger && window.ScrollTrigger);
    }

    function initIc(section) {
        if (section.__okipIcInit) { return; }
        section.__okipIcInit = true;

        var OKIP = window.OKIP;
        var d            = section.dataset;
        var animOn       = d.anim          === '1';
        var pinOn        = d.pin           === '1';
        var disableBelow = OKIP.readInt(d.disableBelow, 1024);
        var scrub        = OKIP.readFloat(d.scrub, 1);

        var icId    = section.id || d.blockInstance || 'ic';
        var isSmall = !!(window.matchMedia && window.matchMedia('(max-width: ' + disableBelow + 'px)').matches);

        var track       = section.querySelector('.okip-ic__track');
        var items       = OKIP.toArray(section.querySelectorAll('.okip-ic__item'));
        var orangeTexts = OKIP.toArray(section.querySelectorAll('.okip-ic__orange-text'));
        var dots        = OKIP.toArray(section.querySelectorAll('.okip-ic__dot'));
        var orangeSr    = section.querySelector('.okip-ic__orange-sr');

        // DOM como única fuente de verdad: el conteo y los índices salen de los nodos
        // reales (items.length), no de ningún atributo en el markup.
        var itemCount = items.length || 1;
        items.forEach(function (el, i) { el.dataset.index = String(i); });
        orangeTexts.forEach(function (el, i) { el.dataset.index = String(i); });
        dots.forEach(function (el, i) { el.dataset.index = String(i); });

        /* ---- Estado activo ---- */
        var prevIdx = -1;
        function setActive(idx) {
            idx = OKIP.clamp(idx, 0, itemCount - 1);
            if (idx === prevIdx) { return; }
            prevIdx = idx;

            items.forEach(function (el, i) {
                el.classList.toggle('is-active', i === idx);
                // Vídeo: reproducir solo el activo.
                var v = el.querySelector('video');
                if (!v) { return; }
                if (i === idx) {
                    var pr = v.play();
                    if (pr && typeof pr.catch === 'function') { pr.catch(function () {}); }
                } else {
                    try { v.pause(); } catch (e) {}
                }
            });
            orangeTexts.forEach(function (el) {
                el.classList.toggle('is-active', OKIP.readInt(el.dataset.index, -1) === idx);
            });
            dots.forEach(function (el) {
                var di = OKIP.readInt(el.dataset.index, -1);
                el.classList.toggle('is-active', di === idx);
                el.setAttribute('aria-selected', di === idx ? 'true' : 'false');
                el.setAttribute('tabindex', di === idx ? '0' : '-1');
            });
            if (orangeSr) {
                var active = section.querySelector('.okip-ic__orange-text[data-index="' + idx + '"]');
                if (active) { orangeSr.textContent = active.textContent; }
            }
        }

        var canAnimate = animOn && !reduceMotion && !isSmall && itemCount > 1 && pinOn;

        /* ============================================================
           MODO ESTÁTICO: móvil, reduce-motion, sin GSAP o sin pin.
           ============================================================ */
        if (!canAnimate || !stReady()) {
            section.classList.add('is-static');

            // IO: actualizar activo al deslizar horizontalmente.
            if ('IntersectionObserver' in window && track) {
                var strip = track.parentElement;
                var io = new IntersectionObserver(function (entries) {
                    var bestIdx = -1, bestRatio = 0;
                    entries.forEach(function (e) {
                        if (e.intersectionRatio > bestRatio) {
                            bestRatio = e.intersectionRatio;
                            bestIdx   = OKIP.readInt(e.target.dataset.index, -1);
                        }
                    });
                    if (bestIdx >= 0) { setActive(bestIdx); }
                }, { threshold: [0, 0.5, 1], root: strip });
                items.forEach(function (el) { io.observe(el); });
            }

            // Dots: scroll horizontal al ítem.
            dots.forEach(function (dot) {
                dot.addEventListener('click', function () {
                    var idx    = OKIP.readInt(dot.dataset.index, 0);
                    var target = items[idx];
                    if (target && track && track.parentElement) {
                        track.parentElement.scrollTo({ left: target.offsetLeft - 24, behavior: 'smooth' });
                    }
                    setActive(idx);
                });
            });

            setActive(0);
            return;
        }

        /* ============================================================
           GSAP + ScrollTrigger — desktop.

           Un solo ScrollTrigger maestro:
             start: 'top top'
             end:   distancia real para centrar del primer al último ítem
             pin:   true
             scrub: {scrub}

           La cinta (.okip-ic__track) se mueve con x: startX → endX.
           ============================================================ */
        var gsap = window.gsap;
        var ST   = window.ScrollTrigger;

        // Calcula el x inicial (ítem 0 centrado) y el x final (ítem N-1 centrado).
        // Retorna { startX, endX, travel }.
        function calcCentering() {
            if (!track || !items.length) { return { startX: 0, endX: 0, travel: 0 }; }
            var vw      = section.clientWidth || window.innerWidth;
            var first   = items[0];
            var last    = items[items.length - 1];
            // offsetLeft es relativo al padre del ítem (el track), no al viewport.
            var firstCenter = first.offsetLeft + first.offsetWidth / 2;
            var lastCenter  = last.offsetLeft  + last.offsetWidth  / 2;
            var startX = vw / 2 - firstCenter;
            var endX   = vw / 2 - lastCenter;
            return {
                startX: startX,
                endX:   endX,
                travel: Math.abs(startX - endX)
            };
        }

        // Inicializar la posición del track (ítem 0 centrado) sin animación.
        var initC = calcCentering();
        gsap.set(track, { x: initC.startX });

        // ENTRADA del Bloque 3 sobre el Bloque 2: el contenido aparece TARDE, solo cuando
        // el panel blanco ya cubre casi todo el viewport (≈85%), no al asomar. Por eso el
        // start es `top 15%` y no `top 80%` (no es un pin; no toca `.okip-pm`). Con `from`,
        // si GSAP faltara no habría estado oculto → nunca queda invisible/atascado.
        var enterTargets = [section.querySelector('.okip-ic__content'), section.querySelector('.okip-ic__strip')]
            .filter(Boolean);
        if (enterTargets.length) {
            gsap.from(enterTargets, {
                y: 40,
                opacity: 0,
                duration: 0.7,
                ease: 'power2.out',
                stagger: 0.12,
                scrollTrigger: {
                    id:            icId + '-enter',
                    trigger:       section,
                    start:         'top 15%',
                    toggleActions: 'play none none none'
                }
            });
        }

        // Índice activo por progreso.
        function progressToIdx(p) {
            return Math.round(p * (itemCount - 1));
        }

        // ScrollTrigger maestro: pin + movimiento de cinta.
        var pinTween = gsap.to(track, {
            x: function () { return calcCentering().endX; },
            ease: 'none',
            scrollTrigger: {
                id:                  icId + '-pin',
                trigger:             section,
                start:               'top top',
                end: function () {
                    return '+=' + calcCentering().travel;
                },
                pin:                 true,
                pinSpacing:          true,
                scrub:               scrub,
                anticipatePin:       1,
                invalidateOnRefresh: true,
                onUpdate: function (self) {
                    setActive(progressToIdx(self.progress));
                },
                onLeave:     function () { setActive(itemCount - 1); },
                onLeaveBack: function () { setActive(0); }
            }
        });

        // Dots: navegar haciendo scroll al punto correcto.
        dots.forEach(function (dot) {
            dot.addEventListener('click', function () {
                var idx   = OKIP.readInt(dot.dataset.index, 0);
                var stPin = ST.getById(icId + '-pin');
                if (!stPin) { return; }
                var progress = itemCount > 1 ? idx / (itemCount - 1) : 0;
                window.scrollTo({
                    top:      stPin.start + (stPin.end - stPin.start) * progress,
                    behavior: 'smooth'
                });
            });
        });

        // Resize: recalcular. Si pasa a pequeño, matar STs y modo estático.
        var rt;
        window.addEventListener('resize', function () {
            window.clearTimeout(rt);
            rt = window.setTimeout(function () {
                var nowSmall = !!(window.matchMedia && window.matchMedia('(max-width: ' + disableBelow + 'px)').matches);
                if (nowSmall) {
                    ST.getAll().forEach(function (st) {
                        if (st.vars && st.vars.id && String(st.vars.id).indexOf(icId + '-') === 0) {
                            st.kill();
                        }
                    });
                    gsap.set(track, { clearProps: 'x' });
                    // Limpiar el estado de entrada para que el contenido no quede oculto en móvil.
                    if (enterTargets.length) {
                        gsap.set(enterTargets, { clearProps: 'opacity,transform' });
                    }
                    section.classList.add('is-static');
                    setActive(0);
                } else {
                    // Reposicionar el track al x inicial antes de refresh.
                    var c = calcCentering();
                    gsap.set(track, { x: c.startX });
                    ST.refresh();
                }
            }, 200);
        }, { passive: true });

        setActive(0);
    }

    function init() {
        document.querySelectorAll('[data-okip-ic]').forEach(initIc);
    }

    if (window.OKIP && window.OKIP.ready) {
        window.OKIP.ready(init);
    } else if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
