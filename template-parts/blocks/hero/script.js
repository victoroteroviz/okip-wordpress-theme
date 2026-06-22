/*
 * Bloque Hero — comportamiento (scope por instancia via [data-okip-hero]).
 *
 * Secuencia de entrada controlada por JS: fondo → tarjetas → texto.
 *   - video: revela según reveal-strategy (video_end | delay | canplay).
 *   - image/svg/gradient: revela tras image-delay (1.5s por defecto).
 *   - si el video falla o no arranca antes de video-fail-timeout (2s):
 *     fallback limpio (gradiente + blur), pausa el video y revela el contenido.
 *
 * Reentrada (IntersectionObserver): al volver al Hero se reinicia la escena
 * (video a 0, tarjetas/texto se ocultan y vuelven a animarse). Fallback por
 * scrollY si no hay IO. Usa GSAP si está disponible; si no, clases CSS.
 * Respeta prefers-reduced-motion. Sin listeners duplicados.
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
        var animOn   = d.anim === '1';
        var scroll3d = d.scroll3d === '1';
        var bgType   = d.bgType || 'gradient';
        var strategy = d.revealStrategy || 'video_end';
        var imgDelay = parseInt(d.imageDelay, 10) || 1500;
        var failTo   = parseInt(d.videoFailTimeout, 10) || 2000;
        var cardsDelay = parseInt(d.cardsDelay, 10) || 0;
        var textDelay  = parseInt(d.textDelay, 10) || 0;
        var replay   = d.replay === '1';
        var pauseBlur = d.pauseBlur === '1';

        var video = hero.querySelector('[data-okip-hero-bg] video');
        var cardMedia = hero.querySelectorAll('.okip-hero__card-media');
        var lines = hero.querySelectorAll('.okip-hero__title-line, .okip-hero__desc');

        var timers = [];
        var tl = null;
        var done = false;       // ¿ya se reveló en esta escena?
        var revealedOnce = false;

        function clearTimers() {
            timers.forEach(clearTimeout);
            timers = [];
        }

        /* ---------- Revelado (tarjetas → texto) ---------- */
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
                    tl.to(lines, { opacity: 1, y: 0, duration: 0.7, stagger: 0.12 }, '+=' + (textDelay / 1000));
                }
            } else {
                timers.push(setTimeout(function () {
                    hero.classList.add('is-cards-revealed');
                }, cardsDelay));
                timers.push(setTimeout(function () {
                    hero.classList.add('is-text-revealed');
                }, cardsDelay + 600 + textDelay));
            }
        }

        function finishReveal() {
            if (done) { return; }
            done = true;
            revealedOnce = true;
            doReveal();
        }

        function failReveal() {
            if (done) { return; }
            done = true;
            revealedOnce = true;
            if (pauseBlur) {
                hero.classList.add('is-bg-failed');
                if (video) { try { video.pause(); } catch (e) {} }
            }
            doReveal();
        }

        /* ---------- Reinicio (reentrada al Hero) ---------- */
        function resetHidden() {
            done = false;
            clearTimers();
            hero.classList.remove('is-cards-revealed', 'is-text-revealed', 'is-bg-failed');
            if (gsapReady()) {
                if (tl) { tl.kill(); tl = null; }
                if (cardMedia.length) { window.gsap.set(cardMedia, { opacity: 0, y: 24, scale: 0.96 }); }
                if (lines.length) { window.gsap.set(lines, { opacity: 0, y: 28 }); }
            }
        }

        /* ---------- Fondo + disparo de la secuencia ---------- */
        function startBackground() {
            done = false;
            if (bgType === 'video' && video) {
                try { video.currentTime = 0; } catch (e) {}
                if (video.paused) {
                    var p = video.play();
                    if (p && typeof p.catch === 'function') { p.catch(function () {}); }
                }
                if (strategy === 'delay') {
                    timers.push(setTimeout(finishReveal, imgDelay));
                }
                // Salvaguarda: si el video no progresa a tiempo, fallback.
                timers.push(setTimeout(function () {
                    if (done) { return; }
                    if (video.readyState < 2 || video.paused || video.error) {
                        failReveal();
                    }
                }, failTo));
            } else {
                // image | svg | gradient
                timers.push(setTimeout(finishReveal, imgDelay));
            }
        }

        function playScene() {
            resetHidden();
            if (video) {
                hero.classList.remove('is-bg-failed');
                try { video.pause(); video.currentTime = 0; } catch (e) {}
            }
            // Doble rAF: garantiza que el estado oculto se pinte antes de animar.
            requestAnimationFrame(function () {
                requestAnimationFrame(startBackground);
            });
        }

        /* ---------- Listeners del video (una sola vez) ---------- */
        if (video) {
            video.loop = (strategy === 'canplay' || strategy === 'delay');
            video.addEventListener('ended', function () {
                if (strategy === 'video_end') { finishReveal(); }
            });
            video.addEventListener('canplay', function () {
                if (strategy === 'canplay') { finishReveal(); }
            });
            video.addEventListener('error', failReveal);
        }

        /* ---------- Arranque inicial ---------- */
        if (!animOn || reduceMotion) {
            hero.classList.add('is-cards-revealed', 'is-text-revealed');
            revealedOnce = true;
            if (video) {
                var pi = video.play();
                if (pi && typeof pi.catch === 'function') { pi.catch(function () {}); }
            }
        } else {
            startBackground();
        }

        /* ---------- Efecto 3D al hacer scroll ---------- */
        if (scroll3d && !reduceMotion && gsapReady() && window.okipGsap.hasScrollTrigger) {
            var content = hero.querySelector('[data-okip-hero-content]');
            var bg = hero.querySelector('[data-okip-hero-bg]');
            window.gsap.to([bg, content], {
                scrollTrigger: { trigger: hero, start: 'top top', end: 'bottom top', scrub: true },
                scale: 0.88, rotateX: 6, y: -60, opacity: 0.35,
                transformOrigin: 'center 30%', ease: 'none'
            });
        }

        /* ---------- Reentrada: reinicia la escena ---------- */
        if (replay && animOn && !reduceMotion) {
            if ('IntersectionObserver' in window) {
                var hasLeft = false;
                var io = new IntersectionObserver(function (entries) {
                    var e = entries[0];
                    if (!e.isIntersecting || e.intersectionRatio < 0.05) {
                        hasLeft = true;
                    } else if (e.intersectionRatio > 0.6 && hasLeft) {
                        hasLeft = false;
                        if (revealedOnce) { playScene(); }
                    }
                }, { threshold: [0, 0.05, 0.6, 1] });
                io.observe(hero);
            } else {
                // Fallback por scrollY: detecta salida/regreso usando la altura.
                var wasOut = false;
                window.addEventListener('scroll', function () {
                    var rect = hero.getBoundingClientRect();
                    var out = rect.bottom < window.innerHeight * 0.1;
                    var inView = rect.top > -rect.height * 0.4 && rect.bottom > window.innerHeight * 0.6;
                    if (out) { wasOut = true; }
                    else if (inView && wasOut) { wasOut = false; if (revealedOnce) { playScene(); } }
                }, { passive: true });
            }
        }

        setupCards(hero);
    }

    /* ---------- Tarjetas: play en hover (puntero fino) o tap (táctil) ---------- */
    function setupCards(hero) {
        var cards = hero.querySelectorAll('[data-okip-hero-card]');
        var finePointer = window.matchMedia && window.matchMedia('(hover: hover) and (pointer: fine)').matches;

        cards.forEach(function (card) {
            var video = card.querySelector('video');
            if (!video) { return; }

            var playOnHover = card.getAttribute('data-hover') === '1';
            var playOnTap = card.getAttribute('data-tap') === '1';

            var play = function () {
                var p = video.play();
                if (p && typeof p.catch === 'function') { p.catch(function () {}); }
            };
            var pause = function () { video.pause(); };

            if (finePointer && playOnHover) {
                card.addEventListener('mouseenter', play);
                card.addEventListener('mouseleave', pause);
            }
            if (!finePointer && playOnTap) {
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
