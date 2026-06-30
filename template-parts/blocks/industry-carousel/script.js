/*
 * Bloque Industry Carousel (Bloque 3) — scroll-driven (rediseño oscuro).
 * Scope por instancia via [data-okip-ic].
 *
 * Desktop con GSAP + ScrollTrigger (>disable_below px):
 *   UN SOLO ScrollTrigger pin+scrub.
 *   - start: 'top top' (cuando el bloque toca el tope del viewport).
 *   - end: distancia real para alinear desde la primera hasta la última tarjeta a la
 *     izquierda del inset (medidas reales offsetLeft, invalidateOnRefresh).
 *   - El track se mueve con transform inline (GSAP tween).
 *   - Índice activo (tarjeta + botón resaltado): Math.round(progress * (N-1)).
 *   - Relleno de los botones (progreso segmentado): anteriores 100%, el actual con
 *     el progreso local (segment - floor), posteriores 0%. En el último slide el
 *     último botón se llena 100%.
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

        var nav         = section.querySelector('.okip-ic__nav');
        var track       = section.querySelector('.okip-ic__track');
        var items       = OKIP.toArray(section.querySelectorAll('.okip-ic__item'));
        var orangeTexts = OKIP.toArray(section.querySelectorAll('.okip-ic__orange-text'));
        var navBtns     = OKIP.toArray(section.querySelectorAll('.okip-ic__nav-btn'));
        var orangeSr    = section.querySelector('.okip-ic__orange-sr');

        // DOM como única fuente de verdad: el conteo y los índices salen de los nodos
        // reales (items.length), no de ningún atributo en el markup.
        var itemCount = items.length || 1;
        items.forEach(function (el, i) { el.dataset.index = String(i); });
        orangeTexts.forEach(function (el, i) { el.dataset.index = String(i); });
        navBtns.forEach(function (el, i) { el.dataset.index = String(i); });

        /* ---- Estado activo (tarjeta + botón resaltado + texto naranja) ---- */
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
            navBtns.forEach(function (el) {
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

        // Relleno de los botones según el progreso global (0..1).
        // segment = progress * (N-1); el botón floor(segment) se llena con el progreso
        // local, los anteriores al 100% y los posteriores a 0%. En el último slide el
        // último botón se rellena por completo.
        function setFill(btn, f) {
            btn.style.setProperty('--okip-ic-fill', f.toFixed(4));
        }
        function updateFills(progress) {
            if (!navBtns.length) { return; }
            var seg      = progress * (itemCount - 1);
            var floorIdx = Math.floor(seg + 1e-6);
            var local    = seg - floorIdx;
            navBtns.forEach(function (btn, j) {
                setFill(btn, j < floorIdx ? 1 : (j === floorIdx ? local : 0));
            });
            if (progress >= 0.999) {
                setFill(navBtns[navBtns.length - 1], 1);
            }
        }
        // Relleno escalonado para el modo estático (sin progreso continuo).
        function fillUpTo(idx) {
            navBtns.forEach(function (btn, j) { setFill(btn, j <= idx ? 1 : 0); });
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
                    if (bestIdx >= 0) { setActive(bestIdx); fillUpTo(bestIdx); }
                }, { threshold: [0, 0.5, 1], root: strip });
                items.forEach(function (el) { io.observe(el); });
            }

            // Botones: scroll horizontal al ítem.
            navBtns.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var idx    = OKIP.readInt(btn.dataset.index, 0);
                    var target = items[idx];
                    if (target && track && track.parentElement) {
                        track.parentElement.scrollTo({ left: target.offsetLeft - 24, behavior: 'smooth' });
                    }
                    setActive(idx);
                    fillUpTo(idx);
                });
            });

            setActive(0);
            fillUpTo(0);
            return;
        }

        /* ============================================================
           GSAP + ScrollTrigger — desktop.

           Un solo ScrollTrigger maestro:
             start: 'top top'
             end:   distancia real para alinear de la primera a la última tarjeta
             pin:   true
             scrub: {scrub}

           El track (.okip-ic__track) se mueve con x: startX → endX.
           ============================================================ */
        var gsap = window.gsap;
        var ST   = window.ScrollTrigger;

        // Inset lateral resuelto (el mismo que la fila de botones). La tarjeta activa
        // alinea su borde izquierdo a este inset; se asoma la siguiente a la derecha.
        function inset() {
            if (nav) {
                var pl = parseFloat(window.getComputedStyle(nav).paddingLeft);
                if (isFinite(pl)) { return pl; }
            }
            return (section.clientWidth || window.innerWidth) * 0.05;
        }

        // Calcula el x inicial (1ª tarjeta alineada al inset) y el x final (última
        // tarjeta alineada al inset). Retorna { startX, endX, travel }.
        function calcCentering() {
            if (!track || !items.length) { return { startX: 0, endX: 0, travel: 0 }; }
            var pad   = inset();
            var first = items[0];
            var last  = items[items.length - 1];
            // offsetLeft es relativo al padre del ítem (el track), no al viewport.
            var startX = pad - first.offsetLeft;
            var endX   = pad - last.offsetLeft;
            return {
                startX: startX,
                endX:   endX,
                travel: Math.abs(startX - endX)
            };
        }

        // Inicializar la posición del track (1ª tarjeta alineada) sin animación.
        var initC = calcCentering();
        gsap.set(track, { x: initC.startX });

        // ENTRADA del Bloque 3 sobre el Bloque 2: el contenido aparece TARDE, solo cuando
        // el panel ya cubre casi todo el viewport (≈85%), no al asomar. Por eso el start
        // es `top 15%`. Con `from`, si GSAP faltara no habría estado oculto → nunca queda
        // invisible/atascado.
        var enterTargets = [nav, section.querySelector('.okip-ic__strip')].filter(Boolean);
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

        // ScrollTrigger maestro: pin + movimiento de cinta + relleno de botones.
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
                    updateFills(self.progress);
                },
                onLeave:     function () { setActive(itemCount - 1); updateFills(1); },
                onLeaveBack: function () { setActive(0); updateFills(0); }
            }
        });

        // Botones: navegar haciendo scroll al punto correcto del segmento.
        navBtns.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var idx   = OKIP.readInt(btn.dataset.index, 0);
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
                    fillUpTo(0);
                } else {
                    // Reposicionar el track al x inicial antes de refresh.
                    var c = calcCentering();
                    gsap.set(track, { x: c.startX });
                    ST.refresh();
                }
            }, 200);
        }, { passive: true });

        setActive(0);
        updateFills(0);
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
