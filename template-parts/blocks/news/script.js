/*
 * Bloque News — dots sincronizados con scroll horizontal nativo.
 */
(function () {
    'use strict';

    var blocks = document.querySelectorAll('[data-okip-news]');

    if (!blocks.length) {
        return;
    }

    function setActive(dots, activeIndex) {
        dots.forEach(function (dot, index) {
            var active = index === activeIndex;
            dot.classList.toggle('is-active', active);
            dot.setAttribute('aria-current', active ? 'true' : 'false');
        });
    }

    function scrollToIndex(track, items, index) {
        var target = items[index];
        if (!target) {
            return;
        }

        var left = target.offsetLeft - ((track.clientWidth - target.offsetWidth) / 2);
        if (typeof track.scrollTo === 'function') {
            track.scrollTo({
                left: left,
                behavior: 'smooth'
            });
        } else {
            track.scrollLeft = left;
        }
    }

    function closestIndex(track, items) {
        var viewportCenter = track.scrollLeft + (track.clientWidth / 2);
        var bestIndex = 0;
        var bestDistance = Infinity;

        items.forEach(function (item, index) {
            var itemCenter = item.offsetLeft + (item.offsetWidth / 2);
            var distance = Math.abs(itemCenter - viewportCenter);
            if (distance < bestDistance) {
                bestDistance = distance;
                bestIndex = index;
            }
        });

        return bestIndex;
    }

    blocks.forEach(function (block) {
        if (block.__okipNewsInit) {
            return;
        }
        block.__okipNewsInit = true;

        var track = block.querySelector('[data-okip-news-track]');
        var dots = Array.prototype.slice.call(block.querySelectorAll('[data-okip-news-dot]'));
        var items = Array.prototype.slice.call(block.querySelectorAll('[data-okip-news-item]'));
        var prev = block.querySelector('[data-okip-news-prev]');
        var next = block.querySelector('[data-okip-news-next]');
        var ticking = false;
        var activeIndex = 0;

        if (!track || !items.length) {
            return;
        }

        function update() {
            ticking = false;
            activeIndex = closestIndex(track, items);
            setActive(dots, activeIndex);
            if (prev) {
                prev.disabled = activeIndex <= 0;
            }
            if (next) {
                next.disabled = activeIndex >= items.length - 1;
            }
        }

        function requestUpdate() {
            if (ticking) {
                return;
            }
            ticking = true;
            window.requestAnimationFrame(update);
        }

        dots.forEach(function (dot) {
            dot.addEventListener('click', function () {
                var index = parseInt(dot.getAttribute('data-okip-news-dot'), 10) || 0;
                scrollToIndex(track, items, index);
            });
        });

        if (prev) {
            prev.addEventListener('click', function () {
                scrollToIndex(track, items, Math.max(0, activeIndex - 1));
            });
        }

        if (next) {
            next.addEventListener('click', function () {
                scrollToIndex(track, items, Math.min(items.length - 1, activeIndex + 1));
            });
        }

        track.addEventListener('scroll', requestUpdate, { passive: true });
        window.addEventListener('resize', requestUpdate);
        requestUpdate();
    });
})();
