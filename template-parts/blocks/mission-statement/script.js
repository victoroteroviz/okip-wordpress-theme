/*
 * Bloque Mission Statement — reveal de texto por scroll, sin pines.
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
