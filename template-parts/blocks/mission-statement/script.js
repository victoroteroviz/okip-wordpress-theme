/*
 * Bloque Mission Statement — reveal de texto por scroll.
 *
 * Desktop con GSAP + ScrollTrigger: la sección se PINEA y el reveal de letras se
 * scrubbea sobre un rango de scroll dedicado (scroll_duration_vh) → la animación
 * se reproduce con MÁS recorrido, totalmente ligada al scroll.
 * Sin GSAP (pero desktop): fallback rAF ligado a la posición del bloque (sin pin).
 * Móvil/tablet ≤disable_below o reduce-motion: todo visible (revealStatic).
 */
(function () {
    'use strict';

    var reduceMotion = (window.OKIP && typeof window.OKIP.reduceMotion === 'boolean')
        ? window.OKIP.reduceMotion
        : !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);

    var blocks = document.querySelectorAll('[data-okip-ms]');
    var scrollBlocks = [];
    var ticking = false;

    if (!blocks.length) {
        return;
    }

    function stReady() {
        return !!(window.okipGsap && window.okipGsap.ready && window.gsap &&
                  window.okipGsap.hasScrollTrigger && window.ScrollTrigger);
    }

    function clamp(value, min, max) {
        return Math.max(min, Math.min(max, value));
    }

    function vh() {
        return window.innerHeight || document.documentElement.clientHeight || 1;
    }

    function dataFloat(block, key, fallback) {
        var value = parseFloat(block.dataset[key]);
        return isNaN(value) ? fallback : value;
    }

    function dataInt(block, key, fallback) {
        var value = parseInt(block.dataset[key], 10);
        return isNaN(value) ? fallback : value;
    }

    function isSmallViewport(block) {
        var disableBelow = dataInt(block, 'disableBelow', 1024);
        return !!(window.matchMedia && window.matchMedia('(max-width: ' + disableBelow + 'px)').matches);
    }

    function timing(block) {
        var start = clamp(dataFloat(block, 'textRevealStart', 0.08), 0, 0.9);
        var end = clamp(dataFloat(block, 'textRevealEnd', 0.76), 0.1, 0.98);
        if (end <= start) {
            end = Math.min(0.98, start + 0.2);
        }
        return {
            start: start,
            end: end,
            span: Math.max(0.01, end - start),
            charWindow: clamp(dataFloat(block, 'charWindow', 0.34), 0.08, 0.65),
            kickerStart: clamp(dataFloat(block, 'kickerRevealStart', 0.56), 0, 0.98),
            kickerDuration: clamp(dataFloat(block, 'kickerRevealDuration', 0.14), 0.06, 0.5)
        };
    }

    function cacheNodes(block) {
        block.__okipMsChars = block.__okipMsChars || Array.prototype.slice.call(block.querySelectorAll('[data-okip-ms-char]'));
        block.__okipMsKicker = block.__okipMsKicker || block.querySelector('.okip-ms__kicker');
        return {
            chars: block.__okipMsChars,
            kicker: block.__okipMsKicker
        };
    }

    function setChar(node, progress) {
        var p = clamp(progress, 0, 1);
        var y = (1 - p) * 24;
        var blur = (1 - p) * 5;

        node.style.opacity = String(p);
        node.style.transform = 'translateY(' + y.toFixed(2) + 'px)';
        node.style.filter = 'blur(' + blur.toFixed(2) + 'px)';
    }

    function setMissionProgress(block, progress) {
        block.style.setProperty('--okip-ms-scroll', progress.toFixed(3));
        block.style.setProperty('--okip-ms-glow-boost', (progress * 0.28).toFixed(3));
        block.style.setProperty('--okip-ms-glow-shift', (progress * 8).toFixed(2) + '%');
        block.style.setProperty('--okip-ms-glow-scale', (progress * 0.18).toFixed(3));
    }

    /* Pinta el reveal completo (letras + kicker + glow) para un progress 0..1.
       Lo usan tanto el camino GSAP pineado (self.progress) como el fallback rAF. */
    function renderReveal(block, progress) {
        var nodes = cacheNodes(block);
        var chars = nodes.chars;
        var total = Math.max(chars.length, 1);
        var t = timing(block);
        var textProgress = clamp((progress - t.start) / t.span, 0, 1);
        var spread = Math.max(0, 1 - t.charWindow);

        setMissionProgress(block, progress);

        chars.forEach(function (char, index) {
            var startAt = total > 1 ? (index / (total - 1)) * spread : 0;
            var local = clamp((textProgress - startAt) / t.charWindow, 0, 1);
            setChar(char, local);
        });

        if (nodes.kicker) {
            var kickerProgress = clamp((progress - t.kickerStart) / t.kickerDuration, 0, 1);
            setChar(nodes.kicker, kickerProgress);
        }

        block.classList.toggle('is-visible', progress >= 0.98);
    }

    function revealStatic(block) {
        var nodes = cacheNodes(block);

        setMissionProgress(block, 1);
        nodes.chars.forEach(function (char) {
            setChar(char, 1);
        });
        if (nodes.kicker) {
            setChar(nodes.kicker, 1);
        }

        block.classList.add('is-visible', 'is-static');
    }

    /* ---- Fallback rAF (desktop, sin GSAP): progreso por posición del bloque ---- */
    function updateScrollBlock(block) {
        var viewport = vh();
        var rect = block.getBoundingClientRect();
        var doc = document.documentElement;
        var scrollY = window.pageYOffset || doc.scrollTop || 0;
        var maxScroll = Math.max(0, doc.scrollHeight - viewport);
        var blockTop = rect.top + scrollY;
        var startVh = dataInt(block, 'scrollStartVh', 108);
        var rangeVh = dataInt(block, 'scrollDurationVh', 150);
        var startScroll = blockTop - (viewport * startVh / 100);
        var endScroll = startScroll + (viewport * rangeVh / 100);
        var reachableEnd = Math.min(endScroll, maxScroll);
        var range = Math.max(1, reachableEnd - startScroll);
        var progress = clamp((scrollY - startScroll) / range, 0, 1);

        renderReveal(block, progress);
    }

    function updateAll() {
        ticking = false;
        scrollBlocks.forEach(updateScrollBlock);
    }

    function requestUpdate() {
        if (ticking) {
            return;
        }
        ticking = true;
        window.requestAnimationFrame(updateAll);
    }

    function initScrollLetters(block) {
        cacheNodes(block);
        scrollBlocks.push(block);
        updateScrollBlock(block);
    }

    /* ---- Camino GSAP: pin + scrub del reveal sobre rango dedicado ---- */
    function initPinnedReveal(block) {
        var ST = window.ScrollTrigger;
        var msId = block.id || block.dataset.blockInstance || 'ms';
        var revealVh = dataInt(block, 'scrollDurationVh', 200);

        cacheNodes(block);
        renderReveal(block, 0);

        block.__okipMsReveal = ST.create({
            id: msId + '-reveal',
            trigger: block,
            start: 'top top',
            end: function () {
                return '+=' + Math.round(vh() * (revealVh / 100));
            },
            pin: true,
            pinSpacing: true,
            scrub: true,
            anticipatePin: 1,
            invalidateOnRefresh: true,
            refreshPriority: -11,
            onUpdate: function (self) {
                renderReveal(block, self.progress);
            },
            onRefresh: function (self) {
                renderReveal(block, self.progress);
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
                if (isSmallViewport(block) && block.__okipMsReveal) {
                    block.__okipMsReveal.kill();
                    block.__okipMsReveal = null;
                    if (window.gsap) {
                        var nodes = cacheNodes(block);
                        window.gsap.set([].concat(nodes.chars, nodes.kicker ? [nodes.kicker] : []), {
                            clearProps: 'opacity,transform,filter'
                        });
                    }
                    revealStatic(block);
                }
            }, 200);
        }, { passive: true });
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

        if (block.dataset.anim !== '1' || reduceMotion || isSmallViewport(block)) {
            revealStatic(block);
            return;
        }

        // GSAP disponible → pin + scrub (más scroll para reproducir la animación).
        if (block.dataset.textAnim === 'scroll-letters' && stReady()) {
            initPinnedReveal(block);
            return;
        }

        // Fallback rAF (sin GSAP) ligado a la posición del bloque.
        if (block.dataset.textAnim === 'scroll-letters' && block.dataset.useVanilla === '1') {
            initScrollLetters(block);
            return;
        }

        initOneShotReveal(block);
    });

    if (scrollBlocks.length) {
        window.addEventListener('scroll', requestUpdate, { passive: true });
        window.addEventListener('resize', requestUpdate);
        requestUpdate();
    }
})();
