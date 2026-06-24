/*
 * Bloque News — dots sincronizados con scroll horizontal nativo.
 */
(function () {
    'use strict';

    var blocks = document.querySelectorAll('[data-okip-news]');
    var reduceMotion = (window.OKIP && typeof window.OKIP.reduceMotion === 'boolean')
        ? window.OKIP.reduceMotion
        : !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);

    if (!blocks.length) {
        return;
    }

    function clamp(value, min, max) {
        return Math.max(min, Math.min(max, value));
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
        var disableBelow = dataInt(block, 'revealDisableBelow', 768);
        return !!(window.matchMedia && window.matchMedia('(max-width: ' + disableBelow + 'px)').matches);
    }

    function previousMission(block) {
        var node = block.previousElementSibling;
        while (node) {
            if (node.matches && node.matches('[data-okip-ms]')) {
                return node;
            }
            node = node.previousElementSibling;
        }
        return null;
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

        initSplitReveal(block);
    });

    function initSplitReveal(block) {
        var mission = previousMission(block);

        if (block.dataset.reveal !== '1' || reduceMotion || isSmallViewport(block)) {
            if (mission) {
                mission.style.setProperty('--okip-ms-news-lift', '0px');
                mission.classList.remove('is-news-lifting');
            }
            setReveal(block, 1, null);
            block.classList.add('is-revealed');
            return;
        }

        var tickingReveal = false;

        function updateReveal() {
            tickingReveal = false;
            if (isSmallViewport(block)) {
                if (mission) {
                    mission.style.setProperty('--okip-ms-news-lift', '0px');
                    mission.classList.remove('is-news-lifting');
                }
                setReveal(block, 1, null);
                block.classList.add('is-revealed');
                return;
            }
            var viewport = window.innerHeight || document.documentElement.clientHeight || 1;
            var rect = block.getBoundingClientRect();
            var start = dataFloat(block, 'revealStart', .98);
            var end = dataFloat(block, 'revealEnd', .38);
            if (start <= end) {
                start = end + .25;
            }
            var startPx = viewport * start;
            var endPx = viewport * end;
            var progress = clamp((startPx - rect.top) / Math.max(1, startPx - endPx), 0, 1);
            setReveal(block, progress, mission);
            block.classList.toggle('is-revealed', progress >= .98);
        }

        function requestReveal() {
            if (tickingReveal) {
                return;
            }
            tickingReveal = true;
            window.requestAnimationFrame(updateReveal);
        }

        window.addEventListener('scroll', requestReveal, { passive: true });
        window.addEventListener('resize', requestReveal);
        updateReveal();
    }

    function setReveal(block, progress, mission) {
        var p = clamp(progress, 0, 1);
        var viewport = window.innerHeight || document.documentElement.clientHeight || 1;
        var paperInset = dataFloat(block, 'revealPaperInset', 49);
        var liftVh = dataFloat(block, 'revealMissionLiftVh', 30);
        var clip = (paperInset * (1 - p)).toFixed(2) + '%';
        var contentY = (28 * (1 - p)).toFixed(2) + 'px';
        var opacity = clamp((p - 0.12) / 0.88, 0, 1).toFixed(3);
        var scale = (0.985 + (p * 0.015)).toFixed(4);
        var foldOpacity = clamp(Math.sin(p * Math.PI) * 1.15, 0, 1).toFixed(3);
        var lift = Math.round(viewport * (liftVh / 100) * p * -1) + 'px';

        block.style.setProperty('--okip-news-clip', clip);
        block.style.setProperty('--okip-news-paper-inset', clip);
        block.style.setProperty('--okip-news-fold-opacity', foldOpacity);
        block.style.setProperty('--okip-news-content-y', contentY);
        block.style.setProperty('--okip-news-content-opacity', opacity);
        block.style.setProperty('--okip-news-content-scale', scale);

        if (mission && !reduceMotion) {
            mission.style.setProperty('--okip-ms-news-lift', lift);
            mission.classList.toggle('is-news-lifting', p > 0.001);
        }
    }
})();
