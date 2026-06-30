/*
 * OKIP — bloque Video con Título (video-w-title).
 *
 * Solo REVEAL de entrada (determinista). El overlap de salida NO vive aquí: es
 * `position: sticky` por CSS (transition.mode = sticky-cover; ver style.css +
 * assets/css/transitions.css) → suave a cualquier velocidad, sin ScrollTrigger.
 *
 * Reveal robusto:
 *  - El estado inicial oculto lo ARMA este script (clase `is-anim-armed`). Si el
 *    script no corre, el texto queda visible (nunca oculto permanentemente).
 *  - Disparo determinista por IO de "línea de disparo": revela cuando el top del
 *    bloque cruza el 15% superior del viewport (el bloque cubre ~85%), UNA vez.
 *    Mismo punto que el reveal del navbar → coherente, sin estados a medias.
 *  - Sin IO / reduce-motion / data-anim=0 → revela de inmediato sin armar.
 *
 * No depende de GSAP ni de selectores de otros bloques. Flag `__okipVwtInit` evita
 * doble init.
 */
(function () {
    'use strict';

    var REVEAL_RATIO = 0.15; // top del bloque bajo el 15% superior = cubre ~85%

    // Elementos cuyo texto se anima LETRA a LETRA (el resto anima por bloque).
    var SPLIT_SELECTOR = '.okip-vwt__title, .okip-vwt__subtitle, .okip-vwt__box';

    function reduceMotion() {
        return (window.OKIP && window.OKIP.reduceMotion) ||
            (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
    }

    // Reparte un nodo de texto en palabras (inline-block, no se cortan) y cada
    // palabra en `.okip-vwt__char`. Conserva los espacios para que el salto de
    // línea caiga entre palabras. `ctx.i` es el índice global → cascada del CSS.
    function splitTextNode(textNode, ctx) {
        var text = textNode.nodeValue;
        if (!text) { return; }
        var frag  = document.createDocumentFragment();
        var parts = text.split(/(\s+)/); // conserva los grupos de espacios
        for (var p = 0; p < parts.length; p++) {
            var part = parts[p];
            if (part === '') { continue; }
            if (/^\s+$/.test(part)) {
                frag.appendChild(document.createTextNode(part));
                continue;
            }
            var word = document.createElement('span');
            word.className = 'okip-vwt__word';
            for (var c = 0; c < part.length; c++) {
                var ch = document.createElement('span');
                ch.className = 'okip-vwt__char';
                ch.style.setProperty('--okip-char-i', ctx.i);
                ch.textContent = part.charAt(c);
                word.appendChild(ch);
                ctx.i++;
            }
            frag.appendChild(word);
        }
        textNode.parentNode.replaceChild(frag, textNode);
    }

    // Recorre los hijos: los nodos de texto se parten; los elementos (p.ej. el
    // `.okip-vwt__highlight`) se conservan y se recorren para no perder su estilo.
    function splitNode(node, ctx) {
        var children = Array.prototype.slice.call(node.childNodes);
        for (var i = 0; i < children.length; i++) {
            var child = children[i];
            if (child.nodeType === 3) {
                splitTextNode(child, ctx);
            } else if (child.nodeType === 1) {
                splitNode(child, ctx);
            }
        }
    }

    function splitElement(el) {
        // aria-label con el texto íntegro → el lector lo anuncia como una frase,
        // no letra a letra; los spans generados quedan decorativos.
        var full = el.textContent;
        if (full && !el.getAttribute('aria-label')) {
            el.setAttribute('aria-label', full);
        }
        splitNode(el, { i: 0 });
        el.classList.add('is-split');
    }

    function splitSection(section) {
        var targets = section.querySelectorAll(SPLIT_SELECTOR);
        for (var i = 0; i < targets.length; i++) {
            splitElement(targets[i]);
        }
    }

    function reveal(section) {
        section.classList.add('is-revealed');
    }

    function setupSection(section) {
        if (section.__okipVwtInit) { return; }
        section.__okipVwtInit = true;

        var animEnabled = section.getAttribute('data-anim') === '1';

        // Sin animación, reduce-motion o sin IO → mostrar de inmediato (no armar).
        if (!animEnabled || reduceMotion() || typeof window.IntersectionObserver !== 'function') {
            reveal(section);
            return;
        }

        // Armar el estado inicial oculto SOLO ahora (por JS).
        section.classList.add('is-anim-armed');

        // Partir en letras los elementos marcados (título, subtítulo, cuadros).
        splitSection(section);

        // Caso "ya en vista": si el top ya cruzó el umbral, revelar sin esperar al IO.
        if (section.getBoundingClientRect().top <= window.innerHeight * REVEAL_RATIO) {
            reveal(section);
            return;
        }

        // IO de "línea de disparo": root reducido a una banda fina en el 15% superior;
        // intersecta cuando el TOP del bloque la cruza (el bloque cubre ~85%).
        var io = new window.IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    reveal(entry.target);
                    io.unobserve(entry.target);
                }
            });
        }, { rootMargin: '-15% 0px -85% 0px', threshold: 0 });

        io.observe(section);
    }

    function init() {
        var sections = document.querySelectorAll('[data-okip-vwt]');
        for (var i = 0; i < sections.length; i++) {
            setupSection(sections[i]);
        }
    }

    if (window.OKIP && window.OKIP.ready) {
        window.OKIP.ready(init);
    } else if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
