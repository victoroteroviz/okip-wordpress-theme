/*
 * Bloque Mission Statement — reveal POR PASOS (scroll-snap por gesto).
 *
 * Desktop con GSAP + ScrollTrigger: la sección se PINEA y avanza en 2 pasos
 * discretos; cada giro de rueda (snap direccional) coloca un estado:
 *   paso 1 → se anima la FRASE completa (líneas + línea final strong),
 *   paso 2 → se anima el KICKER ("CREANDO ENTORNOS SEGUROS").
 * El FONDO (glow azul) ya está presente al llegar — NO se anima (evita confusión
 * al bajar del bloque anterior). Cada estado se anima por CSS, no por scrub.
 *
 * Sin GSAP (desktop): reveal one-shot al entrar (todo visible).
 * Móvil/tablet ≤disable_below o reduce-motion: todo visible (revealStatic).
 */
(function () {
    'use strict';

    var STEPS = 2; // frase completa · kicker

    // OKIP garantizado por la cadena de deps (okip-app → gsap-init → animations → bloque).
    var OKIP = window.OKIP;

    var reduceMotion = (OKIP && typeof OKIP.reduceMotion === 'boolean')
        ? OKIP.reduceMotion
        : !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);

    var blocks = document.querySelectorAll('[data-okip-ms]');

    if (!blocks.length) {
        return;
    }

    function stReady() {
        return !!(window.okipGsap && window.okipGsap.ready && window.gsap &&
                  window.okipGsap.hasScrollTrigger && window.ScrollTrigger);
    }

    function vh() {
        return window.innerHeight || document.documentElement.clientHeight || 1;
    }

    function dataInt(block, key, fallback) {
        return OKIP.readInt(block.dataset[key], fallback);
    }

    function isSmallViewport(block) {
        var disableBelow = dataInt(block, 'disableBelow', 1024);
        return !!(window.matchMedia && window.matchMedia('(max-width: ' + disableBelow + 'px)').matches);
    }

    /* Cachea los grupos de caracteres y fija el stagger (transition-delay):
       - statement: TODA la frase (líneas normales + línea final strong),
       - kicker:    "CREANDO ENTORNOS SEGUROS". */
    function prepareGroups(block) {
        if (block.__okipMsGroups) { return block.__okipMsGroups; }

        var statement = Array.prototype.slice.call(
            block.querySelectorAll('.okip-ms__line [data-okip-ms-char]'));
        var kicker = block.querySelector('.okip-ms__kicker');

        // Sin stagger: la frase entra TODA A LA VEZ (más impacto).
        statement.forEach(function (c) { c.style.transitionDelay = '0s'; });

        block.__okipMsGroups = { statement: statement, kicker: kicker };
        return block.__okipMsGroups;
    }

    function setGroupOn(nodes, on) {
        nodes.forEach(function (node) { node.classList.toggle('is-on', on); });
    }

    /* Aplica el estado discreto de un paso (0..STEPS). Reversible.
       El fondo NO se gestiona aquí: está siempre visible. */
    function renderStep(block, step) {
        var g = prepareGroups(block);
        setGroupOn(g.statement, step >= 1);              // paso 1: frase completa
        if (g.kicker) { g.kicker.classList.toggle('is-on', step >= 2); } // paso 2: kicker
        block.dataset.msStep = String(step);
    }

    function revealStatic(block) {
        var g = prepareGroups(block);
        setGroupOn(g.statement, true);
        if (g.kicker) { g.kicker.classList.add('is-on'); }
        block.classList.add('is-visible', 'is-static');
    }

    /* ---- Camino GSAP: pin + snap direccional, 1 gesto = 1 paso ---- */
    function initSteppedReveal(block) {
        var ST = window.ScrollTrigger;
        var msId = block.id || block.dataset.blockInstance || 'ms';
        var totalVh = dataInt(block, 'scrollDurationVh', 180);

        block.classList.add('okip-ms--stepped');
        prepareGroups(block);
        renderStep(block, 0);

        block.__okipMsST = ST.create({
            id: msId + '-steps',
            trigger: block,
            start: 'top top',
            end: function () {
                return '+=' + Math.round(vh() * (totalVh / 100));
            },
            pin: true,
            pinSpacing: true,
            anticipatePin: 1,
            invalidateOnRefresh: true,
            refreshPriority: -11,
            snap: {
                snapTo: [0, 1 / 2, 1],
                duration: { min: 0.12, max: 0.3 },
                delay: 0.01,
                ease: 'power2.inOut',
                directional: true
            },
            onUpdate: function (self) {
                renderStep(block, Math.round(self.progress * STEPS));
            },
            onRefresh: function (self) {
                renderStep(block, Math.round(self.progress * STEPS));
            }
        });

        attachResize(block);
    }

    /* Si la ventana encoge bajo el breakpoint, desmonta el pin y deja todo legible. */
    function attachResize(block) {
        var rt;
        window.addEventListener('resize', function () {
            window.clearTimeout(rt);
            rt = window.setTimeout(function () {
                if (isSmallViewport(block) && block.__okipMsST) {
                    block.__okipMsST.kill();
                    block.__okipMsST = null;
                    block.classList.remove('okip-ms--stepped');
                    revealStatic(block);
                }
            }, 200);
        }, { passive: true });
    }

    /* Perf: congela las animaciones del fondo (glow + viscous) cuando el bloque
       queda fuera del viewport, añadiendo/quitando .is-bg-paused. Es independiente
       del reveal (aplica en cualquier modo) y solo para el fondo gradiente (el modo
       media no anima). Sin IntersectionObserver no hace nada → el fondo sigue vivo. */
    function initBgVisibility(block) {
        if (!('IntersectionObserver' in window) ||
            !block.classList.contains('okip-ms--gradient')) {
            return;
        }

        var io = new IntersectionObserver(function (entries) {
            var entry = entries[0];
            block.classList.toggle('is-bg-paused', !entry.isIntersecting);
        }, { rootMargin: '200px 0px' });

        io.observe(block);
    }

    function initOneShotReveal(block) {
        if (!('IntersectionObserver' in window)) {
            revealStatic(block);
            return;
        }

        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) {
                    return;
                }
                revealStatic(block);
                observer.disconnect();
            });
        }, {
            threshold: 0.28
        });

        observer.observe(block);
    }

    blocks.forEach(function (block) {
        if (block.__okipMissionStatementInit) {
            return;
        }
        block.__okipMissionStatementInit = true;

        initBgVisibility(block);

        if (block.dataset.anim !== '1' || reduceMotion || isSmallViewport(block)) {
            revealStatic(block);
            return;
        }

        // GSAP + modo scroll-letters → reveal por pasos (pin + snap por gesto).
        if (stReady() && block.dataset.textAnim === 'scroll-letters') {
            initSteppedReveal(block);
            return;
        }

        // Otros modos / sin GSAP: reveal one-shot al entrar.
        initOneShotReveal(block);
    });
})();
