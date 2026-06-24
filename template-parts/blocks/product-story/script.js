/*
 * Bloque Product Story (Bloque 4) — scroll-driven por fila.
 * Scope por instancia via [data-okip-ps].
 *
 * Desktop con GSAP + ScrollTrigger (>disable_below px):
 *   UN ScrollTrigger con scrub POR FILA (el root no se transforma — solo nodos
 *   internos). Cada timeline:
 *     1) recuadro izquierdo entra (left_enter: mask-slide|fade-up|scale-soft|none),
 *     2) fondo de la tarjeta derecha hace wipe (copy_bg_enter: wipe-left|fade|none),
 *     3) heading + descripción se revelan (text_reveal: scroll-typewriter|fade-lines|none).
 *   Typewriter: split por palabras→caracteres; texto completo conservado para
 *   lectores de pantalla (copia .okip-ps__sr + aria-hidden en el split).
 *
 * Sin GSAP (pero desktop y use_vanilla_fallback): IO añade `is-revealed` por fila.
 * Móvil/tablet ≤disable_below, reduce-motion o anim off: `is-static`, todo legible.
 *
 * El único pin posible es el handoff final hacia Mission, sin pinSpacing, para
 * que el siguiente bloque se superponga cuando las filas ya terminaron.
 * No autoplay. No intervalos. Respeta prefers-reduced-motion. Nunca deja texto
 * invisible si GSAP falla (los estados ocultos solo viven con JS + animado).
 */
(function () {
    'use strict';

    var reduceMotion = (window.OKIP && typeof window.OKIP.reduceMotion === 'boolean')
        ? window.OKIP.reduceMotion
        : !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);

    function stReady() {
        return !!(window.okipGsap && window.okipGsap.ready && window.gsap &&
                  window.okipGsap.hasScrollTrigger && window.ScrollTrigger);
    }

    /* Split de un nodo .okip-ps__type en palabras→caracteres.
       Mantiene accesibilidad: el split queda aria-hidden y se añade una copia SR. */
    function splitType(typeEl) {
        if (typeEl.__okipSplit) { return typeEl.__okipChars; }
        var full = typeEl.textContent;
        var tokens = full.split(/(\s+)/); // conserva los tokens de espacio
        var frag = document.createDocumentFragment();
        var chars = [];

        tokens.forEach(function (token) {
            if (token === '') { return; }
            if (/^\s+$/.test(token)) {
                var sp = document.createElement('span');
                sp.className = 'okip-ps__char okip-ps__char--space';
                sp.textContent = token;
                frag.appendChild(sp);
                chars.push(sp);
                return;
            }
            var word = document.createElement('span');
            word.className = 'okip-ps__word';
            for (var i = 0; i < token.length; i++) {
                var c = document.createElement('span');
                c.className = 'okip-ps__char';
                c.textContent = token.charAt(i);
                word.appendChild(c);
                chars.push(c);
            }
            frag.appendChild(word);
        });

        typeEl.textContent = '';
        typeEl.appendChild(frag);
        typeEl.setAttribute('aria-hidden', 'true');

        // Copia accesible con el texto completo (hermano dentro del heading/p).
        var sr = document.createElement('span');
        sr.className = 'okip-ps__sr';
        sr.textContent = full;
        if (typeEl.parentNode) { typeEl.parentNode.appendChild(sr); }

        typeEl.__okipSplit = true;
        typeEl.__okipSrCopy = sr;
        typeEl.__okipChars = chars;
        return chars;
    }

    /* Hover / focus play+reset para videos dentro de .okip-ps__visual.
       Se llama en todos los modos (incluyendo is-static): en móvil no hay hover,
       y en desktop sin GSAP el video sigue siendo interactivo.
       Si noMotion es true, los videos quedan estáticos. */
    function setupVideoInteraction(section, noMotion, small) {
        if (noMotion) { return; }
        var visuals = Array.prototype.slice.call(section.querySelectorAll('.okip-ps__visual'));
        visuals.forEach(function (visual) {
            var vid = visual.querySelector('video');
            if (!vid) { return; }
            if (!small) { visual.setAttribute('tabindex', '0'); }
            function doPlay() {
                var pr = vid.play();
                if (pr && typeof pr.catch === 'function') { pr.catch(function () {}); }
            }
            function doReset() {
                try { vid.pause(); vid.currentTime = 0; } catch (e) {}
            }
            visual.addEventListener('mouseenter', doPlay,  { passive: true });
            visual.addEventListener('mouseleave', doReset, { passive: true });
            if (!small) {
                visual.addEventListener('focus', doPlay);
                visual.addEventListener('blur',  doReset);
            }
        });
    }

    function initPs(section) {
        if (section.__okipPsInit) { return; }
        section.__okipPsInit = true;

        var d            = section.dataset;
        var animOn       = d.anim       === '1';
        var useGsap      = d.useGsap    === '1';
        var useVanilla   = d.useVanilla === '1';
        var disableBelow = parseInt(d.disableBelow, 10) || 1024;
        var scrub        = parseFloat(d.scrub);
        if (isNaN(scrub)) { scrub = 1; }
        var leftEnter    = d.leftEnter   || 'mask-slide';
        var copyBgEnter  = d.copyBgEnter || 'wipe-left';
        var textReveal   = d.textReveal  || 'scroll-typewriter';
        var handoffPin   = d.handoffPin === '1';
        var handoffDurationVh = parseInt(d.handoffDurationVh, 10);
        if (isNaN(handoffDurationVh)) { handoffDurationVh = 132; }
        var handoffDisableBelow = parseInt(d.handoffDisableBelow, 10);
        if (isNaN(handoffDisableBelow)) { handoffDisableBelow = disableBelow; }

        var psId    = section.id || d.blockInstance || 'ps';
        var rows    = Array.prototype.slice.call(section.querySelectorAll('[data-okip-ps-row]'));
        var isSmall = !!(window.matchMedia && window.matchMedia('(max-width: ' + disableBelow + 'px)').matches);
        var isHandoffSmall = !!(window.matchMedia && window.matchMedia('(max-width: ' + handoffDisableBelow + 'px)').matches);

        setupVideoInteraction(section, reduceMotion, isSmall);

        var canAnimate = animOn && !reduceMotion && !isSmall;

        /* ---- Modo estático: móvil, reduce-motion o animación apagada ---- */
        if (!canAnimate) {
            section.classList.add('is-static');
            return;
        }

        /* ---- Fallback vanilla (desktop, sin GSAP): IO añade is-revealed ---- */
        if (!stReady() || !useGsap) {
            if (useVanilla && 'IntersectionObserver' in window) {
                var io = new IntersectionObserver(function (entries) {
                    entries.forEach(function (e) {
                        if (e.isIntersecting) {
                            e.target.classList.add('is-revealed');
                            io.unobserve(e.target);
                        }
                    });
                }, { threshold: 0.22, rootMargin: '0px 0px -10% 0px' });
                rows.forEach(function (row) { io.observe(row); });
            } else {
                // Sin IO ni vanilla: no dejar texto oculto.
                section.classList.add('is-static');
            }
            // Si encoge a móvil, garantizar legibilidad.
            attachResizeStatic(section, disableBelow);
            return;
        }

        /* ============================================================
           GSAP + ScrollTrigger — desktop. Un timeline con scrub por fila.
           ============================================================ */
        var gsap = window.gsap;
        var ST   = window.ScrollTrigger;
        var triggerIds = [];

        rows.forEach(function (row, i) {
            buildRowTimeline(gsap, row, i);
        });

        if (handoffPin && !isHandoffSmall) {
            buildHandoffPin();
        }

        function buildHandoffPin() {
            var handoffId = psId + '-mission-handoff';
            triggerIds.push(handoffId);

            // HOLD (no overlap): fijamos la sección cuando su BORDE INFERIOR llega al
            // fondo del viewport ('bottom bottom'). Como la sección es más alta que el
            // viewport, al fijarse lo LLENA por completo (sin hueco que destape los
            // bloques fijos de atrás —parallax-monitor/hero—). El padding inferior
            // (CSS, solo desktop animado) sube el último producto para enmarcarlo.
            // pinSpacing:true → el siguiente bloque NO se solapa durante el hold; al
            // liberarse, Mission entra por scroll natural (z-index mayor) y cubre.
            ST.create({
                id: handoffId,
                trigger: section,
                start: 'bottom bottom',
                end: function () {
                    var viewport = window.innerHeight || document.documentElement.clientHeight || 1;
                    return '+=' + Math.round(viewport * (handoffDurationVh / 100));
                },
                pin: true,
                pinSpacing: true,
                anticipatePin: 1,
                invalidateOnRefresh: true,
                refreshPriority: -10,
                onEnter: function () {
                    section.classList.add('is-handoff-pinned');
                },
                onEnterBack: function () {
                    section.classList.add('is-handoff-pinned');
                },
                onLeave: function () {
                    section.classList.remove('is-handoff-pinned');
                },
                onLeaveBack: function () {
                    section.classList.remove('is-handoff-pinned');
                }
            });
        }

        function buildRowTimeline(gsap, row, i) {
            var visual = row.querySelector('.okip-ps__visual');
            var cardBg = row.querySelector('.okip-ps__card-bg');
            var label  = row.querySelector('.okip-ps__label');
            var types  = Array.prototype.slice.call(row.querySelectorAll('.okip-ps__type'));

            var tlId = psId + '-row-' + i;
            triggerIds.push(tlId);

            var stVars = {
                id:                  tlId,
                trigger:             row,
                start:               'top 82%',
                // Clamp evita que la última fila quede a medias cuando no hay
                // suficiente contenido debajo para alcanzar el end geométrico.
                end:                 'clamp(top 32%)',
                scrub:               scrub,
                invalidateOnRefresh: true
            };

            /* La ÚLTIMA fila debe terminar su reveal ANTES del HOLD pin (que fija la
               sección en 'bottom bottom'). Anclamos su fin al fondo de la sección con
               un pequeño adelanto (+=8%) para que llegue al hold 100% completa y se
               asiente, sin congelarse a medias. */
            var isLastRow = i === rows.length - 1;
            if (isLastRow && handoffPin && !isHandoffSmall) {
                stVars.endTrigger = section;
                stVars.end        = 'bottom bottom+=8%';
            }

            var tl = gsap.timeline({ scrollTrigger: stVars });

            /* 1) Recuadro izquierdo */
            if (visual) {
                if (leftEnter === 'mask-slide') {
                    tl.fromTo(visual,
                        { clipPath: 'inset(0 100% 0 0)', opacity: 0, y: 18 },
                        { clipPath: 'inset(0 0% 0 0)', opacity: 1, y: 0, ease: 'power2.out', duration: 0.42 }, 0);
                } else if (leftEnter === 'fade-up') {
                    tl.fromTo(visual,
                        { clipPath: 'inset(0 0% 0 0)', opacity: 0, y: 36 },
                        { opacity: 1, y: 0, ease: 'power2.out', duration: 0.42 }, 0);
                } else if (leftEnter === 'scale-soft') {
                    tl.fromTo(visual,
                        { clipPath: 'inset(0 0% 0 0)', opacity: 0, scale: 0.94 },
                        { opacity: 1, scale: 1, ease: 'power2.out', duration: 0.42 }, 0);
                } else { // none
                    tl.set(visual, { clipPath: 'inset(0 0% 0 0)', opacity: 1, y: 0 }, 0);
                }
            }

            /* etiqueta gris acompaña al recuadro */
            if (label) {
                tl.fromTo(label, { opacity: 0 }, { opacity: 1, duration: 0.15, ease: 'none' }, 0.32);
            }

            /* 2) Wipe del fondo de la tarjeta derecha */
            if (cardBg) {
                if (copyBgEnter === 'wipe-left') {
                    tl.fromTo(cardBg,
                        { clipPath: 'inset(0 100% 0 0)' },
                        { clipPath: 'inset(0 0% 0 0)', ease: 'power2.out', duration: 0.3 }, 0.36);
                } else if (copyBgEnter === 'fade') {
                    tl.fromTo(cardBg,
                        { clipPath: 'inset(0 0% 0 0)', opacity: 0 },
                        { opacity: 1, ease: 'none', duration: 0.3 }, 0.36);
                } else { // none
                    tl.set(cardBg, { clipPath: 'inset(0 0% 0 0)', opacity: 1 }, 0);
                }
            }

            /* 3) Reveal del texto */
            types.forEach(function (typeEl) {
                if (textReveal === 'scroll-typewriter') {
                    var chars = splitType(typeEl);
                    gsap.set(typeEl, { opacity: 1 });
                    gsap.set(chars, { opacity: 0 });
                    tl.to(chars, { opacity: 1, ease: 'none', duration: 0.4, stagger: { amount: 0.5 } }, 0.55);
                } else if (textReveal === 'fade-lines') {
                    tl.fromTo(typeEl, { opacity: 0, y: 8 }, { opacity: 1, y: 0, ease: 'power1.out', duration: 0.35 }, 0.55);
                } else { // none
                    tl.set(typeEl, { opacity: 1 }, 0);
                }
            });
        }

        /* ---- Resize: si encoge a móvil, desmontar y dejar todo legible ---- */
        var rt;
        window.addEventListener('resize', function () {
            window.clearTimeout(rt);
            rt = window.setTimeout(function () {
                var nowSmall = !!(window.matchMedia && window.matchMedia('(max-width: ' + disableBelow + 'px)').matches);
                if (nowSmall) {
                    // Matar los ST de esta sección.
                    ST.getAll().forEach(function (st) {
                        if (st.vars && st.vars.id && triggerIds.indexOf(String(st.vars.id)) !== -1) {
                            st.kill();
                        }
                    });
                    // Limpiar inline props que podrían dejar nodos ocultos.
                    rows.forEach(function (row) {
                        var nodes = row.querySelectorAll('.okip-ps__visual, .okip-ps__card-bg, .okip-ps__type, .okip-ps__char, .okip-ps__label');
                        gsap.set(nodes, { clearProps: 'opacity,transform,clipPath' });
                    });
                    section.classList.remove('is-handoff-pinned');
                    section.classList.add('is-static');
                } else {
                    ST.refresh();
                }
            }, 200);
        }, { passive: true });
    }

    /* Garantiza legibilidad si la ventana encoge bajo el breakpoint (rama vanilla). */
    function attachResizeStatic(section, disableBelow) {
        var rt;
        window.addEventListener('resize', function () {
            window.clearTimeout(rt);
            rt = window.setTimeout(function () {
                var nowSmall = !!(window.matchMedia && window.matchMedia('(max-width: ' + disableBelow + 'px)').matches);
                if (nowSmall) { section.classList.add('is-static'); }
            }, 200);
        }, { passive: true });
    }

    function init() {
        document.querySelectorAll('[data-okip-ps]').forEach(initPs);
    }

    if (window.OKIP && window.OKIP.ready) {
        window.OKIP.ready(init);
    } else if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
