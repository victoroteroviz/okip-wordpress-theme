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
        // Pausa/reanuda el vídeo de fondo (intro/loop) según el Hero esté enfocado:
        // cubierto por el bloque siguiente → pause (libera decodificación); de vuelta a
        // la vista → resume. El autoplay de GIFs de las tarjetas ya está gateado por la
        // misma señal `is-hero-paused` (ver setupCardsAutoplay), así que también se pausa.
        setupCoverPause(hero, function (covered) {
            var bg = (loopStarted && loop) ? loop : intro;
            if (!bg) { return; }
            if (covered) {
                try { bg.pause(); } catch (e) {}
            } else if (!reduceMotion) {
                safePlay(bg);
            }
        });
        // Snap del traspaso Hero → bloque siguiente (helper global compartido; ver app.js).
        // Solo desktop + forward; el inverso queda nativo. Apagable con data-snap-cover="0".
        if (window.OKIP && OKIP.snapCover) {
            OKIP.snapCover(hero, {
                enabled: hero.dataset.snapCover === '1',
                duration: window.OKIP.readInt(hero.dataset.snapDuration, 700)
            });
        }
    }

    /*
     * Pausa las animaciones del Hero cuando queda CUBIERTO por el bloque siguiente.
     * El Hero es position:sticky → puede seguir intersectando el viewport aunque ya
     * esté tapado; por eso NO usamos IntersectionObserver de "offscreen" sino una
     * comparación por scroll/rAF: cubierto = el bloque siguiente alcanzó el top del
     * viewport (o, sin bloque siguiente, el Hero ya pasó de largo).
     */
    function setupCoverPause(hero, onChange) {
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
                if (onChange) { onChange(covered); }
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
        // Tarjetas activables por el autoplay aleatorio (mismo mecanismo que el hover).
        var players = [];

        cards.forEach(function (card) {
            var video = card.querySelector('video');
            var gif = card.querySelector('.okip-hero__card-gif');
            var playMode = card.getAttribute('data-play-mode') || 'hover';
            var resetOnLeave = card.getAttribute('data-reset-on-leave') === '1';

            if (gif) {
                var gifPlay = setupGifCard(card, gif, finePointer);
                // El GIF se autofinaliza tras su duración → no necesita stop().
                if (gifPlay) { players.push({ play: gifPlay }); }
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

            // El video se reproduce en bucle; el autoplay lo detiene tras un rato.
            var holdMs = readMs(card.getAttribute('data-play-duration-ms'), 0) || 3500;
            players.push({ play: play, stop: pause, holdMs: holdMs });
        });

        setupCardsAutoplay(hero, players);
    }

    /*
     * Activación AUTOMÁTICA de tarjetas: dispara una tarjeta al azar cada cierto
     * intervalo aleatorio, reutilizando las funciones play()/stop() del hover. Se
     * apaga desde el admin (data-cards-autoplay="0"), con reduce-motion, o mientras
     * el ratón está sobre las tarjetas / la pestaña está oculta / el Hero cubierto.
     */
    function setupCardsAutoplay(hero, players) {
        if (reduceMotion || !players.length) { return; }
        var d = hero.dataset;
        if (d.cardsAutoplay !== '1') { return; }

        var minDelay = readMs(d.cardsAutoplayMin, 2500);
        var maxDelay = readMs(d.cardsAutoplayMax, 6500);
        if (maxDelay < minDelay) { maxDelay = minDelay; }
        var startDelay = readMs(d.cardsAutoplayStart, 1200);
        var pauseOnHover = d.cardsAutoplayHover === '1';

        var timer = null;
        var hovering = false;
        var lastIndex = -1;

        function pickIndex() {
            if (players.length === 1) { return 0; }
            var i;
            do { i = Math.floor(Math.random() * players.length); } while (i === lastIndex);
            return i;
        }

        function tick() {
            var blocked = hovering || document.hidden || hero.classList.contains('is-hero-paused');
            if (!blocked) {
                lastIndex = pickIndex();
                var player = players[lastIndex];
                try { player.play(); } catch (e) {}
                if (player.stop) {
                    window.setTimeout(function () {
                        try { player.stop(); } catch (e) {}
                    }, player.holdMs || 3500);
                }
            }
            schedule();
        }

        function schedule() {
            window.clearTimeout(timer);
            timer = window.setTimeout(tick, minDelay + Math.random() * (maxDelay - minDelay));
        }

        if (pauseOnHover) {
            var wrap = hero.querySelector('[data-okip-hero-cards]');
            if (wrap) {
                wrap.addEventListener('mouseenter', function () { hovering = true; });
                wrap.addEventListener('mouseleave', function () { hovering = false; });
            }
        }

        document.addEventListener('visibilitychange', function () {
            if (document.hidden) { window.clearTimeout(timer); }
            else { schedule(); }
        });

        window.setTimeout(schedule, startDelay);
    }

    /*
     * Prepara una tarjeta GIF y devuelve su función `play()` (o null si no aplica),
     * para que el hover Y el autoplay aleatorio compartan el MISMO mecanismo de
     * reproducción. El hover solo se enlaza en puntero fino con modo hover.
     */
    function setupGifCard(card, gif, finePointer) {
        var src = card.getAttribute('data-gif-src') || gif.getAttribute('data-gif-src') || '';
        var playMode = card.getAttribute('data-play-mode') || 'hover';
        var playDuration = readMs(card.getAttribute('data-play-duration-ms'), 4000);
        var token = 0;
        var playing = false;
        if (!src || playMode === 'disabled') { return null; }

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

        if (playMode === 'hover' && finePointer && !reduceMotion) {
            card.addEventListener('mouseenter', play);
        }
        return play;
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
