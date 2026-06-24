/*
 * Bloque Hero — medios y coordinacion de animaciones.
 */
(function () {
    'use strict';

    var reduceMotion = !!(window.OKIPAnimations && window.OKIPAnimations.reduceMotion);

    function initHero(hero) {
        if (hero.__okipHeroInit) { return; }
        hero.__okipHeroInit = true;

        var d = hero.dataset;
        var motionOn = d.motionEnabled === '1';
        var introFail = parseInt(d.introFail, 10) || 2500;
        var crossfade = d.crossfade === '1';
        var crossfadeMs = crossfade ? (parseInt(d.crossfadeMs, 10) || 700) : 0;
        var hasFallback = d.hasFallback === '1';

        var intro = hero.querySelector('[data-okip-hero-intro]');
        var loop = hero.querySelector('[data-okip-hero-loop]');
        var animator = window.OKIPAnimations && window.OKIPAnimations.create
            ? window.OKIPAnimations.create(hero)
            : null;

        var timers = [];
        var done = false;
        var loopStarted = false;
        var motionTargets = ['background', 'cards', 'text'];

        function safePlay(v) {
            if (!v) { return; }
            var p = v.play();
            if (p && typeof p.catch === 'function') { p.catch(function () {}); }
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
                return;
            }
            if (!motionOn || reduceMotion) {
                animator.finishAll(motionTargets);
                hero.classList.add('is-motion-complete');
                return;
            }
            animator.enter('text');
            animator.playback('background');
            animator.enterThenPlayback('cards');
            animator.watchExit(motionTargets);
            hero.classList.add('is-motion-complete');
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
            finishMotion();
        }

        function beginIntro() {
            if (loop) {
                try { loop.load(); } catch (e) {}
            }
            safePlay(intro);

            intro.addEventListener('ended', function () {
                startLoop();
                finishMotion();
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

        if (reduceMotion) {
            if (hasFallback) {
                hero.classList.add('is-fallback-shown', 'is-intro-hidden');
            } else if (loop) {
                hero.classList.add('is-loop-visible', 'is-intro-hidden');
            }
            finishMotion();
        } else if (intro) {
            beginIntro();
        } else if (loop) {
            loopStarted = true;
            safePlay(loop);
            requestAnimationFrame(function () {
                hero.classList.add('is-loop-visible');
                finishMotion();
            });
        } else {
            if (hasFallback) {
                hero.classList.add('is-fallback-shown');
            }
            requestAnimationFrame(finishMotion);
        }

        setupCards(hero);
    }

    function setupCards(hero) {
        var cards = hero.querySelectorAll('[data-okip-hero-card]');
        var finePointer = !!(window.matchMedia && window.matchMedia('(hover: hover) and (pointer: fine)').matches);

        cards.forEach(function (card) {
            var video = card.querySelector('video');
            if (!video) { return; }

            var playMode = card.getAttribute('data-play-mode') || 'hover';
            var resetOnLeave = card.getAttribute('data-reset-on-leave') === '1';

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
