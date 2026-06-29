/*
 * Bloque Hero — medios y coordinacion de animaciones.
 */
(function () {
    'use strict';

    var reduceMotion = !!(window.OKIPAnimations && window.OKIPAnimations.reduceMotion);

    /* Milisegundos hasta que una fase de entrada termina (delay + duración + stagger
       del último elemento). 0 si la fase está deshabilitada. */
    function entryEnd(stage, count) {
        if (!stage || !stage.enabled || stage.preset === 'none') { return 0; }
        var delay = window.OKIP.readInt(stage.delay_ms, 0);
        var dur = parseInt(stage.duration_ms, 10) || 700; // 0 → usar default (no migrar)
        var stagger = window.OKIP.readInt(stage.stagger_ms, 0);
        return delay + dur + stagger * Math.max(0, count - 1);
    }

    function readMs(value, fallback) {
        var parsed = parseInt(value, 10);
        return Number.isFinite(parsed) ? Math.max(0, parsed) : fallback;
    }

    function initHero(hero) {
        if (hero.__okipHeroInit) { return; }
        hero.__okipHeroInit = true;

        var d = hero.dataset;
        var motionOn = d.motionEnabled === '1';
        var introFail = readMs(d.introFail, 2500);
        var crossfade = d.crossfade === '1';
        var crossfadeMs = crossfade ? readMs(d.crossfadeMs, 700) : 0;
        var contentEntryDelay = readMs(d.contentEntryDelay, 900);
        var hasFallback = d.hasFallback === '1';

        var intro = hero.querySelector('[data-okip-hero-intro]');
        var loop = hero.querySelector('[data-okip-hero-loop]');
        var animator = window.OKIPAnimations && window.OKIPAnimations.create
            ? window.OKIPAnimations.create(hero)
            : null;
        var motionConfig = window.OKIPAnimations && window.OKIPAnimations.parseConfig
            ? window.OKIPAnimations.parseConfig(hero)
            : null;
        var motionCfg = motionConfig && motionConfig.motion ? motionConfig.motion : null;

        var timers = [];
        var done = false;
        var loopStarted = false;
        var contentEntryScheduled = false;
        var motionTargets = ['background', 'cards', 'text'];

        function safePlay(v) {
            if (!v) { return; }
            var p = v.play();
            if (p && typeof p.catch === 'function') { p.catch(function () {}); }
        }

        // Reanuda las animaciones pesadas del fondo (pausadas durante la entrada).
        function stopEntering() {
            hero.classList.remove('is-hero-entering');
        }

        function prepareMotion() {
            if (!animator) { return; }
            animator.prepareAll(motionTargets);
            animator.enter('background');
        }

        function finishMotion() {
            if (done) { return; }
            done = true;
            if (!animator) {
                hero.classList.add('is-motion-complete');
                stopEntering();
                return;
            }
            if (!motionOn || reduceMotion) {
                animator.finishAll(motionTargets);
                hero.classList.add('is-motion-complete');
                stopEntering();
                return;
            }
            animator.enter('text');
            animator.playback('background');
            animator.enterThenPlayback('cards');
            animator.watchExit(motionTargets);
            hero.classList.add('is-motion-complete');
            // Reanudar el fondo pesado SOLO cuando texto y tarjetas terminen de entrar.
            var textCount = hero.querySelectorAll('[data-okip-motion-target="text"]').length;
            var cardCount = hero.querySelectorAll('[data-okip-motion-target="cards"]').length;
            var wait = Math.max(
                entryEnd(motionCfg && motionCfg.text ? motionCfg.text.entry : null, textCount),
                entryEnd(motionCfg && motionCfg.cards ? motionCfg.cards.entry : null, cardCount)
            );
            timers.push(setTimeout(stopEntering, wait + 60));
        }

        function scheduleContentEntry() {
            if (done || contentEntryScheduled) { return; }
            contentEntryScheduled = true;
            timers.push(setTimeout(finishMotion, contentEntryDelay));
        }

        function startLoop() {
            if (loopStarted) { return; }
            loopStarted = true;

            if (loop) {
                safePlay(loop);
                requestAnimationFrame(function () {
                    hero.classList.add('is-loop-visible', 'is-intro-hidden');
                });
                if (intro) {
                    timers.push(setTimeout(function () {
                        try { intro.pause(); } catch (e) {}
                    }, crossfadeMs + 80));
                }
            } else if (hasFallback) {
                hero.classList.add('is-fallback-shown', 'is-intro-hidden');
                if (intro) {
                    timers.push(setTimeout(function () {
                        try { intro.pause(); } catch (e) {}
                    }, crossfadeMs + 80));
                }
            } else if (!intro) {
                hero.classList.add('is-bg-failed');
            }
        }

        function introFailPath() {
            if (loopStarted && done) { return; }
            startLoop();
            scheduleContentEntry();
        }

        function beginIntro() {
            if (loop) {
                try { loop.load(); } catch (e) {}
            }
            safePlay(intro);

            intro.addEventListener('ended', function () {
                startLoop();
            }, { once: true });
            intro.addEventListener('error', introFailPath, { once: true });

            timers.push(setTimeout(function () {
                if (done) { return; }
                if (intro.error || intro.readyState < 2 || (intro.paused && intro.currentTime === 0) || intro.currentTime === 0) {
                    introFailPath();
                }
            }, introFail));
        }

        prepareMotion();

        // Salvaguarda: nunca dejar el fondo congelado si la secuencia no completa.
        timers.push(setTimeout(stopEntering, 5000));

        if (reduceMotion) {
            if (hasFallback) {
                hero.classList.add('is-fallback-shown', 'is-intro-hidden');
            } else if (loop) {
                hero.classList.add('is-loop-visible', 'is-intro-hidden');
            }
            finishMotion();
        } else if (intro) {
            beginIntro();
            scheduleContentEntry();
        } else if (loop) {
            loopStarted = true;
            safePlay(loop);
            requestAnimationFrame(function () {
                hero.classList.add('is-loop-visible');
                scheduleContentEntry();
            });
        } else {
            if (hasFallback) {
                hero.classList.add('is-fallback-shown');
            }
            requestAnimationFrame(scheduleContentEntry);
        }

        setupCards(hero);
        setupCoverPause(hero);
    }

    /*
     * Pausa las animaciones del Hero cuando queda CUBIERTO por el bloque siguiente.
     * El Hero es position:sticky → puede seguir intersectando el viewport aunque ya
     * esté tapado; por eso NO usamos IntersectionObserver de "offscreen" sino una
     * comparación por scroll/rAF: cubierto = el bloque siguiente alcanzó el top del
     * viewport (o, sin bloque siguiente, el Hero ya pasó de largo).
     */
    function setupCoverPause(hero) {
        var next = hero.nextElementSibling;
        var paused = false;
        var ticking = false;

        function evaluate() {
            ticking = false;
            var covered = next
                ? next.getBoundingClientRect().top <= 0
                : hero.getBoundingClientRect().bottom <= 0;
            if (covered !== paused) {
                paused = covered;
                hero.classList.toggle('is-hero-paused', covered);
            }
        }

        function onScroll() {
            if (!ticking) {
                ticking = true;
                requestAnimationFrame(evaluate);
            }
        }

        window.addEventListener('scroll', onScroll, { passive: true });
        window.addEventListener('resize', onScroll, { passive: true });
        evaluate();
    }

    function setupCards(hero) {
        var cards = hero.querySelectorAll('[data-okip-hero-card]');
        var finePointer = !!(window.matchMedia && window.matchMedia('(hover: hover) and (pointer: fine)').matches);

        cards.forEach(function (card) {
            var video = card.querySelector('video');
            var gif = card.querySelector('.okip-hero__card-gif');
            var playMode = card.getAttribute('data-play-mode') || 'hover';
            var resetOnLeave = card.getAttribute('data-reset-on-leave') === '1';

            if (gif) {
                setupGifCard(card, gif, finePointer);
            }

            if (!video) { return; }
            if (playMode === 'disabled') { return; }

            var play = function () {
                var p = video.play();
                if (p && typeof p.catch === 'function') { p.catch(function () {}); }
            };
            var pause = function () {
                try {
                    video.pause();
                    if (resetOnLeave) { video.currentTime = 0; }
                } catch (e) {}
            };

            if (playMode === 'hover') {
                if (finePointer) {
                    card.addEventListener('mouseenter', play);
                    card.addEventListener('mouseleave', pause);
                } else {
                    card.addEventListener('click', play);
                }
            } else {
                card.addEventListener('click', function () {
                    if (video.paused) { play(); } else { pause(); }
                });
            }
        });
    }

    function setupGifCard(card, gif, finePointer) {
        var src = card.getAttribute('data-gif-src') || gif.getAttribute('data-gif-src') || '';
        var playMode = card.getAttribute('data-play-mode') || 'hover';
        var playDuration = readMs(card.getAttribute('data-play-duration-ms'), 4000);
        var token = 0;
        var playing = false;
        if (!src) { return; }
        if (playMode !== 'hover' || !finePointer || reduceMotion) { return; }

        function finish(current) {
            if (current !== token) { return; }
            playing = false;
            token += 1;
            card.classList.remove('is-gif-playing');
            window.setTimeout(function () {
                if (!playing && !card.classList.contains('is-gif-playing')) {
                    gif.removeAttribute('src');
                }
            }, 220);
        }

        function play() {
            if (playing) { return; }
            playing = true;
            token += 1;
            var current = token;
            gif.removeAttribute('src');
            window.requestAnimationFrame(function () {
                if (current === token) {
                    gif.setAttribute('src', src);
                    card.classList.add('is-gif-playing');
                }
            });
            window.setTimeout(function () {
                finish(current);
            }, playDuration);
        }

        card.addEventListener('mouseenter', play);
    }

    function init() {
        document.querySelectorAll('[data-okip-hero]').forEach(initHero);
    }

    if (window.OKIP && window.OKIP.ready) {
        window.OKIP.ready(init);
    } else if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
