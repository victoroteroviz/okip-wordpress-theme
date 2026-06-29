/*
 * Bloque News — carrusel horizontal + transición cover-rise hacia Mission.
 *
 * Cover-rise (desktop, sin pin): News sube por scroll natural (z-index mayor,
 * fondo claro opaco) y CUBRE a Mission. Mientras lo hace:
 *   - el contenido de News entra con un fade + leve translate (orgánico),
 *   - Mission hace "depth-out" (lift + scale + fade) para dar profundidad.
 * Móvil/tablet ≤disable_below o reduce-motion: todo visible, sin transform.
 */
(function () {
    'use strict';

    // OKIP está garantizado: los scripts de bloque dependen de okip-animations →
    // okip-gsap-init → okip-app (ver inc/block-loader.php e inc/enqueue.php).
    var OKIP = window.OKIP;

    var blocks = document.querySelectorAll('[data-okip-news]');
    var reduceMotion = (OKIP && typeof OKIP.reduceMotion === 'boolean')
        ? OKIP.reduceMotion
        : !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);

    if (!blocks.length) {
        return;
    }

    function vh() {
        return window.innerHeight || document.documentElement.clientHeight || 1;
    }

    // Adaptadores (block, key) → dataset, delegando el parseo en las utilidades OKIP.
    function dataFloat(block, key, fallback) {
        return OKIP.readFloat(block.dataset[key], fallback);
    }

    function dataInt(block, key, fallback) {
        return OKIP.readInt(block.dataset[key], fallback);
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
            // Al PINEAR Mission, ScrollTrigger la envuelve en un .pin-spacer, así
            // que deja de ser hermano directo: hay que buscarla también DENTRO del
            // hermano previo (el spacer). Si no, el depth-out no encontraría Mission.
            if (node.querySelector) {
                var inner = node.querySelector('[data-okip-ms]');
                if (inner) {
                    return inner;
                }
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
        var dots = OKIP.toArray(block.querySelectorAll('[data-okip-news-dot]'));
        var items = OKIP.toArray(block.querySelectorAll('[data-okip-news-item]'));
        var prev = block.querySelector('[data-okip-news-prev]');
        var next = block.querySelector('[data-okip-news-next]');
        var activeIndex = 0;

        if (!track || !items.length) {
            return;
        }

        // DOM como fuente de verdad: el índice de cada dot es su posición real.
        dots.forEach(function (dot, index) {
            dot.setAttribute('data-okip-news-dot', String(index));
        });

        function update() {
            activeIndex = closestIndex(track, items);
            setActive(dots, activeIndex);
            if (prev) {
                prev.disabled = activeIndex <= 0;
            }
            if (next) {
                next.disabled = activeIndex >= items.length - 1;
            }
        }

        var requestUpdate = OKIP.rafThrottle(update);

        dots.forEach(function (dot) {
            dot.addEventListener('click', function () {
                // Fallback explícito (-1 → clamp a 0) y protección si el conteo cambió.
                var raw = parseInt(dot.getAttribute('data-okip-news-dot'), 10);
                var index = OKIP.clamp(isNaN(raw) ? 0 : raw, 0, items.length - 1);
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

        initCoverReveal(block);
    });

    function resetMission(mission) {
        if (!mission) {
            return;
        }
        mission.style.setProperty('--okip-ms-news-lift', '0px');
        mission.style.setProperty('--okip-ms-news-scale', '1');
        mission.style.setProperty('--okip-ms-news-fade', '1');
        mission.classList.remove('is-news-lifting');
    }

    function initCoverReveal(block) {
        var mission = previousMission(block);

        if (block.dataset.reveal !== '1' || reduceMotion || isSmallViewport(block)) {
            resetMission(mission);
            setReveal(block, 1, null);
            block.classList.add('is-revealed');
            return;
        }

        function updateReveal() {
            if (isSmallViewport(block)) {
                resetMission(mission);
                setReveal(block, 1, null);
                block.classList.add('is-revealed');
                return;
            }
            var viewport = vh();
            var rect = block.getBoundingClientRect();
            var start = dataFloat(block, 'revealStart', .95);
            var end = dataFloat(block, 'revealEnd', .42);
            if (start <= end) {
                start = end + .25;
            }
            var startPx = viewport * start;
            var endPx = viewport * end;
            var progress = OKIP.clamp((startPx - rect.top) / Math.max(1, startPx - endPx), 0, 1);
            setReveal(block, progress, mission);
            block.classList.toggle('is-revealed', progress >= .98);
        }

        var requestReveal = OKIP.rafThrottle(updateReveal);

        window.addEventListener('scroll', requestReveal, { passive: true });
        window.addEventListener('resize', requestReveal);
        updateReveal();
    }

    function setReveal(block, progress, mission) {
        var p = OKIP.clamp(progress, 0, 1);
        var viewport = vh();
        var liftVh = dataFloat(block, 'revealMissionLiftVh', 16);

        // Contenido de News: entra con fade + leve translate hacia arriba.
        var contentOpacity = OKIP.clamp(p / 0.55, 0, 1).toFixed(3);
        var contentY = (26 * (1 - p)).toFixed(2) + 'px';
        block.style.setProperty('--okip-news-content-opacity', contentOpacity);
        block.style.setProperty('--okip-news-content-y', contentY);

        // Mission: depth-out (lift + scale + fade) mientras News la cubre.
        if (mission && !reduceMotion) {
            var lift = Math.round(viewport * (liftVh / 100) * p * -1) + 'px';
            var scale = (1 - 0.04 * p).toFixed(4);
            var fade = (1 - 0.45 * p).toFixed(3);
            mission.style.setProperty('--okip-ms-news-lift', lift);
            mission.style.setProperty('--okip-ms-news-scale', scale);
            mission.style.setProperty('--okip-ms-news-fade', fade);
            mission.classList.toggle('is-news-lifting', p > 0.001);
        }
    }
})();
