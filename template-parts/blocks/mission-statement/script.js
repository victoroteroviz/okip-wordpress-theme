/*
 * Bloque Mission Statement — reveal progresivo con fallback visible.
 */
(function () {
    'use strict';

    var reduceMotion = !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
    var blocks = document.querySelectorAll('[data-okip-ms]');

    if (!blocks.length) {
        return;
    }

    function reveal(block) {
        block.classList.add('is-visible');
    }

    blocks.forEach(function (block) {
        if (block.__okipMissionStatementInit) {
            return;
        }
        block.__okipMissionStatementInit = true;

        if (block.dataset.anim !== '1' || reduceMotion) {
            reveal(block);
            return;
        }

        if (!('IntersectionObserver' in window)) {
            reveal(block);
            return;
        }

        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) {
                    return;
                }
                reveal(block);
                observer.disconnect();
            });
        }, {
            threshold: 0.28
        });

        observer.observe(block);
    });
})();
