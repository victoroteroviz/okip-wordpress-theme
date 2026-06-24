/*
 * Bloque Mission Statement — texto y brillo controlados por scroll.
 */
(function () {
    'use strict';

    var reduceMotion = !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
    var blocks = document.querySelectorAll('[data-okip-ms]');
    var scrollBlocks = [];
    var ticking = false;

    if (!blocks.length) {
        return;
    }

    function clamp(value, min, max) {
        return Math.max(min, Math.min(max, value));
    }

    function revealStatic(block) {
        block.style.setProperty('--okip-ms-scroll', '1');
        block.style.setProperty('--okip-ms-glow-boost', '0.28');
        block.style.setProperty('--okip-ms-glow-shift', '8%');
        block.style.setProperty('--okip-ms-glow-scale', '0.18');
        block.classList.add('is-visible');
    }

    function setChar(char, progress) {
        var y = (1 - progress) * 20;
        var blur = (1 - progress) * 4;

        char.style.opacity = String(progress);
        char.style.transform = 'translateY(' + y.toFixed(2) + 'px)';
        char.style.filter = 'blur(' + blur.toFixed(2) + 'px)';
    }

    function updateScrollBlock(block) {
        var vh = window.innerHeight || document.documentElement.clientHeight || 1;
        var rect = block.getBoundingClientRect();
        var doc = document.documentElement;
        var scrollY = window.pageYOffset || doc.scrollTop || 0;
        var maxScroll = Math.max(0, doc.scrollHeight - vh);
        var blockTop = rect.top + scrollY;
        var startScroll = blockTop - (vh * 0.88);
        var endScroll = blockTop - (vh * 0.42);
        var reachableEnd = Math.min(endScroll, maxScroll);
        var range = Math.max(1, reachableEnd - startScroll);
        var progress = clamp((scrollY - startScroll) / range, 0, 1);
        var chars = block.__okipMsChars || [];
        var total = Math.max(chars.length, 1);

        block.style.setProperty('--okip-ms-scroll', progress.toFixed(3));
        block.style.setProperty('--okip-ms-glow-boost', (progress * 0.28).toFixed(3));
        block.style.setProperty('--okip-ms-glow-shift', (progress * 8).toFixed(2) + '%');
        block.style.setProperty('--okip-ms-glow-scale', (progress * 0.18).toFixed(3));

        chars.forEach(function (char, index) {
            var startAt = (index / total) * 0.76;
            var local = clamp((progress - startAt) / 0.18, 0, 1);
            setChar(char, local);
        });

        if (block.__okipMsKicker) {
            var kickerProgress = clamp((progress - 0.82) / 0.16, 0, 1);
            setChar(block.__okipMsKicker, kickerProgress);
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
        block.__okipMsChars = Array.prototype.slice.call(block.querySelectorAll('[data-okip-ms-char]'));
        block.__okipMsKicker = block.querySelector('.okip-ms__kicker');
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

        if (block.dataset.anim !== '1' || reduceMotion) {
            revealStatic(block);
            return;
        }

        if (block.dataset.textAnim === 'scroll-letters') {
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
