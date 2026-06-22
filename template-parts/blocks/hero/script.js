/*
 * Bloque Hero — comportamiento (scope por instancia via [data-okip-hero]).
 *
 * Escena de entrada con DOS videos:
 *   1. intro: se reproduce UNA vez. Tarjetas y texto permanecen ocultos.
 *   2. loop : al terminar el intro se hace crossfade (sin parpadeo) y queda en
 *      bucle como fondo vivo. Luego se revelan tarjetas (cards_delay) y después
 *      texto (text_delay), medidos desde el fin del intro.
 *
 * Sin intro: arranca el loop (o imagen/svg) y revela tras image_reveal_delay.
 * Fallo del intro (timeout/error): salta al loop; sin loop usa fallback_image;
 * sin fallback, fondo neutro. Nunca rompe.
 *
 * La escena NO se reinicia al volver al Hero (replay_on_enter=false por defecto):
 * el loop sigue vivo y tarjetas/texto permanecen visibles. El intro solo se
 * repite recargando la página. Usa GSAP para el reveal si está disponible; si no,
 * clases CSS. Respeta prefers-reduced-motion. Sin listeners duplicados.
 */
(function () {
    'use strict';

    var reduceMotion = !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);

    function gsapReady() {
        return !!(window.okipGsap && window.okipGsap.ready && window.gsap);
    }

    function initHero(hero) {
        if (hero.__okipHeroInit) { return; }
        hero.__okipHeroInit = true;

        var d = hero.dataset;
        var animOn    = d.anim === '1';
        var scroll3d  = d.scroll3d === '1';
        var imgDelay  = parseInt(d.imageDelay, 10) || 1500;
        var cardsDelay = parseInt(d.cardsDelay, 10) || 0;
        var textDelay  = parseInt(d.textDelay, 10) || 0;
        var introFail  = parseInt(d.introFail, 10) || 2500;
        var crossfade  = d.crossfade === '1';
        var crossfadeMs = parseInt(d.crossfadeMs, 10) || 700;
        var pauseBlur  = d.pauseBlur === '1';
        var hasFallback = d.hasFallback === '1';

        var intro = hero.querySelector('[data-okip-hero-intro]');
        var loop  = hero.querySelector('[data-okip-hero-loop]');
        var cardMedia = hero.querySelectorAll('.okip-hero__card-media');
        var lines = hero.querySelectorAll('.okip-hero__title-line, .okip-hero__desc');

        var timers = [];
        var tl = null;
        var done = false;          // ¿ya se reveló el contenido?
        var loopStarted = false;   // ¿ya se hizo el relevo intro → loop/fallback?

        function clearTimers() { timers.forEach(clearTimeout); timers = []; }
        function safePlay(v) {
            if (!v) { return; }
            var p = v.play();
            if (p && typeof p.catch === 'function') { p.catch(function () {}); }
        }

        /* ---------- Revelado (tarjetas → texto, tiempos desde el fin del intro) ---------- */
        function doReveal() {
            if (reduceMotion || !animOn) {
                hero.classList.add('is-cards-revealed', 'is-text-revealed');
                return;
            }
            if (gsapReady()) {
                if (tl) { tl.kill(); }
                tl = window.gsap.timeline({ defaults: { ease: 'power3.out' } });
                if (cardMedia.length) {
                    tl.to(cardMedia, { opacity: 1, y: 0, scale: 1, duration: 0.6, stagger: 0.12 }, cardsDelay / 1000);
                }
                if (lines.length) {
                    tl.to(lines, { opacity: 1, y: 0, duration: 0.7, stagger: 0.12 }, textDelay / 1000);
                }
            } else {
                timers.push(setTimeout(function () { hero.classList.add('is-cards-revealed'); }, cardsDelay));
                timers.push(setTimeout(function () { hero.classList.add('is-text-revealed'); }, textDelay));
            }
        }
        function finishReveal() {
            if (done) { return; }
            done = true;
            doReveal();
        }

        /* ---------- Relevo intro → loop (crossfade) / fallback / neutro ---------- */
        function startLoop() {
            if (loopStarted) { return; }
            loopStarted = true;

            if (loop) {
                safePlay(loop);
                // Crossfade: el loop ya está reproduciéndose detrás; al ocultar el
                // intro (opacidad) se revela el loop sin parpadeo negro.
                requestAnimationFrame(function () {
                    hero.classList.add('is-loop-visible', 'is-intro-hidden');
                });
                if (intro) {
                    // Pausar el intro tras el crossfade (libera recursos).
                    timers.push(setTimeout(function () {
                        try { intro.pause(); } catch (e) {}
                    }, crossfadeMs + 80));
                }
            } else if (hasFallback) {
                hero.classList.add('is-fallback-shown', 'is-intro-hidden');
                if (intro) {
                    timers.push(setTimeout(function () { try { intro.pause(); } catch (e) {} }, crossfadeMs + 80));
                }
            } else if (intro) {
                // El intro es el único fondo: se queda en su último fotograma.
                // No lo ocultamos ni degradamos.
            } else if (pauseBlur) {
                hero.classList.add('is-bg-failed');
            }
        }

        /* ---------- Camino de fallo del intro ---------- */
        function introFailPath() {
            if (loopStarted && done) { return; }
            startLoop();
            finishReveal();
        }

        /* ---------- Intro: reproducir una vez, precargar loop ---------- */
        function beginIntro() {
            if (loop) { try { loop.load(); } catch (e) {} } // precarga del loop
            safePlay(intro);

            intro.addEventListener('ended', function () {
                startLoop();
                finishReveal();
            }, { once: true });
            intro.addEventListener('error', introFailPath, { once: true });

            // Salvaguarda: si el intro no progresa a tiempo, saltar al loop/fallback.
            timers.push(setTimeout(function () {
                if (done) { return; }
                if (intro.error || intro.readyState < 2 || (intro.paused && intro.currentTime === 0) || intro.currentTime === 0) {
                    introFailPath();
                }
            }, introFail));
        }

        /* ---------- Arranque ---------- */
        if (!animOn || reduceMotion) {
            hero.classList.add('is-cards-revealed', 'is-text-revealed');
            done = true;
            loopStarted = true;
            if (hasFallback) {
                hero.classList.add('is-fallback-shown', 'is-intro-hidden');
            } else if (loop) {
                hero.classList.add('is-loop-visible', 'is-intro-hidden');
                if (!reduceMotion) { safePlay(loop); } // reduce-motion: fotograma estático
            }
            // intro como único fondo → se queda visible (primer fotograma).
        } else if (intro) {
            beginIntro();
        } else if (loop) {
            loopStarted = true;
            safePlay(loop);
            requestAnimationFrame(function () { hero.classList.add('is-loop-visible'); });
            timers.push(setTimeout(finishReveal, imgDelay));
        } else {
            // image | svg | fallback único | missing
            if (hasFallback) { hero.classList.add('is-fallback-shown'); }
            timers.push(setTimeout(finishReveal, imgDelay));
        }

        /* ---------- Efecto 3D al hacer scroll (desactivado en Home) ---------- */
        if (scroll3d && !reduceMotion && gsapReady() && window.okipGsap.hasScrollTrigger) {
            var content = hero.querySelector('[data-okip-hero-content]');
            var bg = hero.querySelector('[data-okip-hero-bg]');
            window.gsap.to([bg, content], {
                scrollTrigger: { trigger: hero, start: 'top top', end: 'bottom top', scrub: true },
                scale: 0.88, rotateX: 6, y: -60, opacity: 0.35,
                transformOrigin: 'center 30%', ease: 'none'
            });
        }

        setupCards(hero);
    }

    /* ---------- Tarjetas: SOLO se activan por interacción (nunca autoplay) ----------
     * play_mode: hover (puntero fino) | tap (táctil) | manual (click siempre).
     * Por defecto NO se reinicia/pausa al salir del hover (reset_on_leave=0).
     * Los placeholders (sin <video>) se ignoran sin lanzar errores. */
    function setupCards(hero) {
        var cards = hero.querySelectorAll('[data-okip-hero-card]');
        var finePointer = window.matchMedia && window.matchMedia('(hover: hover) and (pointer: fine)').matches;

        cards.forEach(function (card) {
            var video = card.querySelector('video');
            if (!video) { return; } // placeholder u otro tipo: nada que reproducir

            var playMode = card.getAttribute('data-play-mode') || 'hover';
            var resetOnLeave = card.getAttribute('data-reset-on-leave') === '1';
            var allowHover = playMode === 'hover' && card.getAttribute('data-hover') !== '0';
            var allowTap = (playMode === 'tap' || playMode === 'manual') ? true
                : card.getAttribute('data-tap') !== '0';

            var play = function () {
                var p = video.play();
                if (p && typeof p.catch === 'function') { p.catch(function () {}); }
            };
            var leave = function () {
                if (!resetOnLeave) { return; } // continuar reproduciéndose
                try { video.pause(); video.currentTime = 0; } catch (e) {}
            };

            if (finePointer && allowHover) {
                card.addEventListener('mouseenter', play);
                card.addEventListener('mouseleave', leave);
            }
            if ((!finePointer && allowTap) || (finePointer && (playMode === 'tap' || playMode === 'manual'))) {
                card.addEventListener('click', function () {
                    if (video.paused) { play(); }
                    else if (resetOnLeave || playMode === 'manual') { video.pause(); }
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
