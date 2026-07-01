/*
 * OKIP — runtime reusable de animaciones.
 */
(function () {
    'use strict';

    var reduceMotion = !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);

    function gsapReady() {
        return !!(window.okipGsap && window.okipGsap.ready && window.gsap);
    }

    function toArray(list) {
        return Array.prototype.slice.call(list || []);
    }

    function parseConfig(root) {
        var node = root.querySelector('[data-okip-motion-config]');
        if (!node) { return null; }
        try {
            return JSON.parse(node.textContent || '{}');
        } catch (e) {
            return null;
        }
    }

    function ms(value, fallback) {
        value = parseInt(value, 10);
        return Number.isFinite(value) ? Math.max(0, value) : fallback;
    }

    function seconds(value, fallback) {
        return ms(value, fallback) / 1000;
    }

    function cssNumber(value, fallback) {
        value = parseFloat(value);
        return Number.isFinite(value) ? value : fallback;
    }

    function transform(stage, side) {
        var suffix = side === 'from' ? '_from' : '_to';
        var x = cssNumber(stage['x' + suffix], 0);
        var y = cssNumber(stage['y' + suffix], 0);
        var scale = cssNumber(stage['scale' + suffix], 1);
        var rotate = cssNumber(stage['rotate' + suffix], 0);
        return 'translate3d(' + x + 'px,' + y + 'px,0) rotate(' + rotate + 'deg) scale(' + scale + ')';
    }

    function filter(stage, side) {
        var suffix = side === 'from' ? '_from' : '_to';
        var blur = cssNumber(stage['blur' + suffix], 0);
        return blur > 0 ? 'blur(' + blur + 'px)' : 'none';
    }

    function styleFor(stage, side) {
        var suffix = side === 'from' ? '_from' : '_to';
        return {
            opacity: cssNumber(stage['opacity' + suffix], side === 'from' ? 0 : 1),
            transform: transform(stage, side),
            filter: filter(stage, side)
        };
    }

    function applyStyle(el, styles) {
        Object.keys(styles).forEach(function (key) {
            el.style[key] = styles[key];
        });
    }

    function clearTransition(el) {
        el.style.transition = '';
    }

    function stageDisabled(stage) {
        return !stage || !stage.enabled || stage.preset === 'none';
    }

    var playbackClasses = [
        'okip-motion-playback--variant-motion',
        'okip-motion-playback--slow-drift',
        'okip-motion-playback--pulse-field',
        'okip-motion-playback--float-soft',
        'okip-motion-playback--glow-pulse'
    ];

    function clearPlaybackClasses(el) {
        playbackClasses.forEach(function (className) {
            el.classList.remove(className);
        });
    }

    function create(root, config) {
        config = config || parseConfig(root) || {};
        var motion = config.motion || {};
        var selectors = config.selectors || {};
        var enabled = !!motion.enabled && !reduceMotion;
        var entered = {};
        var exited = false;
        var exitObserver = null;
        // Timers del stagger CSS (fallback sin GSAP). Se cancelan al cambiar de fase
        // (salida/re-entrada) para que en `replay` no se acumulen ni se pisen fases.
        var cssTimers = [];
        function clearCssTimers() {
            cssTimers.forEach(function (id) { window.clearTimeout(id); });
            cssTimers = [];
        }

        function elements(target) {
            var selector = selectors[target];
            return selector ? toArray(root.querySelectorAll(selector)) : [];
        }

        function stage(target, phase) {
            return motion[target] && motion[target][phase] ? motion[target][phase] : null;
        }

        function setFinal(target) {
            var items = elements(target);
            var entry = stage(target, 'entry');
            items.forEach(function (el) {
                clearTransition(el);
                applyStyle(el, entry ? styleFor(entry, 'to') : { opacity: 1, transform: 'none', filter: 'none' });
            });
            entered[target] = true;
        }

        function prepare(target) {
            var items = elements(target);
            var entry = stage(target, 'entry');
            if (!enabled || stageDisabled(entry)) {
                setFinal(target);
                return;
            }
            items.forEach(function (el) {
                clearTransition(el);
                applyStyle(el, styleFor(entry, 'from'));
                el.classList.add('okip-motion-prepared');
            });
        }

        function animateCss(items, stageConfig, side) {
            var duration = ms(stageConfig.duration_ms, 700);
            var stagger = ms(stageConfig.stagger_ms, 0);
            var delay = ms(stageConfig.delay_ms, 0);
            var to = styleFor(stageConfig, side || 'to');

            items.forEach(function (el, i) {
                cssTimers.push(window.setTimeout(function () {
                    el.style.transition = 'opacity ' + duration + 'ms ease, transform ' + duration + 'ms ease, filter ' + duration + 'ms ease';
                    applyStyle(el, to);
                }, delay + (stagger * i)));
            });
        }

        function animateGsap(items, stageConfig, fromSide, toSide, repeat) {
            var from = styleFor(stageConfig, fromSide || 'from');
            var to = styleFor(stageConfig, toSide || 'to');
            var vars = {
                opacity: to.opacity,
                transform: to.transform,
                filter: to.filter,
                duration: seconds(stageConfig.duration_ms, 700),
                delay: seconds(stageConfig.delay_ms, 0),
                stagger: seconds(stageConfig.stagger_ms, 0),
                ease: stageConfig.ease === 'none' ? 'none' : stageConfig.ease
            };
            if (repeat) {
                vars.repeat = -1;
                vars.yoyo = !!stageConfig.yoyo;
                vars.duration = vars.duration / Math.max(.1, cssNumber(stageConfig.speed, 1));
            }
            window.gsap.killTweensOf(items);
            return window.gsap.fromTo(items, from, vars);
        }

        function enter(target) {
            if (entered[target] && motion.replay_mode !== 'replay') {
                setFinal(target);
                return;
            }
            var items = elements(target);
            var entry = stage(target, 'entry');
            if (!items.length || !enabled || stageDisabled(entry)) {
                setFinal(target);
                return;
            }
            if (gsapReady()) {
                animateGsap(items, entry, 'from', 'to', false);
            } else {
                animateCss(items, entry, 'to');
            }
            entered[target] = true;
            root.classList.add('is-motion-entered-' + target);
        }

        function playback(target) {
            var items = elements(target);
            var play = stage(target, 'playback');
            if (!items.length || !enabled || stageDisabled(play)) {
                return;
            }
            items.forEach(function (el) {
                clearPlaybackClasses(el);
                el.classList.add('is-motion-enabled');
                el.style.setProperty('--okip-motion-duration', (seconds(play.duration_ms, 4200) / Math.max(.1, cssNumber(play.speed, 1))) + 's');
                el.style.setProperty('--okip-motion-intensity', cssNumber(play.intensity, .5));
            });
            if (play.preset === 'variant-motion') {
                return;
            }
            if (gsapReady()) {
                animateGsap(items, play, 'from', 'to', true);
            } else {
                items.forEach(function (el) {
                    el.classList.add('okip-motion-playback--' + play.preset);
                });
            }
        }

        function enterThenPlayback(target) {
            enter(target);
            if (!stage(target, 'playback')) {
                return;
            }
            var entry = stage(target, 'entry');
            if (!enabled || stageDisabled(entry)) {
                playback(target);
                return;
            }
            // Esperar a que termine la entrada antes del playback: si no, el
            // killTweensOf de playback (GSAP) o su clase CSS aniquilan la entrada.
            var count = Math.max(0, elements(target).length - 1);
            var wait = ms(entry.delay_ms, 0) + ms(entry.duration_ms, 700) + (ms(entry.stagger_ms, 0) * count);
            window.setTimeout(function () { playback(target); }, wait);
        }

        function exit(target) {
            var items = elements(target);
            var exitStage = stage(target, 'exit');
            if (!items.length || !enabled || stageDisabled(exitStage)) {
                return;
            }
            items.forEach(clearPlaybackClasses);
            if (gsapReady()) {
                animateGsap(items, exitStage, 'from', 'to', false);
            } else {
                animateCss(items, exitStage, 'to');
            }
            root.classList.add('is-motion-exited-' + target);
        }

        function restore(target) {
            var items = elements(target);
            var entry = stage(target, 'entry');
            items.forEach(function (el) {
                el.style.transition = 'opacity 500ms ease, transform 500ms ease, filter 500ms ease';
                applyStyle(el, entry ? styleFor(entry, 'to') : { opacity: 1, transform: 'none', filter: 'none' });
            });
        }

        function prepareAll(targets) {
            targets.forEach(prepare);
        }

        function enterAll(targets) {
            targets.forEach(enter);
        }

        function playbackAll(targets) {
            targets.forEach(playback);
        }

        function exitAll(targets) {
            targets.forEach(exit);
        }

        function finishAll(targets) {
            targets.forEach(setFinal);
        }

        function watchExit(targets) {
            if (!enabled || motion.exit_trigger !== 'viewport_leave' || !('IntersectionObserver' in window)) {
                return;
            }
            if (exitObserver) {
                exitObserver.disconnect();
            }
            exitObserver = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (!entry.isIntersecting && !exited) {
                        exited = true;
                        clearCssTimers();
                        exitAll(targets);
                    } else if (entry.isIntersecting && exited) {
                        clearCssTimers();
                        if (motion.replay_mode === 'replay') {
                            entered = {};
                            prepareAll(targets);
                            enterAll(targets);
                            playbackAll(targets);
                        } else {
                            targets.forEach(restore);
                            playbackAll(targets);
                        }
                        exited = false;
                    }
                });
            }, { threshold: .05 });
            exitObserver.observe(root);
        }

        return {
            prepare: prepare,
            enter: enter,
            playback: playback,
            enterThenPlayback: enterThenPlayback,
            exit: exit,
            prepareAll: prepareAll,
            enterAll: enterAll,
            playbackAll: playbackAll,
            exitAll: exitAll,
            finishAll: finishAll,
            watchExit: watchExit
        };
    }

    window.OKIPAnimations = {
        create: create,
        parseConfig: parseConfig,
        reduceMotion: reduceMotion
    };
})();
